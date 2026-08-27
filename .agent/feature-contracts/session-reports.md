# عقد ميزة تقارير الجلسات

## تعريف الميزة

- **اسم الميزة:** تقرير الجلسة الرسمية والاعتماد.
- **الفاعل الأساسي:** `teacher` أو `student` فقط.
- **النتيجة المطلوبة:** إنشاء تقرير رسمي عند إنهاء الجلسة، تجميع مدة الجلسة والمهام والأخطاء، ثم تمكين المعلم من تعديل الملخص واعتماد التقرير، والطالب من التعليق أو الإقرار، والمعلم من إعادة فتح التقرير المكتمل.
- **نطاق API:** `GET/PATCH /sessions/{sessionId}/report`، و`POST /sessions/{sessionId}/report/teacher-approval`، و`POST /sessions/{sessionId}/report/student-acknowledgment`، و`POST /sessions/{sessionId}/report/reopen`، و`GET /students/{studentId}/reports`.
- **مخططات الطلب:** `UpdateReportInput`, `ApprovalInput`, `StudentAcknowledgmentInput`, `ReopenReportInput`، مع فلاتر قائمة التقارير المعرفة في OpenAPI.
- **مفتاح الاستجابة الجذري:** `report` للتقرير المفرد و`reports` للقائمة، ويُمنع `data`.
- **هل العملية متزامنة أم مؤجلة؟** متزامنة داخل معاملات قاعدة البيانات.
- **هل البيانات رسمية محفوظة أم حدث لحظي مؤقت؟** التقرير والإحصاءات والاعتمادات بيانات رسمية محفوظة؛ لا يحفظ التقرير وسائط أو SDP أو ICE.
- **آلية منع التكرار:** `client_operation_id` لعمليات اعتماد المعلم وإقرار الطالب وإعادة فتح التقرير، مع حفظ آخر صاحب ونوع عملية وفهرس UUID فريد.

## المدخلات

| الاسم | النوع | مطلوب؟ | قاعدة التحقق |
|---|---|---:|---|
| `summary` | string أو null | اختياري | حد أقصى 4000 حرف، لتعديل التقرير قبل اكتماله. |
| `note` في الاعتماد | string أو null | اختياري | حد أقصى 2000 حرف. |
| `action` | enum string | نعم | `acknowledge` أو `comment`. |
| `note` في إقرار الطالب | string أو null | مشروط | حد أقصى 2000 حرف، ومطلوب عند `comment`. |
| `reason` في إعادة الفتح | string | نعم | من 1 إلى 1000 حرف. |
| `client_operation_id` | UUID | نعم للعمليات القابلة للإعادة | لا يُعاد استخدامه بين موارد أو مستخدمين أو أنواع عمليات مختلفة. |
| `task_type` | enum string | اختياري للقائمة | `memorization`, `review`, `recitation`. |
| `from`, `to` | date | اختياري للقائمة | `to` لا يسبق `from`. |
| `page`, `per_page` | integer | اختياري | الصفحة تبدأ من 1، و`per_page` من 1 إلى 100. |

## المخرجات

| العنصر | Resource | الحقول المسموح بها | المصدر أو الاشتقاق |
|---|---|---|---|
| التقرير | `SessionReportResource` داخل `ReportResponseResource` | `id`, `session_id`, `state`, `summary`, `duration_seconds`, `total_tasks`, `total_mistakes`, `mistake_counts`, `version`, `tasks`, `teacher_approval`, `student_acknowledgment`, `reopened_by`, `reopened_at`, `reopen_reason`, `created_at`, `updated_at` | التقرير من `session_reports`؛ المدة من `connected_at` و`ended_at`؛ الإحصاءات من المهام والأخطاء الفعالة؛ التقييمات وعدد الأخطاء من علاقات المهام. |
| مهمة التقرير | `SessionReportTaskResource` | حقول `SessionTask` المعرفة في OpenAPI | من `session_tasks` مع نوع التتبع والتفصيل والتقييمات والأخطاء. |
| قائمة التقارير | `ReportCollectionResource` | `reports` و`meta` | استعلام مقيد بالطالب أو معلمه المرتبط، مع فلاتر وترقيم الصفحات. |

لا تُخرج Laravel Resources حقول idempotency الداخلية أو أي بيانات وسائط. لا يستخدم أي إخراج غلافًا عامًا باسم `data`.

