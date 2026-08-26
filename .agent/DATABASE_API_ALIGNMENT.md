# مصفوفة مواءمة قاعدة البيانات وOpenAPI
## Quran Halaqa Live — الإصدار 1.0

تربط هذه المصفوفة كل مجموعة بيانات مخزنة بعقد REST والطبقة المسؤولة عنها. الحقول المركبة أو المشتقة لا تعني تكرار التخزين؛ يجب أن تحدد Service أو Query مصدرها بوضوح.

## الحسابات والملفات

| البيانات | جداول التخزين | مخططات OpenAPI | العمليات الرئيسية | Resource/Service |
|---|---|---|---|---|
| حساب الطالب | `users`, `student_profiles` | `StudentRegistrationInput`, `StudentProfile`, `StudentProfileResponse` | `POST /auth/register/student`, `GET/PATCH /me/student-profile` | `RegisterStudentService`, `StudentProfileResource` |
| تسجيل المعلم | `users`, `teacher_profiles` | `TeacherRegistrationInput`, `TeacherProfile`, `TeacherProfileResponse` | `POST /auth/register/teacher`, `GET/PATCH /me/teacher-profile` | `RegisterTeacherService`, `TeacherProfileResource` |
| وثائق المعلم | `teacher_documents` | `TeacherDocumentInput`, `TeacherDocumentUploadInput`, `TeacherDocument` | `GET/POST /me/teacher-documents`, `DELETE /me/teacher-documents/{documentId}` | `TeacherDocumentService`, `TeacherDocumentResource` |
| المصادقة | `personal_access_tokens`, `password_reset_tokens` | `LoginInput`, `AuthResponse`, `ForgotPasswordInput`, `ResetPasswordInput` | `/auth/login`, `/auth/logout`, `/auth/password/*` | `LoginService`, `PasswordService` |

## الحلقات والتسجيل

| البيانات | جداول التخزين | مخططات OpenAPI | العمليات الرئيسية | Policy/Service |
|---|---|---|---|---|
| الحلقة | `halaqas` | `CreateHalaqaInput`, `UpdateHalaqaInput`, `Halaqa` | `/halaqas` CRUD | `HalaqaPolicy`, `CreateHalaqaService`, `UpdateHalaqaService` |
| العضوية | `halaqa_memberships` | `Membership`, `MembershipCollectionResponse`, `AssignStudentInput` | `GET /halaqas/{halaqaId}/memberships` لقائمة lifecycle المرقمة المملوكة للمعلم، وإسناد وإزالة الطالب؛ البحث في `student.name` فقط، ولا يغير `/students` | `HalaqaPolicy@manageMembers`, `MembershipQueryService`, `MembershipService`, `MembershipResource`, `MembershipCollectionResource` |
| طلب التسجيل | `registration_requests`, `registration_request_profiles` | `CreateRegistrationInput`, `RegistrationRequest`, `RegistrationResponse` | `POST /registration-requests`, `POST/GET /halaqas/{halaqaId}/registration-requests`, `GET /student-applications`، القبول والرفض | `RegistrationService`, `RegistrationQueryService`, `ApplicantPublicSummaryResource` |
| Snapshot التسجيل | `registration_request_profiles`، مع `student_availability_profiles` و`student_availability_slots` للحساب الحالي | `StudentApplicationProfile`, `PreviousMemorization`, `AttendancePreferences` | ضمن إنشاء الطلب ولا يعرض كاملًا قبل القبول | `RegistrationService`, `ApplicantPublicSummaryResource` |

## الخطة والجدولة

