# عقد ميزة Laravel: أساس الجلسة المباشرة

## تعريف الميزة

- **الفاعل:** `teacher` ينشئ جلسة لطالب عضو فعال في حلقته.
- **المسار:** `POST /api/v1/sessions` وفق `CreateSessionInput` و`SessionResponse` في OpenAPI.
- **النتيجة:** جلسة رسمية بحالة `requested`، مرتبطة بالحلقة والمعلم والطالب ونوع المهمة، مع `direct_p2p_only=true`.
- **منع التكرار:** تفحص الخدمة وجود جلسة نشطة للطالب داخل Transaction وتعيد تعارضًا منظمًا بدل إنشاء جلسة ثانية.

## الطبقات

```text
CreateSessionRequest -> LiveSessionService -> LiveSession/HalaqaMembership/TrackingType -> SessionResponseResource
```

يقع Request وController وResource وService وModel وMigration في المسارات المعيارية. لا يدخل الصوت أو الفيديو أو SDP أو ICE إلى Laravel أو قاعدة البيانات؛ هذه الشريحة تسجل التحكم فقط.

## الصلاحيات والاختبارات

لا يُسمح إلا للمعلم المصادق بإنشاء الجلسة. تتحقق الخدمة من أن الطالب عضو فعال في الحلقة وأن الحلقة مملوكة للمعلم. تغطي الاختبارات الإنشاء الناجح، `direct_p2p_only`, غياب حقول الوسائط، ومنع الجلسة النشطة الثانية، وretry للجلسة والمهمة. أضيفت `LiveSessionPolicy` و`SessionTransitionService` لمسارات عرض الجلسة وقبولها ورفضها وإلغائها ومغادرتها وإنهائها؛ يقبل الطالب المستهدف ويرفضه، ويلغي المعلم الطلب قبل القبول، وينهي المعلم أو يغادر الطرفان الجلسة من حالات الاتصال المعتمدة فقط. ما زالت إعادة الاتصال والإشارة الداخلية وحالة الاتصال المباشر التفصيلية مؤجلة لشريحة realtime مستقلة.

## امتداد مهام الجلسة

أضيفت مسارات `GET/PATCH /api/v1/sessions/{session}/tasks/{task}` و`GET /api/v1/sessions/{session}/tasks` و`POST .../save-draft` وفق `TaskResponse` و`TaskCollectionResponse`، مع Requests وResource و`SessionTaskPolicy` و`SessionTaskService`. لا تُنشأ المهمة إلا من معلم الجلسة وفي حالة قابلة للإدارة، ويمنع تكرار `sequence_no` و`client_operation_id`. تحفظ الخدمة نطاق البداية والنهاية ومؤشر المصحف الحالي، وتزامن الحالة والكمية مع `TrackingDetail` داخل Transaction؛ يعيد save-draft نفس المورد عند retry للمفتاح نفسه. أضيفت Migration `quran_range_units` قبل FK المهمة لأن عقد MySQL يربط النطاقين المخططين ببيانات المصحف المرجعية. يغطي اختبار الجلسة الإنشاء والقائمة والقراءة وتحديث الطالب للمؤشر وتحديث المعلم للحالة وحفظ المسودة.

## امتداد سجل المتابعة

أضيفت جداول `daily_trackings` و`tracking_details` وModel طبقي لها، مع `GET /api/v1/students/{student}/trackings` وRequest وResource صريحين. عند إنشاء مهمة جلسة تُنشئ الخدمة تفصيل تتبع draft وسجل اليوم بشكل ذري، مع unique على `session_task_id`، ليكون التفصيل الأب الرسمي للأخطاء. يقرأ الطالب سجله، ويقرأه المعلم المرتبط بعضوية فعالة فقط؛ لا يكفي حساب معلم غير مرتبط. ما زالت عمليات إكمال المهمة وتحديث الحضور التفصيلية خارج هذه الشريحة.

## امتداد تخزين الأخطاء والملاحظات والتقييمات

أضيفت جداول `mistakes`, `task_notes`, و`task_evaluations` مع نماذج Soft Delete للأخطاء والملاحظات، وcomposite Quran FKs، وclient_operation_id فريد للأخطاء والملاحظات، وقيد تقييم واحد لكل مقيّم ومهمة. اكتملت مسارات أخطاء المهمة GET/POST/PATCH/DELETE، ومسارات الملاحظات GET/POST/PATCH/DELETE، ومسارات التقييم GET/PUT، مع Requests وResources وPolicies وSessionAnnotationService واختبارات HTTP للملكية والتكرار والنطاقات والحذف المنطقي. لا تزال قائمة أخطاء الطالب التاريخية مرتبطة بمسار القراءة السابق، ولا تُعد تقارير الجلسة أو حالات المهمة المتقدمة مكتملة بهذه الشريحة.

## قائمة أخطاء الطالب

أضيف `GET /api/v1/students/{student}/mistakes` مع `MistakeType` و`MistakeCollectionResource` وتطبيق `StudentLearningPolicy`. يعرض السجلات النشطة فقط ويقصر المعلم على الطالب ذي العضوية الفعالة. وأضيف CRUD الأخطاء المرتبطة بمهمة الجلسة مع `MistakePolicy` وService وidempotency، والتحقق من الإصدار الافتراضي والآية والصفحة، والحذف المنطقي. ما زالت التحديثات الجماعية والتقرير النهائي خارج هذه الشريحة.
