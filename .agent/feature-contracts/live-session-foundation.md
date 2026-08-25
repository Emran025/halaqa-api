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

لا يُسمح إلا للمعلم المصادق. تتحقق الخدمة من أن الطالب عضو فعال في الحلقة وأن الحلقة مملوكة للمعلم. يغطي `LiveSessionTest` الإنشاء الناجح، `direct_p2p_only`, غياب حقول الوسائط، ومنع الجلسة النشطة الثانية. ستُضاف انتقالات القبول والاتصال والإنهاء والإشارة الداخلية في شرائح لاحقة منفصلة بعد شهادة هذه الأساس.

## امتداد مهام الجلسة

أضيف `POST /api/v1/sessions/{session}/tasks` وفق `CreateTaskInput`، مع Migration وModel وRequest وResource وخدمة داخل `LiveSessionService`. لا تُنشأ المهمة إلا من معلم الجلسة وفي حالة قابلة للإدارة، ويمنع تكرار `sequence_no`. أضيفت Migration `quran_range_units` قبل FK المهمة لأن عقد MySQL يربط النطاقين المخططين ببيانات المصحف المرجعية. يغطي اختبار الجلسة إنشاء المهمة بحالة `draft` ونوع التتبع والتسلسل.

## امتداد سجل المتابعة

أضيفت جداول `daily_trackings` و`tracking_details` وModel طبقي لها، مع `GET /api/v1/students/{student}/trackings` وRequest وResource صريحين. يقرأ الطالب سجله، ويقرأه المعلم المرتبط بعضوية فعالة فقط؛ لا يكفي حساب معلم غير مرتبط. عمليات إنشاء وتعديل تفاصيل الحضور والتفصيل الكامل ستضاف مع مسارات العقد الأخرى في دورة لاحقة قبل اعتبار التتبع مكتملًا.

## امتداد تخزين الأخطاء والملاحظات والتقييمات

أضيفت جداول `mistakes`, `task_notes`, و`task_evaluations` مع نماذج Soft Delete للأخطاء والملاحظات وقيد تقييم واحد لكل مقيّم ومهمة. هذا Commit يثبت طبقة التخزين والعلاقات فقط؛ لا يُعتبر API الأخطاء/الملاحظات/التقييم مكتملًا حتى تُضاف Requests وResources وPolicies وServices ومساراتها واختبارات HTTP الخاصة بها.

## قائمة أخطاء الطالب

أضيف `GET /api/v1/students/{student}/mistakes` مع `MistakeType` و`MistakeCollectionResource` وتطبيق `StudentLearningPolicy`. يعرض السجلات النشطة فقط ويقصر المعلم على الطالب ذي العضوية الفعالة. لم تُدَّعَ اكتمالية CRUD للأخطاء في هذا التغيير؛ عمليات الإنشاء والتعديل والحذف المرتبطة بتفصيل المهمة ستُنفذ مع Policy وService وIdempotency في دورة لاحقة.
