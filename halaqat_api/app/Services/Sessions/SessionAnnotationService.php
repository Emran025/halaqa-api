<?php

namespace App\Services\Sessions;

use App\Exceptions\ApiConflictException;
use App\Models\LiveSession;
use App\Models\Mistake;
use App\Models\QuranAyah;
use App\Models\QuranEdition;
use App\Models\SessionTask;
use App\Models\TaskEvaluation;
use App\Models\TaskNote;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SessionAnnotationService
{
    public function createMistake(User $actor, LiveSession $session, SessionTask $task, array $data): Mistake
    {
        $this->assertTaskBelongsToSession($session, $task);

        return DB::transaction(function () use ($actor, $task, $data): Mistake {
            $edition = QuranEdition::query()->where('is_default', true)->first();
            $ayah = $edition === null ? null : QuranAyah::query()->where('id', $data['ayah_id'])->where('edition_id', $edition->id)->first();
            if ($ayah === null) {
                throw new ApiConflictException('The Quran ayah is not available in the default edition.', 'ayah_not_found', 'ayah', $data['ayah_id']);
            }
            if (array_key_exists('page_number', $data) && $data['page_number'] !== null && (int) $ayah->page_number !== (int) $data['page_number']) {
                throw new ApiConflictException('The ayah does not belong to the requested page.', 'ayah_page_mismatch', 'ayah', $data['ayah_id']);
            }

            $detail = DB::table('tracking_details')->where('session_task_id', $task->id)->value('uuid');
            if ($detail === null) {
                throw new ApiConflictException('A tracking detail is required before recording a mistake.', 'tracking_detail_required', 'task', $task->id);
            }
            $type = DB::table('mistake_types')->where('code', $data['mistake_type'])->value('id');
            if ($type === null) {
                throw new ApiConflictException('The mistake type is not available.', 'mistake_type_not_found', 'mistake_type', $data['mistake_type']);
            }

            $existingOperation = Mistake::query()->where('client_operation_id', $data['client_operation_id'])->first();
            if ($existingOperation !== null) {
                if ((string) $existingOperation->detail?->session_task_id !== (string) $task->id || (string) $existingOperation->created_by_user_id !== (string) $actor->id) {
                    throw new ApiConflictException('The client operation already belongs to another mistake.', 'idempotency_key_reused', 'client_operation_id', $data['client_operation_id']);
                }

                return $existingOperation->load(['detail.task.session', 'mistakeType', 'creator', 'ayah']);
            }
            $existing = Mistake::query()->where('tracking_detail_id', $detail)->where('ayah_id', $data['ayah_id'])->where('word_index', $data['word_index'])->where('mistake_type_id', $type)->first();
            if ($existing !== null) {
                return $existing->load(['detail.task.session', 'mistakeType', 'creator', 'ayah']);
            }

            return Mistake::create(['id' => (string) Str::uuid(), 'tracking_detail_id' => $detail, 'ayah_id' => $data['ayah_id'], 'edition_id' => $edition->id, 'word_index' => $data['word_index'], 'mistake_type_id' => $type, 'source_role' => $actor->role, 'note' => $data['note'] ?? null, 'client_operation_id' => $data['client_operation_id'], 'created_by_user_id' => $actor->id])->load(['detail.task.session', 'mistakeType', 'creator', 'ayah']);
        });
    }

    public function listMistakes(LiveSession $session, SessionTask $task, int $perPage = 20): LengthAwarePaginator
    {
        $this->assertTaskBelongsToSession($session, $task);

        return Mistake::query()->whereHas('detail', fn ($query) => $query->where('session_task_id', $task->id))->with(['detail.task.session', 'mistakeType', 'creator', 'ayah'])->latest('created_at')->paginate($perPage);
    }

    public function updateMistake(User $actor, LiveSession $session, SessionTask $task, Mistake $mistake, array $data): Mistake
    {
        $this->assertTaskBelongsToSession($session, $task);
        $this->assertMistakeBelongsToTask($task, $mistake);
        $changes = [];
        if (array_key_exists('mistake_type', $data)) {
            $type = DB::table('mistake_types')->where('code', $data['mistake_type'])->value('id');
            if ($type === null) {
                throw new ApiConflictException('The mistake type is not available.', 'mistake_type_not_found', 'mistake_type', $data['mistake_type']);
            }
            $changes['mistake_type_id'] = $type;
        }
        if (array_key_exists('note', $data)) {
            $changes['note'] = $data['note'];
        }
        $mistake->update($changes);

        return $mistake->fresh(['detail.task.session', 'mistakeType', 'creator', 'ayah']);
    }

    public function deleteMistake(User $actor, LiveSession $session, SessionTask $task, Mistake $mistake): void
    {
        $this->assertTaskBelongsToSession($session, $task);
        $this->assertMistakeBelongsToTask($task, $mistake);
        $mistake->delete();
    }

    public function saveNote(User $actor, LiveSession $session, SessionTask $task, array $data): TaskNote
    {
        $this->assertTaskBelongsToSession($session, $task);

        $existingOperation = TaskNote::query()->where('client_operation_id', $data['client_operation_id'])->first();
        if ($existingOperation !== null) {
            if ((string) $existingOperation->session_task_id !== (string) $task->id || (string) $existingOperation->author_id !== (string) $actor->id) {
                throw new ApiConflictException('The client operation already belongs to another note.', 'idempotency_key_reused', 'client_operation_id', $data['client_operation_id']);
            }

            return $existingOperation->load('author');
        }

        return TaskNote::query()->create(['id' => (string) Str::uuid(), 'session_task_id' => $task->id, 'author_id' => $actor->id, 'note' => $data['body'], 'client_operation_id' => $data['client_operation_id']])->load('author');
    }

    public function listNotes(LiveSession $session, SessionTask $task, int $perPage = 20): LengthAwarePaginator
    {
        $this->assertTaskBelongsToSession($session, $task);

        return TaskNote::query()->where('session_task_id', $task->id)->with('author')->latest('created_at')->paginate($perPage);
    }

    public function updateNote(User $actor, LiveSession $session, SessionTask $task, TaskNote $note, array $data): TaskNote
    {
        $this->assertTaskBelongsToSession($session, $task);
        $this->assertNoteBelongsToTask($task, $note);
        if ((string) $note->author_id !== (string) $actor->id) {
            throw new ApiConflictException('Only the note author can update this note.', 'note_not_owned', 'note', $note->id);
        }

        $note->update(['note' => $data['body']]);

        return $note->fresh('author');
    }

    public function deleteNote(User $actor, LiveSession $session, SessionTask $task, TaskNote $note): void
    {
        $this->assertTaskBelongsToSession($session, $task);
        $this->assertNoteBelongsToTask($task, $note);
        if ((string) $note->author_id !== (string) $actor->id) {
            throw new ApiConflictException('Only the note author can delete this note.', 'note_not_owned', 'note', $note->id);
        }

        $note->delete();
    }

    public function saveEvaluation(User $actor, LiveSession $session, SessionTask $task, array $data): TaskEvaluation
    {
        $this->assertTaskBelongsToSession($session, $task);

        $evaluation = TaskEvaluation::query()->firstOrNew(['session_task_id' => $task->id, 'evaluator_id' => $actor->id]);
        if (! $evaluation->exists) {
            $evaluation->id = (string) Str::uuid();
        }
        $evaluation->fill(['evaluator_role' => $actor->role, 'score' => $data['score'], 'comment' => $data['comment'] ?? null]);
        $evaluation->save();

        return $evaluation->fresh('evaluator');
    }

    public function listEvaluations(LiveSession $session, SessionTask $task): array
    {
        $this->assertTaskBelongsToSession($session, $task);
        $evaluations = TaskEvaluation::query()->where('session_task_id', $task->id)->with('evaluator')->get()->keyBy('evaluator_role');

        return ['teacher' => $evaluations->get('teacher'), 'student' => $evaluations->get('student')];
    }

    private function assertTaskBelongsToSession(LiveSession $session, SessionTask $task): void
    {
        if ((string) $task->session_id !== (string) $session->id) {
            throw new ApiConflictException('The task does not belong to this session.', 'task_session_mismatch', 'task', $task->id);
        }
    }

    private function assertMistakeBelongsToTask(SessionTask $task, Mistake $mistake): void
    {
        if ((string) $mistake->detail?->session_task_id !== (string) $task->id) {
            throw new ApiConflictException('The mistake does not belong to this task.', 'mistake_task_mismatch', 'mistake', $mistake->id);
        }
    }

    private function assertNoteBelongsToTask(SessionTask $task, TaskNote $note): void
    {
        if ((string) $note->session_task_id !== (string) $task->id) {
            throw new ApiConflictException('The note does not belong to this task.', 'note_task_mismatch', 'note', $note->id);
        }
    }
}
