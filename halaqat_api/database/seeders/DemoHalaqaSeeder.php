<?php

namespace Database\Seeders;

use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DemoHalaqaSeeder extends Seeder
{
    private const TIMEZONE = 'Asia/Riyadh';

    public function run(): void
    {
        $this->call([
            MistakeTypeSeeder::class,
            QuranReferenceSeeder::class,
            QuranRangeUnitSeeder::class,
        ]);

        $now = CarbonImmutable::now('UTC');
        $password = Hash::make('HalaqaDemo!2026');
        $teacher = $this->teacher();
        $students = $this->students();
        $applicant = $this->applicant();

        DB::transaction(function () use ($now, $password, $teacher, $students, $applicant): void {
            $this->seedUsers($now, $password, $teacher, $students, $applicant);
            $this->seedProfiles($now, $teacher, $students, $applicant);

            $halaqa = [
                'id' => $this->id('halaqa'),
                'teacher_id' => $teacher['id'],
                'name' => 'حلقة الإتقان اليومية',
                'description' => 'حلقة تجريبية متكاملة لعرض الحفظ اليومي والمراجعة والتقارير التربوية داخل منصة Halaqa.',
                'gender' => 'male',
                'country' => 'المملكة العربية السعودية',
                'residence' => 'الرياض',
                'avatar_path' => null,
                'status' => 'active',
                'max_students' => 12,
                'timezone' => self::TIMEZONE,
                'created_at' => $now->subMonths(4)->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ];
            $this->write('halaqas', ['id' => $halaqa['id']], $halaqa);

            $memberships = $this->seedMemberships($now, $halaqa['id'], $students);
            $this->seedAvailability($now, $students, $applicant);
            $plans = $this->seedPlans($now, $teacher['id'], $students);
            $editionId = $this->editionId();
            $rangeIds = $this->rangeIds($editionId);
            $this->seedFollowUpItemsAndDailyTracking($now, $halaqa['id'], $students, $memberships, $plans, $rangeIds);
            $this->seedSessionsAndReports($now, $teacher['id'], $halaqa['id'], $students, $plans, $rangeIds, $editionId);
            $this->seedApplicantRequest($now, $teacher['id'], $halaqa['id'], $applicant);
            $this->seedNotifications($now, $teacher['id'], $students, $plans);
        });
    }

    /** @return array{id: string, name: string, email: string, username: string, birth_date: string, phone: string} */
    private function teacher(): array
    {
        return [
            'id' => $this->id('teacher'),
            'name' => 'الشيخ عبد الرحمن السالمي',
            'email' => 'teacher.demo@halaqa.local',
            'username' => 'teacher_demo',
            'birth_date' => '1981-04-18',
            'phone' => '500000001',
        ];
    }

    /** @return array<string, array{id: string, name: string, email: string, username: string, birth_date: string, phone: string, memorized_juz: float, memorization_level: string, review_level: string, joined_months: int, page_start: int, daily_pages: int, review_juz: int, behavior: int}> */
    private function students(): array
    {
        return [
            'ahmad' => ['id' => $this->id('student:ahmad'), 'name' => 'أحمد ياسر الغامدي', 'email' => 'ahmad.demo@halaqa.local', 'username' => 'ahmad_demo', 'birth_date' => '2007-02-12', 'phone' => '500000101', 'memorized_juz' => 4.5, 'memorization_level' => 'متوسط — بداية سورة المجادلة', 'review_level' => 'مراجعة الأجزاء الثلاثة الأخيرة', 'joined_months' => 4, 'page_start' => 534, 'daily_pages' => 2, 'review_juz' => 29, 'behavior' => 5],
            'yusuf' => ['id' => $this->id('student:yusuf'), 'name' => 'يوسف عمر الحارثي', 'email' => 'yusuf.demo@halaqa.local', 'username' => 'yusuf_demo', 'birth_date' => '2008-08-30', 'phone' => '500000102', 'memorized_juz' => 3.0, 'memorization_level' => 'متوسط — تثبيت جزء عم وتبارك', 'review_level' => 'مراجعة منتظمة لجزأين', 'joined_months' => 3, 'page_start' => 560, 'daily_pages' => 1, 'review_juz' => 30, 'behavior' => 4],
            'khalid' => ['id' => $this->id('student:khalid'), 'name' => 'خالد محمد الزهراني', 'email' => 'khalid.demo@halaqa.local', 'username' => 'khalid_demo', 'birth_date' => '2006-11-06', 'phone' => '500000103', 'memorized_juz' => 9.0, 'memorization_level' => 'متقدم — تثبيت المحفوظ', 'review_level' => 'مراجعة خمسة أجزاء متتابعة', 'joined_months' => 4, 'page_start' => 462, 'daily_pages' => 1, 'review_juz' => 27, 'behavior' => 5],
            'abdullah' => ['id' => $this->id('student:abdullah'), 'name' => 'عبد الله فهد المطيري', 'email' => 'abdullah.demo@halaqa.local', 'username' => 'abdullah_demo', 'birth_date' => '2009-01-24', 'phone' => '500000104', 'memorized_juz' => 2.0, 'memorization_level' => 'متوسط — حفظ جزء عم', 'review_level' => 'مراجعة جزء عم أسبوعياً', 'joined_months' => 2, 'page_start' => 582, 'daily_pages' => 1, 'review_juz' => 30, 'behavior' => 4],
            'sulaiman' => ['id' => $this->id('student:sulaiman'), 'name' => 'سليمان صالح العتيبي', 'email' => 'sulaiman.demo@halaqa.local', 'username' => 'sulaiman_demo', 'birth_date' => '2007-06-19', 'phone' => '500000105', 'memorized_juz' => 6.5, 'memorization_level' => 'متوسط مرتفع — بداية جزء الذاريات', 'review_level' => 'مراجعة أربعة أجزاء متدرجة', 'joined_months' => 4, 'page_start' => 518, 'daily_pages' => 2, 'review_juz' => 28, 'behavior' => 5],
            'omar' => ['id' => $this->id('student:omar'), 'name' => 'عمر عبد العزيز القحطاني', 'email' => 'omar.demo@halaqa.local', 'username' => 'omar_demo', 'birth_date' => '2005-03-09', 'phone' => '500000106', 'memorized_juz' => 12.0, 'memorization_level' => 'متقدم — مراجعة تثبيتية', 'review_level' => 'مراجعة مكثفة للأجزاء المحفوظة', 'joined_months' => 3, 'page_start' => 434, 'daily_pages' => 1, 'review_juz' => 24, 'behavior' => 5],
        ];
    }

    /** @return array{id: string, name: string, email: string, username: string, birth_date: string, phone: string} */
    private function applicant(): array
    {
        return [
            'id' => $this->id('applicant:ibrahim'),
            'name' => 'إبراهيم ناصر الدوسري',
            'email' => 'ibrahim.applicant@halaqa.local',
            'username' => 'ibrahim_applicant',
            'birth_date' => '2008-12-05',
            'phone' => '500000107',
        ];
    }

    /** @param array<string, array<string, int|float|string>> $students */
    private function seedUsers(CarbonImmutable $now, string $password, array $teacher, array $students, array $applicant): void
    {
        $accounts = array_merge([$teacher], array_values($students), [$applicant]);
        foreach ($accounts as $account) {
            $role = $account['id'] === $teacher['id'] ? 'teacher' : 'student';
            $createdAt = $role === 'teacher' ? $now->subYears(3) : $now->subMonths(5);
            $this->write('users', ['id' => $account['id']], [
                'id' => $account['id'],
                'role' => $role,
                'username' => $account['username'],
                'name' => $account['name'],
                'email' => $account['email'],
                'password' => $password,
                'gender' => 'male',
                'birth_date' => $account['birth_date'],
                'country' => 'المملكة العربية السعودية',
                'city' => 'الرياض',
                'residence' => 'الرياض',
                'avatar_path' => null,
                'phone' => $account['phone'],
                'phone_zone' => '+966',
                'whatsapp_phone' => $account['phone'],
                'whatsapp_zone' => '+966',
                'status' => 'active',
                'email_verified_at' => $createdAt->addDay()->toDateTimeString(),
                'last_login_at' => $now->subHours($role === 'teacher' ? 2 : 8)->toDateTimeString(),
                'remember_token' => null,
                'created_at' => $createdAt->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ]);
        }
    }

    /** @param array<string, array<string, int|float|string>> $students */
    private function seedProfiles(CarbonImmutable $now, array $teacher, array $students, array $applicant): void
    {
        $this->write('teacher_profiles', ['user_id' => $teacher['id']], [
            'user_id' => $teacher['id'],
            'teacher_code' => 'ITQAN-AR-01',
            'qualification' => 'إجازة في رواية حفص عن عاصم، ودبلوم تأهيل معلمي القرآن الكريم',
            'experience_years' => 14,
            'bio' => 'معلم قرآن مهتم بالمتابعة الواقعية والتدرج في الحفظ والمراجعة وربط الخطة بقدرة كل طالب.',
            'available_time' => '18:30:00',
            'max_halaqas' => 3,
            'created_at' => $now->subYears(3)->toDateTimeString(),
            'updated_at' => $now->toDateTimeString(),
        ]);

        foreach ($students as $key => $student) {
            $this->write('student_profiles', ['user_id' => $student['id']], [
                'user_id' => $student['id'],
                'memorization_level' => $student['memorization_level'],
                'review_level' => $student['review_level'],
                'memorized_juz_count' => $student['memorized_juz'],
                'memorized_surah_ids' => $this->json([78, 79, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 114]),
                'last_completed_unit' => $this->json([
                    'task_type' => 'memorization',
                    'unit' => 'page',
                    'amount' => $student['daily_pages'],
                    'note' => 'آخر إنجاز مسجل قبل تشغيل بيانات المحاكاة.',
                ]),
                'previous_memorization_notes' => 'ينتظم في الحضور ويستفيد من تقسيم المراجعة إلى وحدات قصيرة قابلة للقياس.',
                'stop_reasons' => null,
                'bio' => 'طالب في بيئة عرض حلقة الإتقان اليومية.',
                'created_at' => $now->subMonths((int) $student['joined_months'])->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ]);
        }

        $this->write('student_profiles', ['user_id' => $applicant['id']], [
            'user_id' => $applicant['id'],
            'memorization_level' => 'مبتدئ — حفظ جزء عم',
            'review_level' => 'مراجعة قصيرة يومية',
            'memorized_juz_count' => 1.0,
            'memorized_surah_ids' => $this->json([78, 79, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 114]),
            'last_completed_unit' => $this->json(['task_type' => 'memorization', 'unit' => 'page', 'amount' => 1, 'note' => 'صفحة واحدة من جزء عم.']),
            'previous_memorization_notes' => 'يرغب في الالتزام بحلقة مسائية ثابتة تحت متابعة معلم.',
            'stop_reasons' => null,
            'bio' => 'متقدم تجريبي لاختبار مسار طلب الانضمام.',
            'created_at' => $now->subDays(6)->toDateTimeString(),
            'updated_at' => $now->toDateTimeString(),
        ]);
    }

    /** @param array<string, array<string, int|float|string>> $students
     * @return array<string, string>
     */
    private function seedMemberships(CarbonImmutable $now, string $halaqaId, array $students): array
    {
        $memberships = [];
        foreach ($students as $key => $student) {
            $membershipId = $this->id("membership:{$key}");
            $memberships[$key] = $membershipId;
            $joinedAt = $now->subMonths((int) $student['joined_months'])->setTime(16, 0);
            $this->write('halaqa_memberships', ['id' => $membershipId], [
                'id' => $membershipId,
                'halaqa_id' => $halaqaId,
                'student_id' => $student['id'],
                'status' => 'active',
                'joined_at' => $joinedAt->toDateTimeString(),
                'left_at' => null,
                'created_at' => $joinedAt->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ]);
        }

        return $memberships;
    }

    /** @param array<string, array<string, int|float|string>> $students */
    private function seedAvailability(CarbonImmutable $now, array $students, array $applicant): void
    {
        $accounts = array_merge($students, ['applicant' => $applicant]);
        foreach ($accounts as $key => $account) {
            $this->write('student_availability_profiles', ['student_id' => $account['id']], [
                'student_id' => $account['id'],
                'timezone' => self::TIMEZONE,
                'preferred_session_duration_minutes' => $key === 'khalid' ? 45 : 30,
                'created_at' => $now->subMonths(2)->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ]);

            foreach ([[0, '18:30:00', '19:30:00', true], [2, '18:30:00', '19:30:00', true], [4, '18:30:00', '19:30:00', false]] as [$day, $from, $to, $preferred]) {
                $this->write('student_availability_slots', [
                    'student_id' => $account['id'],
                    'day_of_week' => $day,
                    'available_from' => $from,
                    'available_to' => $to,
                ], [
                    'student_id' => $account['id'],
                    'day_of_week' => $day,
                    'available_from' => $from,
                    'available_to' => $to,
                    'is_preferred' => $preferred,
                    'created_at' => $now->subMonths(2)->toDateTimeString(),
                    'updated_at' => $now->toDateTimeString(),
                ]);
            }
        }
    }

    /** @param array<string, array<string, int|float|string>> $students
     * @return array<string, array{id: string, details: array<string, array{id: string, type_id: int, unit_id: int, amount: int|float, notes: string}>}>
     */
    private function seedPlans(CarbonImmutable $now, string $teacherId, array $students): array
    {
        $plans = [];
        foreach ($students as $key => $student) {
            $planId = $this->id("plan:{$key}");
            $memoryAmount = (int) $student['daily_pages'];
            $plans[$key] = [
                'id' => $planId,
                'details' => [
                    'memorization' => [
                        'id' => $this->id("plan-detail:{$key}:memorization"),
                        'type_id' => 1,
                        'unit_id' => 5,
                        'amount' => $memoryAmount,
                        'notes' => "حفظ {$memoryAmount} صفحة يومياً مع تسميع مضبوط في الجلسة التالية.",
                    ],
                    'review' => [
                        'id' => $this->id("plan-detail:{$key}:review"),
                        'type_id' => 2,
                        'unit_id' => 1,
                        'amount' => 1,
                        'notes' => "مراجعة الجزء {$student['review_juz']} وفق مبدأ المراجعة المتدرجة.",
                    ],
                ],
            ];

            if (in_array($key, ['ahmad', 'khalid'], true)) {
                $plans[$key]['details']['recitation'] = [
                    'id' => $this->id("plan-detail:{$key}:recitation"),
                    'type_id' => 3,
                    'unit_id' => 4,
                    'amount' => 1,
                    'notes' => 'تلاوة ربع حزب مرتين أسبوعياً مع التركيز على السلامة والإيقاع.',
                ];
            }

            $this->write('follow_up_plans', ['id' => $planId], [
                'id' => $planId,
                'student_id' => $student['id'],
                'created_by_user_id' => $teacherId,
                'source_registration_request_id' => null,
                'frequency' => 'daily',
                'status' => 'active',
                'timezone' => self::TIMEZONE,
                'starts_on' => $now->subMonths((int) $student['joined_months'])->toDateString(),
                'ends_on' => null,
                'version' => 3,
                'approved_by_user_id' => $teacherId,
                'approved_at' => $now->subMonths(1)->toDateTimeString(),
                'created_at' => $now->subMonths((int) $student['joined_months'])->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ]);

            foreach ($plans[$key]['details'] as $detail) {
                $this->write('follow_up_plan_details', ['id' => $detail['id']], [
                    'id' => $detail['id'],
                    'plan_id' => $planId,
                    'tracking_type_id' => $detail['type_id'],
                    'tracking_unit_id' => $detail['unit_id'],
                    'amount' => $detail['amount'],
                    'notes' => $detail['notes'],
                    'sort_order' => $detail['type_id'],
                    'created_at' => $now->subMonths((int) $student['joined_months'])->toDateTimeString(),
                    'updated_at' => $now->toDateTimeString(),
                ]);
            }
        }

        return $plans;
    }

    /** @param array<string, array<string, int|float|string>> $students
     * @param array<string, string> $memberships
     * @param array<string, array{id: string, details: array<string, array{id: string, type_id: int, unit_id: int, amount: int|float, notes: string}>}> $plans
     * @param array<string, int> $rangeIds
     */
    private function seedFollowUpItemsAndDailyTracking(CarbonImmutable $now, string $halaqaId, array $students, array $memberships, array $plans, array $rangeIds): void
    {
        foreach ($students as $key => $student) {
            foreach (range(-14, 7) as $dayOffset) {
                foreach ($plans[$key]['details'] as $detailKey => $detail) {
                    $recitationDay = (($dayOffset % 7) + 7) % 7;
                    if ($detailKey === 'recitation' && !in_array($recitationDay, [1, 4], true)) {
                        continue;
                    }

                    $state = $dayOffset < -1 ? 'completed' : ($dayOffset === -1 ? 'overdue' : ($dayOffset === 0 ? 'due' : 'upcoming'));
                    if ($dayOffset === -6 && $detailKey === 'review' && $key === 'yusuf') {
                        $state = 'skipped';
                    }
                    $scheduledAt = $this->at($dayOffset, $detailKey === 'memorization' ? 18 : 19, 30);
                    $itemId = $this->id("follow-up:{$key}:{$detailKey}:{$dayOffset}");
                    $this->write('follow_up_items', ['id' => $itemId], [
                        'id' => $itemId,
                        'plan_id' => $plans[$key]['id'],
                        'plan_detail_id' => $detail['id'],
                        'student_id' => $student['id'],
                        'halaqa_id' => $halaqaId,
                        'scheduled_for' => $scheduledAt->toDateTimeString(),
                        'timezone' => self::TIMEZONE,
                        'state' => $state,
                        'completed_at' => $state === 'completed' ? $scheduledAt->addMinutes(34)->toDateTimeString() : null,
                        'skipped_at' => $state === 'skipped' ? $scheduledAt->addHours(4)->toDateTimeString() : null,
                        'skip_reason' => $state === 'skipped' ? 'تعذر الحضور بعذر مسبق، وأعيدت المهمة إلى موعد لاحق.' : null,
                        'rescheduled_from_id' => null,
                        'notification_sent_at' => $dayOffset >= 0 ? $scheduledAt->subMinutes(30)->toDateTimeString() : null,
                        'last_client_operation_id' => $this->id("follow-up-operation:{$key}:{$detailKey}:{$dayOffset}"),
                        'last_operation_by_user_id' => $student['id'],
                        'last_operation_type' => $state === 'completed' ? 'complete' : 'schedule',
                        'reschedule_reason' => null,
                        'created_at' => $scheduledAt->subDay()->toDateTimeString(),
                        'updated_at' => $now->toDateTimeString(),
                    ]);
                }
            }

            foreach (range(-27, 0) as $dayOffset) {
                $trackingId = $this->id("daily-tracking:{$key}:{$dayOffset}");
                $attendance = $this->attendanceFor($key, $dayOffset);
                $date = $this->at($dayOffset, 0)->setTimezone(self::TIMEZONE)->toDateString();
                $this->write('daily_trackings', ['id' => $trackingId], [
                    'id' => $trackingId,
                    'membership_id' => $memberships[$key],
                    'student_id' => $student['id'],
                    'date' => $date,
                    'attendance_type' => $attendance,
                    'note' => $this->attendanceNote($attendance),
                    'behavior_note' => $attendance === 'present' ? $student['behavior'] : null,
                    'created_at' => $this->at($dayOffset, 20)->toDateTimeString(),
                    'updated_at' => $now->toDateTimeString(),
                ]);

                if ($attendance === 'excused') {
                    continue;
                }

                $dayIndex = 27 + $dayOffset;
                $fromPage = min(604, (int) $student['page_start'] + $dayIndex * (int) $student['daily_pages']);
                $toPage = min(604, $fromPage + max(0, (int) $student['daily_pages'] - 1));
                $memoryDetailId = $this->id("tracking-detail:{$key}:memorization:{$dayOffset}");
                $this->write('tracking_details', ['uuid' => $memoryDetailId], [
                    'uuid' => $memoryDetailId,
                    'tracking_id' => $trackingId,
                    'session_task_id' => null,
                    'tracking_type_id' => 1,
                    'from_unit_id' => $this->rangeId($rangeIds, 'page', $fromPage),
                    'to_unit_id' => $this->rangeId($rangeIds, 'page', $toPage),
                    'actual_amount' => $attendance === 'absent' ? 0 : $student['daily_pages'],
                    'state' => $attendance === 'absent' ? 'skipped' : 'completed',
                    'comment' => $attendance === 'absent' ? 'لم يتسنّ للطالب التسميع في هذا اليوم.' : 'تم تسميع الحفظ وفق الخطة مع تثبيت المواضع المحتاجة إلى مراجعة.',
                    'score' => $attendance === 'late' ? 82 : ($attendance === 'absent' ? null : 94),
                    'gap' => $attendance === 'late' ? 0.25 : 0,
                    'created_at' => $this->at($dayOffset, 20)->toDateTimeString(),
                    'updated_at' => $now->toDateTimeString(),
                ]);

                if ($attendance !== 'absent') {
                    $reviewDetailId = $this->id("tracking-detail:{$key}:review:{$dayOffset}");
                    $reviewJuz = min(30, (int) $student['review_juz']);
                    $this->write('tracking_details', ['uuid' => $reviewDetailId], [
                        'uuid' => $reviewDetailId,
                        'tracking_id' => $trackingId,
                        'session_task_id' => null,
                        'tracking_type_id' => 2,
                        'from_unit_id' => $this->rangeId($rangeIds, 'juz', $reviewJuz),
                        'to_unit_id' => $this->rangeId($rangeIds, 'juz', $reviewJuz),
                        'actual_amount' => 1,
                        'state' => 'completed',
                        'comment' => 'مراجعة مركزة مع التنبيه إلى مواضع التشابه اللفظي.',
                        'score' => $attendance === 'late' ? 84 : 92,
                        'gap' => $attendance === 'late' ? 0.15 : 0,
                        'created_at' => $this->at($dayOffset, 20)->toDateTimeString(),
                        'updated_at' => $now->toDateTimeString(),
                    ]);
                }
            }
        }
    }

    /** @param array<string, array<string, int|float|string>> $students
     * @param array<string, array{id: string, details: array<string, array{id: string, type_id: int, unit_id: int, amount: int|float, notes: string}>}> $plans
     * @param array<string, int> $rangeIds
     */
    private function seedSessionsAndReports(CarbonImmutable $now, string $teacherId, string $halaqaId, array $students, array $plans, array $rangeIds, int $editionId): void
    {
        $completedSessions = [
            ['key' => 'ahmad', 'offset' => -3, 'type_id' => 1, 'summary' => 'تسميع متقن لوجهين مع تحسن واضح في ضبط أوائل الآيات وربط المراجعة بالحفظ الجديد.'],
            ['key' => 'khalid', 'offset' => -10, 'type_id' => 3, 'summary' => 'جلسة مراجعة وتلاوة ركزت على مخارج الحروف والوقف السليم، مع استجابة جيدة للتوجيه.'],
        ];

        foreach ($completedSessions as $sessionSpec) {
            $key = $sessionSpec['key'];
            $student = $students[$key];
            $scheduledAt = $this->at($sessionSpec['offset'], 18, 30);
            $sessionId = $this->id("session:{$key}:completed");
            $followUpDetail = $key === 'khalid' ? 'recitation' : 'memorization';
            $followUpItemId = $this->id("follow-up:{$key}:{$followUpDetail}:{$sessionSpec['offset']}");
            $this->write('live_sessions', ['id' => $sessionId], [
                'id' => $sessionId,
                'halaqa_id' => $halaqaId,
                'teacher_id' => $teacherId,
                'student_id' => $student['id'],
                'follow_up_item_id' => $followUpItemId,
                'task_type_id' => $sessionSpec['type_id'],
                'state' => 'ended',
                'scheduled_at' => $scheduledAt->toDateTimeString(),
                'requested_at' => $scheduledAt->subHours(6)->toDateTimeString(),
                'accepted_at' => $scheduledAt->subHours(5)->toDateTimeString(),
                'connected_at' => $scheduledAt->toDateTimeString(),
                'ended_at' => $scheduledAt->addMinutes(32)->toDateTimeString(),
                'end_reason' => 'completed',
                'direct_p2p_only' => true,
                'client_operation_id' => $this->id("session-operation:{$key}:completed"),
                'last_client_operation_id' => $this->id("session-last-operation:{$key}:completed"),
                'last_operation_by_user_id' => $teacherId,
                'last_operation_type' => 'end',
                'created_at' => $scheduledAt->subHours(6)->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ]);

            $taskId = $this->id("session-task:{$key}:primary");
            $isRecitation = $sessionSpec['type_id'] === 3;
            $fromPage = min(604, (int) $student['page_start'] + (27 + $sessionSpec['offset']) * (int) $student['daily_pages']);
            $toPage = min(604, $fromPage + max(0, (int) $student['daily_pages'] - 1));
            $fromRangeId = $isRecitation
                ? $this->rangeId($rangeIds, 'quarterHizb', 100)
                : $this->rangeId($rangeIds, 'page', $fromPage);
            $toRangeId = $isRecitation
                ? $fromRangeId
                : $this->rangeId($rangeIds, 'page', $toPage);
            $rangeBounds = DB::table('quran_range_units')->where('id', $fromRangeId)->first(['from_page', 'to_page', 'from_ayah_id', 'to_ayah_id']);
            if ($rangeBounds === null) {
                throw new RuntimeException('تعذر تحميل حدود وحدة مهمة الجلسة التجريبية.');
            }
            $fromPage = $rangeBounds->from_page;
            $toPage = $isRecitation ? $rangeBounds->to_page : $toPage;
            $fromAyah = $rangeBounds->from_ayah_id;
            $toAyah = $isRecitation ? $rangeBounds->to_ayah_id : (int) DB::table('quran_range_units')->where('id', $toRangeId)->value('to_ayah_id');
            $this->write('session_tasks', ['id' => $taskId], [
                'id' => $taskId,
                'session_id' => $sessionId,
                'client_operation_id' => $this->id("session-task-operation:{$key}:primary"),
                'tracking_type_id' => $sessionSpec['type_id'],
                'sequence_no' => 1,
                'planned_from_unit_id' => $fromRangeId,
                'planned_to_unit_id' => $toRangeId,
                'start_page' => $fromPage,
                'start_ayah_id' => $fromAyah,
                'end_page' => $toPage,
                'end_ayah_id' => $toAyah,
                'current_page' => $toPage,
                'current_ayah_id' => $toAyah,
                'last_draft_operation_id' => $this->id("session-task-draft:{$key}:primary"),
                'planned_amount' => $key === 'ahmad' ? 2 : 1,
                'actual_amount' => $key === 'ahmad' ? 2 : 1,
                'state' => 'completed',
                'comment' => $key === 'ahmad' ? 'تحسن ملحوظ في الربط بين الآيات مع الحاجة إلى تثبيت موضعين في آخر الوجه.' : 'تلاوة هادئة ومركزة مع ملاحظة محددة في مخرج القاف.',
                'score' => $key === 'ahmad' ? 93 : 89,
                'gap' => $key === 'ahmad' ? 0.05 : 0.12,
                'started_at' => $scheduledAt->toDateTimeString(),
                'completed_at' => $scheduledAt->addMinutes(28)->toDateTimeString(),
                'created_at' => $scheduledAt->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ]);

            $trackingDetailId = $this->id("session-tracking-detail:{$key}:primary");
            $this->write('tracking_details', ['uuid' => $trackingDetailId], [
                'uuid' => $trackingDetailId,
                'tracking_id' => $this->id("daily-tracking:{$key}:{$sessionSpec['offset']}"),
                'session_task_id' => $taskId,
                'tracking_type_id' => $sessionSpec['type_id'],
                'from_unit_id' => $fromRangeId,
                'to_unit_id' => $toRangeId,
                'actual_amount' => $key === 'ahmad' ? 2 : 1,
                'state' => 'completed',
                'comment' => 'تفصيل مرتبط بجلسة المتابعة المباشرة.',
                'score' => $key === 'ahmad' ? 93 : 89,
                'gap' => $key === 'ahmad' ? 0.05 : 0.12,
                'created_at' => $scheduledAt->addMinutes(30)->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ]);

            $this->seedSessionAnnotations($scheduledAt, $editionId, $teacherId, $student['id'], $sessionId, $taskId, $trackingDetailId, $fromAyah, $key, $sessionSpec['summary']);
        }

        $upcomingAt = $this->at(1, 18, 30);
        $this->write('live_sessions', ['id' => $this->id('session:yusuf:upcoming')], [
            'id' => $this->id('session:yusuf:upcoming'),
            'halaqa_id' => $halaqaId,
            'teacher_id' => $teacherId,
            'student_id' => $students['yusuf']['id'],
            'follow_up_item_id' => $this->id('follow-up:yusuf:memorization:1'),
            'task_type_id' => 1,
            'state' => 'requested',
            'scheduled_at' => $upcomingAt->toDateTimeString(),
            'requested_at' => $now->toDateTimeString(),
            'accepted_at' => null,
            'connected_at' => null,
            'ended_at' => null,
            'end_reason' => null,
            'direct_p2p_only' => true,
            'client_operation_id' => $this->id('session-operation:yusuf:upcoming'),
            'last_client_operation_id' => $this->id('session-last-operation:yusuf:upcoming'),
            'last_operation_by_user_id' => $teacherId,
            'last_operation_type' => 'request',
            'created_at' => $now->toDateTimeString(),
            'updated_at' => $now->toDateTimeString(),
        ]);
    }

    private function seedSessionAnnotations(CarbonImmutable $sessionAt, int $editionId, string $teacherId, string $studentId, string $sessionId, string $taskId, string $trackingDetailId, int $ayahId, string $key, string $summary): void
    {
        $mistakeId = $this->id("mistake:{$key}:primary");
        $this->write('mistakes', ['id' => $mistakeId], [
            'id' => $mistakeId,
            'tracking_detail_id' => $trackingDetailId,
            'ayah_id' => $ayahId,
            'edition_id' => $editionId,
            'word_index' => 1,
            'mistake_type_id' => $key === 'ahmad' ? 2 : 4,
            'source_role' => 'teacher',
            'note' => $key === 'ahmad' ? 'موضع يحتاج إلى إعادة تثبيت في بداية التسميع القادم.' : 'تنبيه تربوي على مخرج القاف دون التأثير على إيقاع التلاوة.',
            'created_by_user_id' => $teacherId,
            'client_operation_id' => $this->id("mistake-operation:{$key}:primary"),
            'created_at' => $sessionAt->addMinutes(30)->toDateTimeString(),
            'updated_at' => $sessionAt->addMinutes(30)->toDateTimeString(),
            'deleted_at' => null,
        ]);

        $this->write('task_notes', ['id' => $this->id("task-note:{$key}:teacher")], [
            'id' => $this->id("task-note:{$key}:teacher"),
            'session_task_id' => $taskId,
            'author_id' => $teacherId,
            'client_operation_id' => $this->id("task-note-operation:{$key}:teacher"),
            'note' => 'ينصح بقراءة المقطع مرتين غيباً ثم عرضه في بداية الجلسة القادمة قبل الانتقال إلى الجديد.',
            'ayah_id' => $ayahId,
            'edition_id' => $editionId,
            'word_index' => 1,
            'created_at' => $sessionAt->addMinutes(30)->toDateTimeString(),
            'updated_at' => $sessionAt->addMinutes(30)->toDateTimeString(),
            'deleted_at' => null,
        ]);

        foreach ([['teacher', $teacherId, 92, 'التزام جيد بالخطة واستجابة دقيقة للتوجيه.'], ['student', $studentId, 90, 'الخطة واضحة ومقدار المهمة مناسب لوقت المراجعة.']] as [$role, $evaluatorId, $score, $comment]) {
            $evaluationId = $this->id("evaluation:{$key}:{$role}");
            $this->write('task_evaluations', ['id' => $evaluationId], [
                'id' => $evaluationId,
                'session_task_id' => $taskId,
                'evaluator_id' => $evaluatorId,
                'evaluator_role' => $role,
                'score' => $score,
                'comment' => $comment,
                'created_at' => $sessionAt->addMinutes(30)->toDateTimeString(),
                'updated_at' => $sessionAt->addMinutes(31)->toDateTimeString(),
            ]);
        }

        $this->write('session_reports', ['id' => $this->id("session-report:{$key}")], [
            'id' => $this->id("session-report:{$key}"),
            'session_id' => $sessionId,
            'state' => 'completed',
            'summary' => $summary,
            'duration_seconds' => 1920,
            'total_tasks' => 1,
            'total_mistakes' => 1,
            'mistake_counts' => $this->json([['mistake_type' => $key === 'ahmad' ? 'memory' : 'pronunciation', 'count' => 1]]),
            'version' => 2,
            'teacher_approved_by' => $teacherId,
            'teacher_approval_note' => 'تم اعتماد التقرير مع توجيه عملي واضح للجلسة التالية.',
            'teacher_approved_at' => $sessionAt->addMinutes(30)->toDateTimeString(),
            'student_acknowledged_at' => $sessionAt->addHours(8)->toDateTimeString(),
            'student_acknowledgment_note' => 'اطلعت على التقرير وسأطبق خطة المراجعة المقترحة.',
            'reopened_by' => null,
            'reopened_at' => null,
            'reopen_reason' => null,
            'last_client_operation_id' => $this->id("session-report-operation:{$key}"),
            'last_operation_by_user_id' => $teacherId,
            'last_operation_type' => 'student_acknowledge',
            'created_at' => $sessionAt->addMinutes(30)->toDateTimeString(),
            'updated_at' => $sessionAt->addHours(10)->toDateTimeString(),
        ]);
    }

    private function seedApplicantRequest(CarbonImmutable $now, string $teacherId, string $halaqaId, array $applicant): void
    {
        $requestId = $this->id('registration-request:ibrahim');
        $submittedAt = $now->subDays(2)->setTime(14, 30);
        $this->write('registration_requests', ['id' => $requestId], [
            'id' => $requestId,
            'student_id' => $applicant['id'],
            'teacher_id' => $teacherId,
            'teacher_code_snapshot' => 'ITQAN-AR-01',
            'requested_halaqa_id' => $halaqaId,
            'routing_mode' => 'specific_teacher',
            'state' => 'pending',
            'public_message' => 'أرغب في الانضمام إلى حلقة مسائية منتظمة بخطة حفظ ومراجعة متدرجة.',
            'decision_note' => null,
            'decided_by_teacher_id' => null,
            'submitted_at' => $submittedAt->toDateTimeString(),
            'decided_at' => null,
            'accepted_at' => null,
            'withdrawn_at' => null,
            'created_at' => $submittedAt->toDateTimeString(),
            'updated_at' => $now->toDateTimeString(),
        ]);
        $this->write('registration_request_profiles', ['registration_request_id' => $requestId], [
            'registration_request_id' => $requestId,
            'gender' => 'male',
            'birth_date' => $applicant['birth_date'],
            'country' => 'المملكة العربية السعودية',
            'city' => 'الرياض',
            'residence' => 'الرياض',
            'phone' => $applicant['phone'],
            'phone_zone' => '+966',
            'whatsapp_phone' => $applicant['phone'],
            'whatsapp_zone' => '+966',
            'memorization_level' => 'مبتدئ — حفظ جزء عم',
            'review_level' => 'مراجعة قصيرة يومية',
            'memorized_juz_count' => 1.0,
            'memorized_surah_ids' => $this->json([78, 79, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 114]),
            'last_completed_unit' => $this->json(['task_type' => 'memorization', 'unit' => 'page', 'amount' => 1, 'note' => 'آخر صفحة من جزء عم.']),
            'previous_memorization_notes' => 'انقطع سابقاً لفترة قصيرة ويريد العودة بخطة تدريجية منتظمة.',
            'stop_reasons' => 'تعارض الدراسة مع وقت الحلقة السابقة.',
            'profile_bio' => 'متقدم جاد يبحث عن متابعة تربوية منتظمة.',
            'created_at' => $submittedAt->toDateTimeString(),
            'updated_at' => $now->toDateTimeString(),
        ]);
        $this->write('registration_request_availability', ['registration_request_id' => $requestId], [
            'registration_request_id' => $requestId,
            'timezone' => self::TIMEZONE,
            'preferred_session_duration_minutes' => 30,
            'created_at' => $submittedAt->toDateTimeString(),
            'updated_at' => $now->toDateTimeString(),
        ]);
        foreach ([[0, '18:30:00', '19:30:00'], [2, '18:30:00', '19:30:00']] as [$day, $from, $to]) {
            $this->write('registration_request_availability_slots', [
                'registration_request_id' => $requestId,
                'day_of_week' => $day,
                'available_from' => $from,
                'available_to' => $to,
            ], [
                'registration_request_id' => $requestId,
                'day_of_week' => $day,
                'available_from' => $from,
                'available_to' => $to,
                'is_preferred' => true,
                'created_at' => $submittedAt->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ]);
        }
    }

    /** @param array<string, array<string, int|float|string>> $students
     * @param array<string, array{id: string, details: array<string, array{id: string, type_id: int, unit_id: int, amount: int|float, notes: string}>}> $plans
     */
    private function seedNotifications(CarbonImmutable $now, string $teacherId, array $students, array $plans): void
    {
        $notifications = [
            ['teacher:pending-registration', $teacherId, 'registration_request', 'طلب انضمام جديد', 'ورد طلب إبراهيم ناصر الدوسري للانضمام إلى حلقة الإتقان اليومية.', ['registration_request_id' => $this->id('registration-request:ibrahim')], null],
            ['teacher:report-ready', $teacherId, 'report_ready', 'تقرير جلسة معتمد', 'تم اعتماد تقرير جلسة أحمد ياسر الغامدي ويمكن مراجعة ملاحظاته التربوية.', ['session_id' => $this->id('session:ahmad:completed')], $now->subDay()],
            ['ahmad:follow-up-due', $students['ahmad']['id'], 'follow_up_due', 'مهمة اليوم جاهزة', 'حفظ وجهين ومراجعة جزء وفق خطة الإتقان اليومية.', ['plan_id' => $plans['ahmad']['id']], null],
            ['yusuf:session-scheduled', $students['yusuf']['id'], 'session_scheduled', 'جلسة الغد المجدولة', 'جلسة حفظ قصيرة غداً في 18:30 بتوقيت الرياض.', ['session_id' => $this->id('session:yusuf:upcoming')], null],
            ['khalid:report-ready', $students['khalid']['id'], 'report_ready', 'تقرير التلاوة متاح', 'اطلع على تنبيه المعلم المتعلق بمخرج القاف وخطة التثبيت.', ['session_id' => $this->id('session:khalid:completed')], $now->subDays(2)],
        ];

        foreach ($notifications as [$key, $userId, $type, $title, $body, $payload, $readAt]) {
            $id = $this->id("notification:{$key}");
            $this->write('notifications', ['id' => $id], [
                'id' => $id,
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'payload' => $this->json($payload),
                'dedupe_key' => "demo-halaqa:{$key}",
                'read_at' => $readAt?->toDateTimeString(),
                'created_at' => $now->subHours(8)->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ]);
        }
    }

    /** @return array<string, int> */
    private function editionId(): int
    {
        $editionId = DB::table('quran_editions')->where('code', 'hafs-uthmani')->value('id');
        if ($editionId === null) {
            throw new RuntimeException('إصدار المصحف حفص عن عاصم غير موجود قبل إعداد بيانات المحاكاة.');
        }

        return (int) $editionId;
    }

    private function rangeIds(int $editionId): array
    {
        $rangeIds = DB::table('quran_range_units')
            ->join('tracking_units', 'tracking_units.id', '=', 'quran_range_units.unit_type_id')
            ->where('quran_range_units.edition_id', $editionId)
            ->select(['quran_range_units.id', 'quran_range_units.unit_index', 'tracking_units.code'])
            ->get()
            ->mapWithKeys(fn (object $range): array => ["{$range->code}:{$range->unit_index}" => $range->id])
            ->all();

        if (count($rangeIds) !== 1054) {
            throw new RuntimeException('لم تكتمل نطاقات وحدات المصحف المرجعية قبل إعداد بيانات المحاكاة.');
        }

        return $rangeIds;
    }

    /** @param array<string, int> $rangeIds */
    private function rangeId(array $rangeIds, string $unitCode, int $unitIndex): int
    {
        $key = "{$unitCode}:{$unitIndex}";
        if (! isset($rangeIds[$key])) {
            throw new RuntimeException("نطاق وحدة المصحف غير موجود: {$key}");
        }

        return $rangeIds[$key];
    }

    private function attendanceFor(string $studentKey, int $dayOffset): string
    {
        $seed = array_search($studentKey, array_keys($this->students()), true) ?: 0;
        $value = abs($dayOffset) + $seed;
        if ($value % 17 === 0) {
            return 'excused';
        }
        if ($value % 13 === 0) {
            return 'absent';
        }
        if ($value % 9 === 0) {
            return 'late';
        }

        return 'present';
    }

    private function attendanceNote(string $attendance): string
    {
        return match ($attendance) {
            'present' => 'حضور منتظم ومتابعة مكتملة حسب الخطة.',
            'late' => 'حضر الطالب متأخراً قليلاً وأكمل القدر الرئيس من المتابعة.',
            'absent' => 'غياب مسجل مع إبقاء المهمة للمراجعة في الموعد التالي.',
            'excused' => 'عذر مسبق موثق، ولا تحتسب المهمة ضمن الإنجاز اليومي.',
            default => 'سجل متابعة تجريبي.',
        };
    }

    private function at(int $dayOffset, int $hour, int $minute = 0): CarbonImmutable
    {
        return CarbonImmutable::now(self::TIMEZONE)
            ->startOfDay()
            ->addDays($dayOffset)
            ->setTime($hour, $minute)
            ->utc();
    }

    /** @param array<string, mixed> $key
     * @param array<string, mixed> $values
     */
    private function write(string $table, array $key, array $values): void
    {
        DB::table($table)->updateOrInsert($key, $values);
    }

    /** @param array<mixed> $value */
    private function json(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    private function id(string $key): string
    {
        $hash = md5("halaqa-demo:{$key}");
        $hash[12] = '4';
        $hash[16] = dechex((hexdec($hash[16]) & 0x3) | 0x8);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            substr($hash, 12, 4),
            substr($hash, 16, 4),
            substr($hash, 20, 12),
        );
    }
}
