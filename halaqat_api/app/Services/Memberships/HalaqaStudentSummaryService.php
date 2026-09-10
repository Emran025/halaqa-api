<?php

namespace App\Services\Memberships;

use App\Models\DailyTracking;
use App\Models\FollowUpItem;
use App\Models\FollowUpPlan;
use App\Models\Halaqa;
use App\Models\LiveSession;
use App\Models\Mistake;
use App\Models\QuranEdition;
use App\Models\SessionTask;
use Illuminate\Support\Collection;

/**
 * Fetches a per-halaqa summary of all active students in a single batch.
 *
 * Instead of N×4 individual requests (follow-up-plan, follow-up-items,
 * trackings, progress) this service runs 4 bulk queries scoped to the
 * halaqa and returns everything keyed by student_id.
 */
class HalaqaStudentSummaryService
{
    /**
     * @param  Halaqa  $halaqa
     * @return array<string, array{
     *     student_id: string,
     *     follow_up_plan: array<string,mixed>|null,
     *     recent_follow_up_items: array<int, array<string,mixed>>,
     *     recent_trackings: array<int, array<string,mixed>>,
     *     progress: array<string,mixed>,
     * }>
     */
    public function forHalaqa(Halaqa $halaqa): array
    {
        // ─── 1. جلب معرّفات الطلاب النشطين + أسماءهم في استعلام واحد ─────────
        $memberships = $halaqa->activeMemberships()
            ->with('student:id,name')
            ->get();

        $studentIds = $memberships
            ->pluck('student_id')
            ->map(fn ($id) => (string) $id)
            ->all();

        // خريطة id => name لتجنب N استعلامات لاحقاً
        $studentNames = $memberships
            ->mapWithKeys(fn ($m) => [(string) $m->student_id => $m->student?->name ?? 'طالب']);

        if (empty($studentIds)) {
            return [];
        }

        // ─── 2. خطط المتابعة (FollowUpPlan) – واحدة لكل طالب ─────────────────
        $plans = FollowUpPlan::query()
            ->whereIn('student_id', $studentIds)
            ->with(['details.trackingType', 'details.trackingUnit', 'student.studentProfile.availability'])
            ->get()
            ->keyBy(fn ($p) => (string) $p->student_id);

        // ─── 3. بنود المتابعة (FollowUpItems) – آخر 5 لكل طالب ───────────────
        $followUpItems = FollowUpItem::query()
            ->whereIn('student_id', $studentIds)
            ->whereNotNull('halaqa_id')
            ->where('halaqa_id', $halaqa->id)
            ->with(['detail.trackingType', 'detail.trackingUnit'])
            ->orderBy('scheduled_for')
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($item) => (string) $item->student_id);

        // ─── 4. التتبع اليومي (DailyTracking) – آخر 5 لكل طالب ──────────────
        $trackings = DailyTracking::query()
            ->whereIn('student_id', $studentIds)
            ->with([
                'membership',
                'details.trackingType',
                'details.fromUnit.unitType',
                'details.toUnit.unitType',
                'details.mistakes.mistakeType',
                'details.mistakes.ayah',
            ])
            ->latest('date')
            ->get()
            ->groupBy(fn ($t) => (string) $t->student_id);

        // ─── 5. التقدم (Progress) – استعلام واحد لكل نوع ───────────────────
        $editionId = (int) (QuranEdition::query()->where('is_default', true)->value('id') ?? 1);
        $progressMap = $this->buildProgressMap($studentIds, $editionId);

        // ─── 6. تجميع النتائج ─────────────────────────────────────────────────
        $result = [];
        foreach ($studentIds as $sid) {
            $result[$sid] = [
                'student_id'               => $sid,
                'student_name'             => $studentNames->get($sid, 'طالب'),
                'follow_up_plan'           => $plan ? $this->serializePlan($plan) : null,
                'recent_follow_up_items'   => $this->serializeFollowUpItems($followUpItems->get($sid, collect())),
                'recent_trackings'         => $this->serializeTrackings($trackings->get($sid, collect())),
                'progress'                 => $progressMap[$sid] ?? $this->emptyProgress($sid),
            ];
        }

        return $result;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────────────