| البيانات | جداول التخزين | مخططات OpenAPI | العمليات الرئيسية | Query/Service |
|---|---|---|---|---|
| ملف التوافر الحالي | `student_availability_profiles`, `student_availability_slots` | `AttendancePreferences` | `GET/PUT /students/{studentId}/availability` | `UpdateStudentAvailabilityService` |
| خطة الطالب | `follow_up_plans`, `follow_up_plan_details` | `FollowUpPlanInput`, `FollowUpPlan`, `PlanDetailInput`, `PlanDetail` | `/students/{studentId}/follow-up-plan`؛ يعرض `timezone`, `version`, وحالة الاعتماد | `CreateFollowUpPlanService`, `UpdateFollowUpPlanService` |
| عنصر المتابعة | `follow_up_items` | `FollowUpItem`, `FollowUpItemResponse`, `FollowUpItemCollectionResponse` | `GET /follow-up-items` مع فلاتر `date`, `state`, `task_type`, `student_id` للمعلم، ثم complete/skip/reschedule؛ يعرض `plan_id` و`plan_detail_id` و`rescheduled_from_id` صراحةً، ويستخدم `client_operation_id` مع تحقق صاحب العملية وحالة العنصر | `FollowUpItemService`؛ Query داخلي مقيد بملكية الطالب أو عضوية المعلم النشطة |
| التنبيه | `notifications` | `Notification` | `/notifications`, القراءة | `NotificationResource`, `MarkNotificationReadService` |
| التنفيذ المؤجل | `jobs`, `failed_jobs` | لا يعرض مباشرة | توليد الجدولة وإرسال التنبيهات وإعادة المحاولة | Laravel Job + Service |

| تقرير الجلسة | `session_reports` | `SessionReport`, `ReportResponse`, `ReportCollectionResponse` | إنشاء تلقائي عند إنهاء الجلسة، عرض الطرفين، تعديل الملخص للمعلم، اعتماد المعلم، إقرار/تعليق الطالب، إعادة الفتح، وقائمة تقارير الطالب | `SessionReportService` مع `SessionReportPolicy` وحقول idempotency للعمليات القابلة لإعادة المحاولة |

## المصحف والتتبع

| البيانات | جداول التخزين | مخططات OpenAPI | العمليات الرئيسية |
|---|---|---|---|
| إصدار المصحف | `quran_editions` | لا يعرض كمورد REST في الإصدار الحالي | مرجع داخلي للـSeed/Import واختيار الإصدار النشط |
| السور والصفحات | `quran_surahs`, `quran_pages` | `Surah`, `QuranPage`, `QuranPageResponse`، `SurahCollectionResponse` | قراءة وفهرسة وبحث |
| الآيات والكلمات | `quran_ayahs`, `quran_ayah_words` | `Ayah`, `AyahWord` أو ضمن `QuranPage` | تحديد الآية والكلمة وربط الخطأ |
| حدود الوحدات | `quran_range_units` | `TrackingUnitDetail`, نطاق المهمة | حساب البداية والنهاية |
| سجل اليوم | `daily_trackings` | `Tracking`, `TrackingCollectionResponse` | سجل الحضور والمتابعة |
| تفاصيل المتابعة | `tracking_details` | `TrackingDetail` | كمية الإنجاز والدرجة والفجوة |
| أنواع الخطأ | `mistake_types` | `MistakeType` | `none`, `memory`, `grammar`, `pronunciation`, `timing`، وتستخدم في السجل بواسطة `mistake_type_id`. |
| الخطأ | `mistakes` | `CreateMistakeInput`, `UpdateMistakeInput`, `Mistake` | GET/POST/PATCH/DELETE للمهمة؛ يشتق `tracking_detail_id` من التفصيل الذي تنشئه المهمة، ويتحقق من الإصدار الافتراضي والآية/الصفحة، ويمنع التكرار النشط وretry عبر مفاتيح فريدة. |

## الجلسات والتقارير

