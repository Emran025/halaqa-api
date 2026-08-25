# فهرس وظائف وعقود API
## Quran Halaqa Live API — الإصدار 1

هذا الفهرس هو قائمة التحقق الوظيفية الموافقة لملف `openapi.yaml`. يجب ألا تُنفذ وظيفة جديدة خارج هذا الفهرس دون تحديث عقد OpenAPI أولًا.

## قواعد العقد العامة

| القاعدة | العقد المعتمد |
|---|---|
| الإصدار | `/api/v1` |
| المصادقة | `Authorization: Bearer <token>`، ويُصدر الرمز عند الدخول. |
| نوع البيانات | `application/json`، باستثناء مصادقة قناة البث التي تستخدم `application/x-www-form-urlencoded`. |
| استجابة المورد | كائن Laravel Resource داخل المفتاح `data`. |
| القوائم | `data` مع `meta` للتقسيم والعدد. |
| التحقق | `422` مع `message` و`errors` حسب أسماء الحقول. |
| عدم المصادقة | `401`. |
| عدم الصلاحية | `403`، دون كشف وجود مورد محمي عند الحاجة. |
| المورد غير الموجود | `404`. |
| تعارض الحالة أو التكرار | `409`. |
| إنشاء مورد | `201`. |
| نجاح دون محتوى | `204`. |
| عملية مؤجلة | `202`. |
| معرفات الموارد | UUID، ما لم يفرض مصدر المصحف معرفًا مختلفًا. |
| التوقيت | ISO 8601 بصيغة date-time مع منطقة زمنية. |

## آلية التسجيل والخصوصية

يعتمد التسجيل على مرحلتين مترابطتين. ينشئ الطالب حسابه وملفه الأولي، ثم يرسل طلب انضمام يحتوي على البيانات الشخصية وبيانات التواصل ومستوى الحفظ السابق وخطة الحفظ والمراجعة والتلاوة وأوقات الحضور والتكرار. يمكن للطالب إدخال `teacher_code` اختياريًا. إذا أُرسل الكود، يوجّه الطلب إلى المعلم المطابق؛ وإذا تُرك فارغًا، يظهر الطلب في صندوق المتقدمين لدى جميع المعلمين المؤهلين.

قبل القبول، يعيد الخادم `ApplicantPublicSummary` فقط، ولا يعيد البريد أو أرقام الهاتف أو WhatsApp أو تفاصيل الحفظ السابقة أو الخطة أو أوقات الحضور. بعد قبول الطالب وبدء العلاقة التعليمية، يصبح الملف التفصيلي مرئيًا للطالب نفسه ومعلمه المرتبط فقط. لا يظهر ملف الطالب التفصيلي للمعلمين الآخرين، ولا توجد موافقة إدارية على إنشاء حساب المعلم ضمن النطاق الحالي.

| الوظيفة | Endpoint | الدور | النتيجة |
|---|---|---|---|
| قائمة المعلمين العامة | `GET /teachers?code={teacher_code}` | الطالب | بطاقة عامة مع كود المعلم والمؤهل والقدرة، دون بيانات التواصل الخاصة. |
| عرض بطاقة معلم عامة | `GET /teachers/{teacherId}` | الطالب | بيانات عامة قابلة للاختيار قبل التسجيل. |
| عرض صندوق الطلبات العامة | `GET /student-applications` | المعلم | الطلبات الموجهة إليه بالكود والطلبات العامة، بملخص غير حساس قبل القبول. |
| عرض ملف الطالب التفصيلي | `GET /students/{studentId}` | الطالب أو المعلم المرتبط | لا يسمح به لمعلم غير مرتبط بعد القبول. |
| عرض ملف الطالب الحالي | `GET /me/student-profile` | الطالب | الملف الشخصي والخطة وأوقات الحضور. |
| عرض ملف المعلم الحالي | `GET /me/teacher-profile` | المعلم | البيانات الشخصية وبيانات التواصل والمؤهل والخبرة والوثائق. |
| إرفاق وثيقة للمعلم | `POST /me/teacher-documents` | المعلم | رفع مؤهل أو وثيقة مع بياناتها. |
| حذف وثيقة المعلم | `DELETE /me/teacher-documents/{documentId}` | المعلم | حذف وثيقته فقط. |

## وظائف الحساب والوصول