    /** @param string[] $studentIds */
    private function buildProgressMap(array $studentIds, int $editionId): array
    {
        // جلب إجماليات الجلسات دفعةً واحدة
        $sessionTotals = LiveSession::query()
            ->selectRaw('student_id, COUNT(*) as total')
            ->whereIn('student_id', $studentIds)
            ->where('state', 'ended')
            ->groupBy('student_id')
            ->pluck('total', 'student_id')
            ->map(fn ($v) => (int) $v);

        // المهام مع نوعها (task_type_code + student)
        $tasks = SessionTask::query()
            ->selectRaw('session_tasks.*, tracking_types.code as task_type_code, live_sessions.student_id as sid')
            ->join('live_sessions', 'live_sessions.id', '=', 'session_tasks.session_id')
            ->join('tracking_types', 'tracking_types.id', '=', 'session_tasks.tracking_type_id')
            ->whereIn('live_sessions.student_id', $studentIds)
            ->get();

        // إجماليات الأخطاء دفعةً واحدة
        $mistakeTotals = Mistake::query()
            ->selectRaw('live_sessions.student_id as sid, COUNT(*) as total')
            ->join('session_task_details', 'session_task_details.id', '=', 'mistakes.detail_id')
            ->join('session_tasks', 'session_tasks.id', '=', 'session_task_details.task_id')
            ->join('live_sessions', 'live_sessions.id', '=', 'session_tasks.session_id')
            ->whereIn('live_sessions.student_id', $studentIds)
            ->groupBy('live_sessions.student_id')
            ->pluck('total', 'sid')
            ->map(fn ($v) => (int) $v);

        $map = [];
        $tasksByStudent = $tasks->groupBy('sid');

        foreach ($studentIds as $sid) {
            $studentTasks = $tasksByStudent->get($sid, collect());
            $taskTypeGroups = $studentTasks->groupBy('task_type_code');

            $lastCompleted = [];
            foreach (['memorization', 'review', 'recitation'] as $type) {
                $latest = ($taskTypeGroups->get($type) ?? collect())
                    ->whereNotNull('completed_at')
                    ->sortByDesc('completed_at')
                    ->first();

                $lastCompleted[$type] = $latest ? [
                    'edition_id'      => $editionId,
                    'start_page'      => $latest->start_page,
                    'start_ayah_id'   => $latest->start_ayah_id,
                    'end_page'        => $latest->end_page,
                    'end_ayah_id'     => $latest->end_ayah_id,
                    'end_ayah_number' => null,
                ] : null;
            }

            $map[$sid] = [
                'student_id'   => $sid,
                'last_completed' => $lastCompleted,
                'totals' => [
                    'total_sessions'      => (int) ($sessionTotals->get($sid) ?? 0),
                    'total_tasks'         => $studentTasks->count(),
                    'total_mistakes'      => (int) ($mistakeTotals->get($sid) ?? 0),
                    'memorization_tasks'  => ($taskTypeGroups->get('memorization') ?? collect())->count(),
                    'review_tasks'        => ($taskTypeGroups->get('review') ?? collect())->count(),
                    'recitation_tasks'    => ($taskTypeGroups->get('recitation') ?? collect())->count(),
                ],
            ];
        }

        return $map;
    }

    private function serializePlan(\App\Models\FollowUpPlan $plan): array
    {
        return [
            'id'                              => (string) $plan->id,
            'student_id'                      => (string) $plan->student_id,
            'frequency'                       => $plan->frequency,
            'status'                          => $plan->status,
            'timezone'                        => $plan->timezone,
            'starts_on'                       => $plan->starts_on?->format('Y-m-d'),
            'ends_on'                         => $plan->ends_on?->format('Y-m-d'),
            'version'                         => (int) $plan->version,
            'approved_at'                     => $plan->approved_at?->toISOString(),
            'created_at'                      => $plan->created_at?->toISOString(),
            'updated_at'                      => $plan->updated_at?->toISOString(),
            'details'                         => $plan->details->map(fn ($d) => [
                'id'        => (string) $d->id,
                'task_type' => $d->trackingType?->code,
                'unit'      => $d->trackingUnit?->code,
                'amount'    => (float) $d->amount,
                'notes'     => $d->notes,
                'sort_order'=> (int) $d->sort_order,
            ])->values()->all(),
            'attendance_preferences'          => $plan->student?->studentProfile?->availability
                ? [
                    'days'       => $plan->student->studentProfile->availability->days ?? [],
                    'time_start' => $plan->student->studentProfile->availability->time_start,
                    'time_end'   => $plan->student->studentProfile->availability->time_end,
                ]
                : null,
        ];
    }

    private function serializeFollowUpItems(Collection $items): array
    {
        return $items->take(10)->map(fn ($item) => [
            'id'            => (string) $item->id,
            'student_id'    => (string) $item->student_id,
            'halaqa_id'     => (string) $item->halaqa_id,
            'scheduled_for' => $item->scheduled_for?->toISOString(),
            'state'         => $item->state,
            'task_type'     => $item->detail?->trackingType?->code,
            'unit'          => $item->detail?->trackingUnit?->code,
            'amount'        => $item->detail ? (float) $item->detail->amount : null,
            'completed_at'  => $item->completed_at?->toISOString(),
            'skipped_at'    => $item->skipped_at?->toISOString(),
            'skip_reason'   => $item->skip_reason,
        ])->values()->all();
    }

    private function serializeTrackings(Collection $trackings): array
    {
        return $trackings->take(5)->map(fn ($t) => [
            'id'         => (string) $t->id,
            'student_id' => (string) $t->student_id,
            'date'       => $t->date?->format('Y-m-d'),
            'notes'      => $t->notes,
            'details'    => $t->details->map(fn ($d) => [
                'id'               => (string) $d->id,
                'task_type'        => $d->trackingType?->code,
                'from_unit'        => $d->fromUnit?->code ?? null,
                'to_unit'          => $d->toUnit?->code ?? null,
                'from_unit_number' => $d->from_unit_number,
                'to_unit_number'   => $d->to_unit_number,
                'notes'            => $d->notes,
            ])->values()->all(),
        ])->values()->all();
    }

    /** @return array<string,mixed> */
    private function emptyProgress(string $sid): array
    {
        return [
            'student_id'   => $sid,
            'last_completed' => ['memorization' => null, 'review' => null, 'recitation' => null],
            'totals' => [
                'total_sessions'     => 0,
                'total_tasks'        => 0,
                'total_mistakes'     => 0,
                'memorization_tasks' => 0,
                'review_tasks'       => 0,
                'recitation_tasks'   => 0,
            ],
        ];
    }
}
