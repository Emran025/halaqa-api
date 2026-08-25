# تقرير المراجعة الشاملة لعقود مشروع حلقات تحفيظ القرآن

## 1. نطاق المراجعة

أُجريت المراجعة على وثائق الـRAG والعقود المرتبطة بالنظام: `SKILL.md`، `PROJECT_ARCHITECTURE_POLICY.md`، `API_FUNCTIONS_CATALOG.md`، `openapi.yaml`، `REALTIME_CONTRACT.md`، `references/realtime-and-webrtc.md`، `database_schema.sql`، `DATABASE_SCHEMA_CONTRACT.md`، `DATABASE_API_ALIGNMENT.md`، `OPENAPI_COMPLETENESS_AUDIT.md`، `references/canonical-tree.md`، `references/placement-rules.md`، `references/validation-checklist.md`، والقالب `templates/feature-contract.md`، إضافة إلى مدققات Python.

شملت المراجعة الاتساق الدلالي والشكلي بين المسارات والمخططات، تطابق الحقول مع MySQL، قيم التعدادات، علاقات الملكية والصلاحيات، إخفاء البيانات قبل القبول وبعده، دورة التسجيل والعضوية، خطط الحفظ والمتابعة، المصحف وإصداره، الجلسات والتقارير، idempotency، وقواعد Laravel-only وWebSocket الداخلي وP2P-only.

## 2. القرارات المعمارية المثبتة

| المجال | القرار الملزم |
|---|---|
| المستخدمون | دورا `teacher` و`student` فقط، بلا مشرف أو مدير أو ولي أمر. |
| Backend | Laravel هو REST API والمصادقة والسياسات والحفظ الرسمي وWebSocket الداخلي والإشارة. |
| WebRTC | الصوت والفيديو DataChannel بين المعلم والطالب مباشرة P2P. |
| ICE | Host candidates فقط؛ لا STUN ولا TURN ولا Relay ولا Proxy ولا Media Server. |
| فشل الاتصال | الحالة الرسمية `direct_connection_unavailable` مع فشل آمن وإعادة محاولة، دون مسار بديل مرحل. |
| المصحف | `edition_id` صريح في موارد المصحف؛ حالة الجلسة الرسمية محفوظة في `session_mushaf_states`. |
| الاستجابات | لا يوجد غلاف عام باسم `data`؛ تستخدم مفاتيح مجال صريحة مثل `user` و`session` و`tasks`. |
| الخصوصية | الطلب العام يعرض ملخصًا غير حساس؛ بعد القبول والعلاقة الفعالة يظهر الملف التفصيلي للطرف المخول فقط. |
| بنية Laravel | Request للتحقق، Controller نحيف، Service للمنطق المركب، Query للقراءات المركبة، Resource للتحويل، Policy للصلاحيات، Job للتأجيل. |

## 3. التعارضات التي اكتُشفت وأُصلحت

| المجال | المشكلة المكتشفة | الإصلاح المطبق |
|---|---|---|
| الحلقة | كانت خصائص `gender` و`country` و`residence` و`timezone` ناقصة أو غير موحدة بين OpenAPI وSQL. | أضيفت إلى مخططات الحلقة والملخص العام، وأضيف `description` إلى SQL وقاموس البيانات. |
| الحساب والعضوية | حالة الحساب في OpenAPI احتوت `pending`، والعضوية احتوت `suspended`، بينما SQL يعتمد حالات مختلفة. | أضيفت مخططات `UserStatus` و`HalaqaStatus` و`MembershipStatus` وربطت بها الخصائص؛ أضيفت `withdrawn` لدورة التسجيل. |
| المصحف | كانت بعض معرفات السور والآيات نصية، ولم يكن `edition_id` حاضرًا في موارد الصفحة والسورة والآية وحالة المصحف. | أصبحت المعرفات رقمية بحدود صريحة، وأصبح `edition_id` مطلوبًا في الموارد والسياقات الرسمية، وأضيف إلى معاملات القراءة. |
| حالة المصحف | لم يكن هناك تخزين رسمي مستقل لحالة المصحف داخل الجلسة. | أضيف جدول `session_mushaf_states` بعلاقة واحد لواحد مع الجلسة، وإصدار وتزامن ونطاق وقيود FK. |
| المهام | كانت استجابة المهمة ناقصة في معرفات الوحدات المخططة والمبالغ والتواريخ والتعليق والدرجة والفجوة. | وسّع `SessionTask` و`CreateTaskInput` و`UpdateTaskInput` لتغطية الأعمدة الرسمية أو توثيق اشتقاقها. |
| عناصر المتابعة | كان OpenAPI يستخدم `source_plan_id` ولا يعرض `plan_id` و`plan_detail_id` و`rescheduled_from_id`. | تم توحيد `FollowUpItem` مع جدول `follow_up_items` وأصبح يعرض المفاتيح والحالات والطوابع اللازمة. |
| خطط المتابعة | لم تكن استجابة الخطة تعرض `timezone` و`version` وبيانات الاعتماد، كما كان مدخل التفاصيل يستخدم مخطط الاستجابة. | فصل `PlanDetailInput` عن `PlanDetail`، وأضيفت حقول الخطة الرسمية والإصدار والاعتماد. |
| التسجيل | بعض عمليات الإنشاء القابلة لإعادة المحاولة لم تكن تحمل معرف عملية. | أضيف `client_operation_id` إلى تسجيل الحساب، وطلب التسجيل، وإنشاء الجلسة، وإنشاء المهمة، وحفظ الخطأ والملاحظة وحالة المصحف واعتماد التقرير وعمليات المتابعة. |
| التقرير | كان تمثيل إحصاء الأخطاء قابلًا لأن يُفهم كخريطة ديناميكية. | أضيف `MistakeCount` كمصفوفة عناصر صريحة تحتوي `mistake_type` و`count`. |
| WebSocket | كان مثال الغلاف يستخدم placeholder نصيًا عامًا، وكانت قاعدة الحفظ لا تذكر جدول حالة المصحف. | أصبح المثال JSON فعليًا، وأضيف جدول حمولة لكل نوع، وأضيفت حالة المصحف إلى قواعد الحفظ. |
| الأخطاء | القيد الفريد مع `deleted_at` كان لا يمنع فعليًا تكرار الأخطاء النشطة في MySQL بسبب NULL. | أضيف generated column باسم `active_mistake_key` وفهرس فريد يمنع تكرار السجل النشط ويسمح بتاريخ الحذف المنطقي. |
| RAG | القواعد لم تكن تذكر صراحة `edition_id` أو حالة المصحف الرسمية أو مدقق الاتساق العميق. | حدث `SKILL.md` والقالب وقائمة التحقق لإلزام هذه القواعد. |