| الوظيفة | Endpoint | الدور | Laravel المتوقع |
|---|---|---|---|
| تسجيل طالب جديد | `POST /auth/register/student` | عام | `RegisterStudentController` + `StoreStudentRegistrationRequest` + `RegisterStudentService` + `UserResource`. |
| تسجيل معلم جديد | `POST /auth/register/teacher` | عام | `RegisterTeacherController` + `TeacherRegistrationRequest` + `RegisterTeacherService` + `TeacherProfileResource`، دون موافقة إدارية ضمن النطاق الحالي. |
| تسجيل الدخول | `POST /auth/login` | عام | `LoginController` + `LoginRequest` + `LoginService` + `UserResource`. |
| تسجيل الخروج | `POST /auth/logout` | المعلم/الطالب | Controller نحيف + خدمة إبطال الرمز عند استخدام Sanctum. |
| طلب إعادة كلمة المرور | `POST /auth/password/forgot` | عام | Request + Service/Password Broker. |
| إعادة كلمة المرور | `POST /auth/password/reset` | عام | Request + Service/Password Broker. |
| تغيير كلمة المرور | `POST /auth/password/change` | المعلم/الطالب | `ChangePasswordController` + Request + Service. |
| عرض الملف الشخصي | `GET /me` | المعلم/الطالب | `UserResource`. |
| تعديل الملف الشخصي | `PATCH /me` | المعلم/الطالب | `UpdateProfileRequest` + `UpdateProfileService` + `UserResource`. |

## وظائف الحلقات والطلاب

| الوظيفة | Endpoint | الدور | Policy |
|---|---|---|---|
| قائمة الحلقات | `GET /halaqas` | المعلم/الطالب | تظهر للطالب الحلقات القابلة للطلب، وللمعلم حلقاته فقط أو النطاق المعتمد. |
| إنشاء حلقة | `POST /halaqas` | المعلم | `HalaqaPolicy@create`. |
| عرض حلقة | `GET /halaqas/{halaqaId}` | المعلم المالك أو الطالب العضو | `HalaqaPolicy@view`. |
| تعديل حلقة | `PATCH /halaqas/{halaqaId}` | المعلم المالك | `HalaqaPolicy@update`. |
| تفعيل حلقة | `POST /halaqas/{halaqaId}/activate` | المعلم المالك | `HalaqaPolicy@activate`. |
| إيقاف حلقة | `POST /halaqas/{halaqaId}/deactivate` | المعلم المالك | `HalaqaPolicy@deactivate`. |
| قائمة طلاب الحلقة | `GET /halaqas/{halaqaId}/students` | المعلم المالك | `HalaqaPolicy@viewStudents`. |
| إسناد طالب مقبول | `POST /halaqas/{halaqaId}/students` | المعلم المالك | `AssignStudentToHalaqaService`. |
| تحديث حالة العضوية | `PATCH /halaqas/{halaqaId}/memberships/{membershipId}` | المعلم المالك | `MembershipPolicy@update`. |
| إزالة الطالب دون حذف التاريخ | `DELETE /halaqas/{halaqaId}/memberships/{membershipId}` | المعلم المالك | `RemoveStudentFromHalaqaService`. |

## وظائف طلبات التسجيل

| الوظيفة | Endpoint | الدور | قاعدة الحالة |
|---|---|---|---|
| عرض طلبات المستخدم | `GET /registration-requests` | المعلم/الطالب | يرشح الخادم النتائج وفق الدور والعلاقة. |
| تقديم طلب عام أو موجه بالكود | `POST /registration-requests` | الطالب | يرسل `teacher_code` اختياريًا؛ عند فراغه يظهر الطلب لجميع المعلمين المؤهلين بملخص عام فقط. |
| تقديم طلب مباشر إلى حلقة | `POST /halaqas/{halaqaId}/registration-requests` | الطالب | مسار توافق اختياري إذا اختار الطالب حلقة محددة، ويطبق نفس إخفاء البيانات قبل القبول. |
| طلبات الحلقة الواردة | `GET /halaqas/{halaqaId}/registration-requests` | المعلم المالك | يعرض الطلبات التابعة للحلقة فقط. |
| عرض تفاصيل الطلب | `GET /registration-requests/{registrationId}` | صاحب الطلب أو معلم الحلقة | `RegistrationPolicy@view`. |
| سحب الطلب | `DELETE /registration-requests/{registrationId}` | الطالب صاحب الطلب | يسمح قبل القرار فقط. |
| قبول الطلب | `POST /registration-requests/{registrationId}/accept` | معلم الحلقة | ينشئ العضوية عبر Service ومعاملة ذرية. |
| رفض الطلب | `POST /registration-requests/{registrationId}/reject` | معلم الحلقة | يحفظ سبب القرار الاختياري ويُشعر الطالب. |
| طلب استكمال البيانات | `POST /registration-requests/{registrationId}/request-completion` | معلم الحلقة | يحفظ الحقول المطلوبة ويرسل إشعارًا. |