## الصلاحيات

- المستخدم المسموح له: `teacher` أو `student` فقط.
- علاقة المستخدم بالمورد: عرض التقرير لطرفي الجلسة؛ تعديل التقرير واعتماده وإعادة فتحه للمعلم صاحب الجلسة؛ إقرار التقرير للطالب صاحب الجلسة؛ قائمة الطالب لنفسه أو لمعلم مرتبط بعضوية نشطة.
- الإسقاط العام والحقول الحساسة: لا توجد بيانات شخصية إضافية في التقرير، وتبقى حقول العملية الداخلية خارج Resource.
- Policy/Gate المطلوب: `SessionReportPolicy`، مع `StudentLearningPolicy` لقائمة تقارير الطالب.
- يتغير الوصول إلى سجل الطالب بحسب وجود علاقة تعليمية نشطة؛ لا يسمح لمعلم غير مرتبط برؤية التقرير أو القائمة.

## توزيع الملفات

```text
app/Http/Controllers/Api/V1/Reports/GetSessionReportController.php
app/Http/Controllers/Api/V1/Reports/UpdateSessionReportController.php
app/Http/Controllers/Api/V1/Reports/SessionReportActionController.php
app/Http/Controllers/Api/V1/Reports/ListStudentReportsController.php
app/Http/Requests/Api/V1/Reports/UpdateSessionReportRequest.php
app/Http/Requests/Api/V1/Reports/ApprovalReportRequest.php
app/Http/Requests/Api/V1/Reports/StudentAcknowledgmentReportRequest.php
app/Http/Requests/Api/V1/Reports/ReopenSessionReportRequest.php
app/Http/Requests/Api/V1/Reports/ListStudentReportsRequest.php
app/Http/Resources/Api/V1/Reports/SessionReportResource.php
app/Http/Resources/Api/V1/Reports/SessionReportTaskResource.php
app/Http/Resources/Api/V1/Reports/ReportResponseResource.php
app/Http/Resources/Api/V1/Reports/ReportCollectionResource.php
app/Services/Reports/SessionReportService.php
app/Policies/SessionReportPolicy.php
database/migrations/2026_08_25_000018_create_session_reports_table.php
```

## مسار التنفيذ

```text
Request -> Policy -> Controller -> SessionReportService -> SessionReport/LiveSession/SessionTask/TrackingDetail/Mistake -> Resource
```

## قواعد القرار

لأن التقرير يجمع عدة Models ويحسب إحصاءات ويطبق انتقالات حالات ومعاملات وidempotency، وضع منطق الأعمال في `SessionReportService` وليس في Controller أو Resource. ينشأ التقرير تلقائيًا عند انتقال الجلسة إلى `ended`. لا توجد معالجة مؤجلة في هذه الشريحة، ولا يتضمن التقرير أي نقل لحظي أو وسائط.

## دورة الحالات

```text
draft -> pending_student_acknowledgment -> completed
completed -> reopened -> pending_student_acknowledgment -> completed
pending_student_acknowledgment -> pending_student_acknowledgment  # تعليق الطالب
```

لا يمكن تعديل التقرير إلا في `draft` أو `reopened`، ولا يعتمد المعلم إلا تقرير جلسة منتهية، ولا يعيد المعلم فتح إلا تقريرًا `completed`.

## الاختبارات المطلوبة

- إنشاء التقرير تلقائيًا عند إنهاء الجلسة وإرجاعه لطرفيها.
- حساب المهام والأخطاء وإحصاء الأنواع من البيانات الفعلية.
- نجاح تعديل الملخص واعتماد المعلم وتعليق الطالب وإقراره وإعادة الفتح.
- رفض انتقالات الحالة غير المسموحة.
- رفض الحقول غير المعرفة بسبب `additionalProperties: false`.
- رفض المستخدم غير المصرح أو المعلم غير المرتبط.
- مورد جلسة أو تقرير غير موجود.
- إعادة محاولة الاعتماد والإقرار وإعادة الفتح بنفس `client_operation_id` دون تكرار.
- منع إعادة استخدام UUID لعملية أو تقرير أو مستخدم مختلف.
- اتساق Resource مع جدول MySQL وقيوده وUnique وFK.
- عدم حفظ أي وسائط أو SDP أو ICE في التقرير أو قاعدة البيانات.