## 4. الحالة الحالية للعقود

| العقد | الحالة |
|---|---|
| OpenAPI | صالح بصيغة 3.1.1، ويحتوي 66 مسارًا و90 عملية و142 مخططًا. |
| صراحة JSON | لا توجد خصائص `data` في مخططات الطلبات والاستجابات. |
| إغلاق الأجسام | 122 مخطط كائن، ولا توجد أجسام مفتوحة أو ديناميكية غير مبررة. |
| المراجع | لا توجد مراجع داخلية مفقودة. |
| MySQL | 40 جدولًا، مع FK وIndexes وChecks وفق المدقق. |
| API/DB | التعدادات والمواضع الحرجة والجداول ذات الأولوية متوافقة. |
| السياسة | Laravel-only وP2P-only وHost-only ICE متوافقة بين الوثائق. |
| الاتساق العميق | المسارات والتعدادات والحقول الحرجة والخصوصية وحالة المصحف وFollowUpItem تمر دون أخطاء أو تحذيرات. |

## 5. قواعد مصدر الحقيقة

تُعد قاعدة MySQL مصدر الحقيقة للبيانات الرسمية بعد تطبيق Services وPolicies. وتُعد استجابة Laravel Resource شكل السلك الرسمي وفق OpenAPI. أما أحداث الصفحة والمؤشر والآية العابرة عبر DataChannel فهي أحداث عرض مؤقتة، ولا تصبح مصدر حقيقة إلا عند إرسالها إلى مسار حفظ حالة المصحف.

يجب أن تتحقق Services من أن `edition_id` واحد في الصفحة والسورة والآية ونطاق التلاوة، وأن `planned_from_unit_id` و`planned_to_unit_id` ينتميان إلى الإصدار والسياق الصحيحين، وأن عناصر المتابعة مرتبطة بالخطة والتفصيل والطالب، وأن قبول التسجيل ينشئ العضوية ضمن Transaction ذرية.

## 6. متطلبات التنفيذ قبل اعتبار النظام جاهزًا للبرمجة التطبيقية

العقود أصبحت موحدة، لكن تنفيذ Laravel نفسه لم يُبن ضمن هذه المراجعة. عند بدء التنفيذ يجب إنشاء Migrations تطابق `database_schema.sql`، ثم Models وعلاقاتها، وForm Requests ترفض الحقول غير المعرفة، وResources تخرج مفاتيح الاستجابة نفسها دون غلاف `data`، وPolicies تطبق ملكية الحلقة وعلاقة الطالب بالمعلم وحالة القبول.

يجب بناء `SaveSessionMushafStateService` مع تحقق الإصدار والنطاق وoptimistic concurrency باستخدام `version`، وبناء `WebRtcSignalingService` الذي يتحقق من طرفي الجلسة وHost ICE فقط ولا يفسر أو يخزن SDP أو ICE. ويجب تغطية العمليات باختبارات Feature وUnit للحالات الصحيحة والخاطئة والتكرار والخصوصية وانتقالات الحالة وفشل الاتصال المباشر.

لا ينبغي إضافة Reverb أو Pusher أو Soketi أو Socket.IO أو SIPSorcery أو STUN أو TURN أو Relay أو Media Server أو Proxy أثناء التنفيذ؛ ذلك سيكون خرقًا مباشرًا للسياسة حتى لو كان الهدف تحسين الاتصال.

## 7. أدوات التحقق المستخدمة

```text
python scripts/check_openapi_refs.py
python scripts/validate_openapi_contract.py openapi.yaml
python scripts/audit_openapi_explicit_contract.py
python scripts/audit_openapi_nested_objects.py
python scripts/audit_openapi_strictness.py
python scripts/validate_database_contract.py
python scripts/validate_api_database_alignment.py
python scripts/validate_project_policy.py
python scripts/audit_cross_document_consistency.py
```

نتيجة آخر تشغيل ناجح: `missing_refs 0`، وOpenAPI صالح، و`data_properties=[]`، و`open_nested_object_nodes=0`، و`open_objects=0`، و`dynamic_objects=[]`، وMySQL/API/Policy/Deep consistency كلها `PASS`.

## 8. الحكم النهائي للمراجعة

الوثائق والعقود الآن **متكاملة ومتوافقة ضمن النطاق الوظيفي والمعماري المحدد**، ولا توجد تعارضات حرجة معروفة بين OpenAPI وMySQL وLaravel وWebSocket/WebRTC وسياسة الخصوصية وP2P. وما بقي هو تحويل العقود إلى تنفيذ Laravel واختبارات تشغيلية، وليس قرارًا وظيفيًا مجهولًا أو فجوة تعاقدية غير موثقة.