## وظائف الخطة والجدولة وقائمة المتابعة

| الوظيفة | Endpoint | الدور | الغرض |
|---|---|---|---|
| عرض خطة الطالب | `GET /students/{studentId}/follow-up-plan` | الطالب أو المعلم المرتبط | خطة الحفظ والمراجعة والتلاوة مع التكرار والتفاصيل. |
| تحديث خطة الطالب | `PUT /students/{studentId}/follow-up-plan` | الطالب قبل اعتماد المعلم أو المعلم المرتبط | تعديل الخطة واعتمادها حسب حالة العلاقة. |
| عرض أوقات الحضور | `GET /students/{studentId}/availability` | الطالب أو المعلم المرتبط | المنطقة الزمنية والفترات الأسبوعية المتاحة. |
| تحديث أوقات الحضور | `PUT /students/{studentId}/availability` | الطالب أو المعلم المرتبط | حفظ الفترات التي يستطيع الطالب الحضور فيها. |
| قائمة المتابعة اليومية | `GET /follow-up-items` | المعلم/الطالب | للمعلم قائمة طلابه، وللطالب قائمته الشخصية، مع حالات due وoverdue وغيرها. |
| تعليم عنصر كمكتمل | `POST /follow-up-items/{followUpItemId}/complete` | المعلم/الطالب | ربط الإنجاز بجلسة أو متابعة اليوم. |
| تجاوز عنصر | `POST /follow-up-items/{followUpItemId}/skip` | المعلم/الطالب | تسجيل سبب عدم التنفيذ. |
| إعادة جدولة عنصر | `POST /follow-up-items/{followUpItemId}/reschedule` | المعلم/الطالب | إنشاء موعد جديد مع المنطقة الزمنية والسبب. |
| سجل الحضور والمتابعة | `GET /students/{studentId}/trackings` | الطالب أو المعلم المرتبط | سجلات يومية تشمل الحضور والملاحظات والتفاصيل والدرجات والفجوات والأخطاء. |

تُنشأ عناصر المتابعة من `FollowUpPlan` بحسب `frequency` و`weekly_slots`، وتُرسل التنبيهات قبل الموعد وبعد التأخر وفق سياسة التنبيه المعتمدة. يجب ألا تكشف قائمة المعلمين أو قائمة المتقدمين بيانات حساسة قبل تحقق حالة القبول والعلاقة التعليمية.

## وظائف المصحف

| الوظيفة | Endpoint | الدور | المصدر |
|---|---|---|---|
| تحميل قائمة السور | `GET /quran/surahs` | المعلم/الطالب | مصدر المصحف المعتمد، للعرض فقط. |
| تحميل صفحة | `GET /quran/pages/{pageNumber}` | المعلم/الطالب | يعيد الصفحة والآيات والكلمات. |
| تحميل آية | `GET /quran/ayahs/{ayahId}` | المعلم/الطالب | يعيد الكلمات مع فهارسها التفاعلية. |
| استعادة حالة المصحف | `GET /sessions/{sessionId}/mushaf-state` | طرف الجلسة | يعيد آخر صفحة وآية ونطاق محفوظ. |
| حفظ حالة المصحف | `PUT /sessions/{sessionId}/mushaf-state` | المعلم/الطالب وفق صلاحية الجلسة | يحفظ الحالة الرسمية أو المسودة. |

تظل مزامنة الصفحة والمؤشر اللحظي عبر WebSocket أو DataChannel، ولا يلزم إرسال كل تغيير عرضي إلى قاعدة البيانات. أما نطاق التلاوة ونهاية الآية فبيانات رسمية تحفظ عبر endpoint أو Service الجلسة.

## وظائف الجلسة المباشرة