| البيانات | جداول التخزين | مخططات OpenAPI | العمليات الرئيسية | Service/Policy |
|---|---|---|---|---|
| الجلسة | `live_sessions` | `CreateSessionInput`, `Session`, `SessionState`, `DirectConnectionUnavailableInput` | إنشاء وقبول ورفض ومغادرة وإنهاء وإعادة اتصال، وتسجيل `direct_connection_unavailable` مع حقول آخر عملية، مع فرض `direct_p2p_only = TRUE`. | `LiveSessionPolicy`, `LiveSessionService`, `SessionTransitionService`, `RealtimeSessionService` |
| رسالة realtime الرسمية | `realtime_outbox_messages` | أحداث server-originated في `REALTIME_CONTRACT.md` | حفظ pending بعد commit، dedupe حسب recipient/نوع الحدث/payload، وتسليمها بعد نجاح الكتابة فقط؛ لا تحفظ وسائط أو SDP/ICE. | `RealtimeOutboxPublisher`, `RealtimeOutboxDispatcher`, `RealtimeServerEventEnvelopeFactory` |
| حالة المصحف الرسمية للجلسة | `session_mushaf_states` | `MushafState`, `UpdateMushafStateInput`, `MushafStateResponse` | استعادة وحفظ الإصدار والصفحة والآية ونطاق التلاوة؛ المؤشر اللحظي لا يصبح رسميًا إلا عبر هذا المسار | `SessionMushafStatePolicy`, `SaveSessionMushafStateService` |
| مهمة الجلسة | `session_tasks` | `SessionTask`, `CreateTaskInput`, `UpdateTaskInput` | إنشاء/تهيئة المهمة مع `client_operation_id` فريد وإنشاء `tracking_details` draft وسجل اليوم ذريًا؛ يعرض `planned_from_unit_id` و`planned_to_unit_id`، ويعرض `range` مشتقًا من `quran_range_units` عند طلب الإسقاط التفصيلي | `LiveSessionService`, `SessionTaskPolicy` |
| الملاحظة | `task_notes` | `CreateNoteInput`, `UpdateNoteInput`, `Note` | GET/POST/PATCH/DELETE للملاحظة مع `client_operation_id` فريد وملكية المؤلف | `SessionTaskPolicy`, `SessionAnnotationService` |
| تقييم المعلم/الطالب | `task_evaluations` | `UpsertEvaluationInput`, `Evaluation` | GET/PUT وتقييم واحد لكل طرف عبر `(session_task_id, evaluator_id)` | `SessionTaskPolicy`, `SessionAnnotationService` |
| تقرير الجلسة | `session_reports` | `SessionReport`, `UpdateReportInput`, `StudentAcknowledgmentInput`, `ReopenReportInput` | تحديث واعتماد المعلم وتأكيد الطالب وإعادة الفتح | `FinalizeSessionReportService`, `AcknowledgeReportService` |
| الإشارة | لا تخزين دائم | `RealtimeSession`, `RealtimeChannelAuthorization*` | تفويض قناة وتمرير offer/answer/host ICE | `WebRtcSignalingService` داخل Laravel |

## التتبع والتكرار

| المجال | الآلية |
|---|---|
| الطلبات القابلة لإعادة المحاولة | `idempotency_keys` بمفتاح فريد لكل مستخدم وطلب. |
| التدقيق | `audit_events` لتسجيل actor والعملية والمورد والوقت دون بيانات وسائط أو SDP/ICE. |
| الحذف المنطقي | الأخطاء والوثائق والحسابات التي تحتاج تاريخًا تستخدم `deleted_at`. |
| الخصوصية | Resource عام للمتقدم، وResource تفصيلي مشروط بـPolicy العلاقة بعد القبول. |
| التزامن | Transactions داخل Services عند قبول التسجيل وإنشاء العضوية ونسخ Snapshot الخطة والتوافر. |
| فشل P2P | تخزن حالة `direct_connection_unavailable` وسببها وحقول retry في `live_sessions`، وينشر الحدث عبر outbox، ولا ينشأ مسار Relay أو Media Server. |

## قواعد المطابقة

كل حقل مطلوب في OpenAPI يجب أن يصل إلى Model أو DTO أو Service أو يكون مشتقًا موثقًا من أكثر من جدول. لا يعاد Eloquent Model خامًا. لا تعرض `password`, `token`, `request_hash`, `response_body`, مسارات الوثائق الخاصة، أو أي بيانات طلب حساسة في Resource عام. ويجب أن يطابق كل enum في OpenAPI كودًا ثابتًا في `app/Enums` وقيمة مسموحة في عقد MySQL.
