# مصفوفة مواءمة قاعدة البيانات وOpenAPI
## Quran Halaqa Live — الإصدار 1.0

تربط هذه المصفوفة كل مجموعة بيانات مخزنة بعقد REST والطبقة المسؤولة عنها. الحقول المركبة أو المشتقة لا تعني تكرار التخزين؛ يجب أن تحدد Service أو Query مصدرها بوضوح.

## الحسابات والملفات

| البيانات | جداول التخزين | مخططات OpenAPI | العمليات الرئيسية | Resource/Service |
|---|---|---|---|---|
| حساب الطالب | `users`, `student_profiles` | `StudentRegistrationInput`, `StudentProfile`, `StudentProfileResponse` | `POST /auth/register/student`, `GET/PATCH /me/student-profile` | `RegisterStudentService`, `StudentProfileResource` |
| تسجيل المعلم | `users`, `teacher_profiles` | `TeacherRegistrationInput`, `TeacherProfile`, `TeacherProfileResponse` | `POST /auth/register/teacher`, `GET/PATCH /me/teacher-profile` | `RegisterTeacherService`, `TeacherProfileResource` |
| وثائق المعلم | `teacher_documents` | `TeacherDocumentInput`, `TeacherDocumentUploadInput`, `TeacherDocument` | `POST/PATCH /me/teacher-documents`, `DELETE /me/teacher-documents/{documentId}` | `ManageTeacherDocumentService`, `TeacherDocumentResource` |
| المصادقة | `personal_access_tokens`, `password_reset_tokens` | `LoginInput`, `AuthResponse`, `ForgotPasswordInput`, `ResetPasswordInput` | `/auth/login`, `/auth/logout`, `/auth/password/*` | `LoginService`, `PasswordService` |

## الحلقات والتسجيل

| البيانات | جداول التخزين | مخططات OpenAPI | العمليات الرئيسية | Policy/Service |
|---|---|---|---|---|
| الحلقة | `halaqas` | `CreateHalaqaInput`, `UpdateHalaqaInput`, `Halaqa` | `/halaqas` CRUD | `HalaqaPolicy`, `CreateHalaqaService`, `UpdateHalaqaService` |
| العضوية | `halaqa_memberships` | `Membership`, `AssignStudentInput` | إسناد وإزالة الطالب | `HalaqaMembershipPolicy`, `AssignStudentService` |
| طلب التسجيل | `registration_requests`, `registration_request_profiles` | `CreateRegistrationInput`, `RegistrationRequest`, `RegistrationResponse` | `POST /registration-requests`, صندوق الطلبات، القبول والرفض | `SubmitRegistrationService`, `AcceptRegistrationService` |
| Snapshot التسجيل | `registration_request_profiles`, `registration_request_availability`, `registration_request_availability_slots` | `StudentApplicationProfile`, `AttendancePreferences` | ضمن إنشاء الطلب ولا يعرض كاملًا قبل القبول | `StoreRegistrationSnapshotService`, `ApplicantPublicSummaryResource` |

## الخطة والجدولة

| البيانات | جداول التخزين | مخططات OpenAPI | العمليات الرئيسية | Query/Service |
|---|---|---|---|---|
| ملف التوافر الحالي | `student_availability_profiles`, `student_availability_slots` | `AttendancePreferences` | `GET/PUT /students/{studentId}/availability` | `UpdateStudentAvailabilityService` |
| خطة الطالب | `follow_up_plans`, `follow_up_plan_details` | `FollowUpPlanInput`, `FollowUpPlan`, `PlanDetailInput`, `PlanDetail` | `/students/{studentId}/follow-up-plan`؛ يعرض `timezone`, `version`, وحالة الاعتماد | `CreateFollowUpPlanService`, `UpdateFollowUpPlanService` |
| عنصر المتابعة | `follow_up_items` | `FollowUpItem`, `FollowUpItemResponse` | قائمة اليوم، إكمال، تجاوز، إعادة جدولة؛ يعرض `plan_id` و`plan_detail_id` و`rescheduled_from_id` صراحةً | `FollowUpQueueQuery`, `CompleteFollowUpItemService`, `RescheduleFollowUpService` |
| التنبيه | `notifications` | `Notification` | `/notifications`, القراءة | `NotificationResource`, `MarkNotificationReadService` |
| التنفيذ المؤجل | `jobs`, `failed_jobs` | لا يعرض مباشرة | توليد الجدولة وإرسال التنبيهات وإعادة المحاولة | Laravel Job + Service |

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
| الخطأ | `mistakes` | `CreateMistakeInput`, `UpdateMistakeInput`, `Mistake` | إنشاء وتعديل وحذف منطقي وعرض فوري، مع `tracking_detail_id` المشتق من مسار المهمة، و`edition_id` و`ayah_id` و`word_index` و`mistake_type_id`؛ تمنع Service التكرار النشط بواسطة المفتاح المركب. |

## الجلسات والتقارير

| البيانات | جداول التخزين | مخططات OpenAPI | العمليات الرئيسية | Service/Policy |
|---|---|---|---|---|
| الجلسة | `live_sessions` | `CreateSessionInput`, `Session`, `SessionState` | إنشاء وقبول ورفض ومغادرة وإنهاء وإعادة اتصال، مع فرض `direct_p2p_only = TRUE`. | `LiveSessionPolicy`, `CreateLiveSessionService`, `EndLiveSessionService` |
| حالة المصحف الرسمية للجلسة | `session_mushaf_states` | `MushafState`, `UpdateMushafStateInput`, `MushafStateResponse` | استعادة وحفظ الإصدار والصفحة والآية ونطاق التلاوة؛ المؤشر اللحظي لا يصبح رسميًا إلا عبر هذا المسار | `SessionMushafStatePolicy`, `SaveSessionMushafStateService` |
| مهمة الجلسة | `session_tasks` | `SessionTask`, `CreateTaskInput`, `UpdateTaskInput` | إنشاء وتحديث وإكمال المهمة؛ يعرض `planned_from_unit_id` و`planned_to_unit_id`، ويعرض `range` مشتقًا من `quran_range_units` عند طلب الإسقاط التفصيلي | `SessionTaskService` |
| الملاحظة | `task_notes` | `CreateNoteInput`, `Note` | إضافة وتعديل وحذف الملاحظة | `TaskNotePolicy`, `TaskNoteService` |
| تقييم المعلم/الطالب | `task_evaluations` | `UpsertEvaluationInput`, `Evaluation` | حفظ تقييم كل طرف مرة واحدة | `TaskEvaluationPolicy`, `SaveEvaluationService` |
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
| فشل P2P | تخزن حالة `direct_connection_unavailable` في `live_sessions` فقط، ولا ينشأ مسار Relay أو Media Server. |

## قواعد المطابقة

كل حقل مطلوب في OpenAPI يجب أن يصل إلى Model أو DTO أو Service أو يكون مشتقًا موثقًا من أكثر من جدول. لا يعاد Eloquent Model خامًا. لا تعرض `password`, `token`, `request_hash`, `response_body`, مسارات الوثائق الخاصة، أو أي بيانات طلب حساسة في Resource عام. ويجب أن يطابق كل enum في OpenAPI كودًا ثابتًا في `app/Enums` وقيمة مسموحة في عقد MySQL.