| الوظيفة | Endpoint | الدور | Laravel المتوقع |
|---|---|---|---|
| قائمة الجلسات | `GET /sessions` | المعلم/الطالب | Query Service مع filters وpagination. |
| إنشاء طلب جلسة | `POST /sessions` | المعلم | `CreateLiveSessionService` + `SessionResource`. |
| عرض الجلسة | `GET /sessions/{sessionId}` | الطرفان | `LiveSessionPolicy@view`. |
| إلغاء الطلب | `DELETE /sessions/{sessionId}` | المعلم قبل القبول | `CancelLiveSessionService`. |
| قبول الجلسة | `POST /sessions/{sessionId}/accept` | الطالب المستهدف | `AcceptLiveSessionService`. |
| رفض الجلسة | `POST /sessions/{sessionId}/reject` | الطالب المستهدف | `RejectLiveSessionService`. |
| مغادرة الجلسة | `POST /sessions/{sessionId}/leave` | الطرفان | يسجل المغادرة ولا يعتمد التقرير. |
| إنهاء الجلسة | `POST /sessions/{sessionId}/end` | المعلم | ينهي الاتصال المنطقي ويترك التقرير قابلًا للاعتماد. |
| تسجيل إعادة الاتصال | `POST /sessions/{sessionId}/reconnect` | الطرفان | يعيد حالة التفاوض ويعيد إعدادات الاتصال عند الحاجة. |
| إعدادات WebRTC | `GET /sessions/{sessionId}/realtime` | الطرفان | `GetRealtimeSessionConfigService`، ولا يعيد أسرارًا دائمة. |
| مصادقة قناة البث | `POST /broadcasting/auth` | الطرفان | قناة خاصة مع `BroadcastChannelPolicy`. |

## وظائف المهام

| الوظيفة | Endpoint | الدور | قاعدة التنفيذ |
|---|---|---|---|
| قائمة مهام الجلسة | `GET /sessions/{sessionId}/tasks` | الطرفان | `SessionTaskQuery`. |
| إنشاء/تهيئة مهمة | `POST /sessions/{sessionId}/tasks` | المعلم، أو الطالب إذا سمحت الحالة | `CreateSessionTaskService`. |
| عرض المهمة | `GET /sessions/{sessionId}/tasks/{taskId}` | الطرفان | `SessionTaskPolicy@view`. |
| تحديث النطاق والموضع | `PATCH /sessions/{sessionId}/tasks/{taskId}` | المعلم أساسًا، والطالب للموضع المسموح | `UpdateSessionTaskService`. |
| حفظ المسودة | `POST /sessions/{sessionId}/tasks/{taskId}/save-draft` | الطرفان | Idempotency عبر `client_operation_id`. |

## وظائف الأخطاء والمصحف التفاعلي

| الوظيفة | Endpoint | الدور | البيانات الأساسية |
|---|---|---|---|
| قائمة أخطاء المهمة | `GET /sessions/{sessionId}/tasks/{taskId}/mistakes` | الطرفان | الآية، الصفحة، فهرس الكلمة، النوع، المصدر. |
| تسجيل خطأ | `POST /sessions/{sessionId}/tasks/{taskId}/mistakes` | المعلم/الطالب | يمنع التكرار داخل المهمة نفسها. |
| تعديل خطأ | `PATCH /sessions/{sessionId}/tasks/{taskId}/mistakes/{mistakeId}` | صاحب العلامة أو المعلم وفق الحالة | يسمح قبل اعتماد التقرير. |
| حذف خطأ | `DELETE /sessions/{sessionId}/tasks/{taskId}/mistakes/{mistakeId}` | المعلم أو صاحب العلامة وفق الحالة | يغير السجل الرسمي ولا يكتفي بإخفائه من الواجهة. |

تنتقل العلامة فورًا للطرف الآخر عبر WebRTC DataChannel أو WebSocket. لكن عملية الحفظ النهائية تمر عبر `SyncMushafMistakeService` في Laravel. يجب اعتبار `ayah_id + word_index + task_id` مفتاح منع التكرار المنطقي.

## وظائف الملاحظات والتقييم

| الوظيفة | Endpoint | الدور | Resource |
|---|---|---|---|
| عرض ملاحظات المهمة | `GET /sessions/{sessionId}/tasks/{taskId}/notes` | الطرفان | `NoteResource`. |
| إضافة ملاحظة | `POST /sessions/{sessionId}/tasks/{taskId}/notes` | الطرفان | `CreateTaskNoteService`. |
| تعديل ملاحظة | `PATCH /sessions/{sessionId}/tasks/{taskId}/notes/{noteId}` | صاحب الملاحظة أو المعلم وفق الحالة | `UpdateTaskNoteService`. |
| حذف ملاحظة | `DELETE /sessions/{sessionId}/tasks/{taskId}/notes/{noteId}` | صاحب الملاحظة أو المعلم وفق الحالة | يسمح قبل اكتمال التقرير. |
| عرض التقييمات | `GET /sessions/{sessionId}/tasks/{taskId}/evaluation` | الطرفان | يعرض تقييم المعلم والطالب منفصلين. |
| حفظ التقييم | `PUT /sessions/{sessionId}/tasks/{taskId}/evaluation` | كل طرف لتقييمه | `UpsertEvaluationService`. |

## وظائف التقرير والاعتماد

| الوظيفة | Endpoint | الدور | النتيجة |
|---|---|---|---|
| عرض التقرير | `GET /sessions/{sessionId}/report` | الطرفان | تفاصيل المهام والأخطاء والتقييمات والاعتمادات. |
| تعديل التقرير | `PATCH /sessions/{sessionId}/report` | المعلم قبل الاكتمال | `UpdateSessionReportService`. |
| اعتماد المعلم | `POST /sessions/{sessionId}/report/teacher-approval` | المعلم | يجعل التقرير مكتملًا أو بانتظار تأكيد الطالب حسب العقد. |
| تأكيد الطالب أو التعليق | `POST /sessions/{sessionId}/report/student-acknowledgment` | الطالب | لا يحل محل اعتماد المعلم. |
| إعادة فتح التقرير | `POST /sessions/{sessionId}/report/reopen` | المعلم | يحفظ سبب إعادة الفتح ويعيد الحالة إلى `reopened`. |

## وظائف السجل والتقدم

| الوظيفة | Endpoint | الدور | نوع الخدمة |
|---|---|---|---|
| عرض تقدم الطالب | `GET /students/{studentId}/progress` | الطالب لنفسه أو معلم الحلقة | `StudentProgressQuery`. |
| قائمة التقارير | `GET /students/{studentId}/reports` | الطالب لنفسه أو معلم الحلقة | `StudentReportsQuery`. |
| قائمة الأخطاء التاريخية | `GET /students/{studentId}/mistakes` | الطالب لنفسه أو معلم الحلقة | `StudentMistakesQuery`. |

هذه الاستعلامات للقراءة فقط، وتوضع في `app/Queries` لأنها مركبة وتحتاج filters وpagination وربطًا بين الطالب والمهام والأخطاء والتقارير.

## وظائف الإشعارات

| الوظيفة | Endpoint | الدور |
|---|---|---|
| عرض الإشعارات | `GET /notifications` | المعلم/الطالب، كل مستخدم يرى إشعاراته فقط. |
| تعليم إشعار كمقروء | `POST /notifications/{notificationId}/read` | صاحب الإشعار. |
| تعليم الكل كمقروء | `POST /notifications/read-all` | المستخدم الحالي. |

أما إشعارات الجلسة الفورية فتُبث عبر Laravel Broadcasting، وتُحفظ نسخة قابلة للعرض في قائمة الإشعارات عند الحاجة.

## مسار Laravel القياسي لكل Endpoint

```text
Route
  -> Middleware: auth:sanctum
  -> FormRequest: validation + initial authorization
  -> Policy/Gate: ownership and relationship
  -> Thin Controller
  -> Service or Query Service
  -> Model / Transaction / Event / Job
  -> API Resource
  -> JSON response
```

## وظائف خارج العقد

لا توجد في هذا العقد endpoints للمشرف أو مدير النظام أو الموظف الإداري أو إدارة المدارس، ولا توجد endpoints للتصحيح الآلي بالذكاء الاصطناعي أو الدفع أو الشهادات أو أولياء الأمور. كما لا يوجد endpoint لنقل الصوت والفيديو؛ WebRTC يتولى الوسائط، وLaravel يتولى الإشارة والحالة والحفظ.

## قاعدة تغيير العقد

أي تغيير في اسم حقل أو حالة أو مسار أو صلاحية أو كود استجابة يجب أن يبدأ بتعديل `openapi.yaml` وفهرس الوظائف، ثم تحديث Laravel Resources وRequests والاختبارات. لا تُعتبر ميزة مكتملة إذا لم تتطابق هذه الملفات الثلاثة معًا.
