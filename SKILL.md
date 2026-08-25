---
name: laravel-architecture-rag
description: Laravel architecture governance for AI agents. Use when creating, modifying, reviewing, or refactoring Laravel APIs and when the agent must enforce a scalable tree /f structure, place API Resources in app/Http/Resources, Form Requests in app/Http/Requests, keep Controllers thin, and extract multi-step or long-running logic into Services or Jobs.
---

# Laravel Architecture Governance RAG

## الهدف

طبّق هذه المهارة عند إنشاء أو تعديل أي جزء من مشروع Laravel، خصوصًا في مشاريع API التي تحتوي على مصادقة، حلقات، طلاب، جلسات مباشرة، تقارير، أو تكاملات خارجية. الهدف هو منع الوكيل من وضع منطق غير مناسب داخل Controller أو إنشاء ملفات في أماكن عشوائية، مع الحفاظ على بنية قابلة للتوسع.

## قاعدة التشغيل الإلزامية

لا تكتب كودًا قبل تنفيذ هذه الخطوات بالترتيب:

1. افحص بنية المشروع الحالية باستخدام `tree /f` على Windows أو أمر مكافئ مثل `find . -maxdepth 4 -type f` على Linux/macOS.
2. اقرأ [references/canonical-tree.md](references/canonical-tree.md) وقارنها بالبنية الفعلية للمشروع.
3. اقرأ [references/placement-rules.md](references/placement-rules.md) لتحديد مكان كل ملف.
4. حدد حالات الاستخدام، المدخلات، المخرجات، الصلاحيات، وطول العملية قبل اختيار طبقة التنفيذ.
5. طبّق التعديل بأقل تغيير ممكن، ولا تخلط نمطًا معماريًا جديدًا مع نمط قائم دون سبب موثق.
6. شغّل [scripts/validate_laravel_tree.py](scripts/validate_laravel_tree.py) أو طبّق قائمة التحقق في [references/validation-checklist.md](references/validation-checklist.md).
7. اعرض في النتيجة الملفات التي أُنشئت أو عُدلت، وسبب وضع كل ملف في مكانه.

إذا تعارضت البنية المرجعية مع بنية المشروع القائمة، فالأولوية للتوافق مع المشروع القائم بشرط تسجيل الاستثناء وعدم إنشاء نمطين متنافسين داخل الوحدة نفسها.

## القواعد المعمارية غير القابلة للتفاوض

### Controllers

ضع Controllers في `app/Http/Controllers`. اجعل Controller مسؤولًا عن استقبال الطلب، استدعاء Form Request، تفويض الصلاحية، استدعاء Service أو Model Query مناسب، وإرجاع Resource أو استجابة API. لا تضع فيه استعلامات مركبة، حلقات أعمال، معاملات طويلة، استدعاءات خدمات خارجية، أو تحويلًا يدويًا كبيرًا للبيانات.

يُسمح باستعلام بسيط جدًا في Controller فقط إذا كان واضحًا وقصيرًا ولا يتكرر ولا يحتوي على قواعد عمل. عند وجود أكثر من خطوة مترابطة، أو أكثر من Model، أو معاملة `DB::transaction`, أو تكامل خارجي، أو منطق قابل لإعادة الاستخدام، انقل المنطق إلى Service.

### Form Requests

ضع طلبات التحقق والتفويض الخاصة بالمدخلات في `app/Http/Requests`. أنشئ Form Request لكل عملية لها قواعد تحقق أو صلاحيات أو رسائل أخطاء مستقلة. لا تضع قواعد التحقق الكبيرة داخل Controller، ولا تثق في بيانات العميل قبل مرورها عبر Request.

سمِّ الملف بصيغة عملية واضحة مثل `StoreHalaqaRequest` أو `UpdateSessionReportRequest`. اجعل `authorize()` خاصًا بالوصول المرتبط بالطلب، واجعل `rules()` خاصة بشكل البيانات، ولا تخلط منطق الأعمال داخل Form Request.

### API Resources

ضع Resources في `app/Http/Resources`. استخدمها لتحديد شكل استجابة API، وإخفاء الحقول الداخلية، وتوحيد أسماء الحقول والعلاقات، ومنع إعادة كائنات Eloquent الخام مباشرة إلى العميل.

سمِّ Resource المفرد بصيغة `HalaqaResource`، ومجموعة Resource بصيغة `HalaqaCollection` عند الحاجة إلى تحويل جماعي خاص. لا تضع استعلامات أو تغييرات قاعدة بيانات أو قواعد قرار داخل Resource؛ دوره تحويل البيانات فقط.

### Services

ضع الخدمات في `app/Services`، وأنشئ Service عندما تكون العملية متعددة الخطوات، أو طويلة، أو تستخدم أكثر من Model، أو تحتاج إلى Transaction، أو تتعامل مع Laravel Events أو WebSocket أو API خارجي، أو يجب اختبارها بمعزل عن HTTP.

سمِّ الخدمة حسب حالة الاستخدام، مثل `CreateHalaqaService`, `AcceptStudentRegistrationService`, `FinalizeSessionReportService`, أو `SyncMushafMistakesService`. اجعل لها مدخلًا واضحًا ومخرجًا واضحًا، ولا تجعلها تعتمد على `Request` أو `JsonResponse`؛ تستقبل DTO أو قيمًا منقحة وتعيد Entity أو Result أو قيمة محددة.

إذا كانت العملية طويلة ولا يلزم تنفيذها أثناء الطلب، فقسّمها إلى Service تنسق العملية وJob في `app/Jobs` ينفذ الجزء غير المتزامن. لا تستخدم Job لإخفاء منطق أعمال غير منظم.

### Models وScopes

ضع Models في `app/Models`. احتفظ فيها بالعلاقات، casts، accessors/mutators البسيطة، وquery scopes القابلة لإعادة الاستخدام. لا تحول Model إلى Service ضخم، ولا تضع فيه تنسيق استجابة API أو منطق WebSocket.

استخدم Scope للاستعلام المتكرر والمحدد، مثل `scopeForTeacher()` أو `scopeActive()`. إذا جمع الاستعلام عدة Models مع صلاحيات وتفرعات ونتائج مختلفة، ضعه في Query Service أو Service مناسب بدل تضخيم Model.

### Routes

اجعل `routes/api.php` و`routes/web.php` للتوجيه فقط. عرّف middleware وroute model binding وController action، ولا تضع عمليات قاعدة بيانات أو قواعد أعمال داخل closure route في الإنتاج.

### Authorization

استخدم Policies أو Gates للصلاحيات التي تعتمد على Model أو مورد محدد، وضعها في `app/Policies`. يجب أن يتحقق النظام من أن المعلم يملك الحلقة أو الجلسة، وأن الطالب مرتبط بها، قبل القراءة أو التعديل أو الاعتماد.

### Events وNotifications وBroadcasting

ضع Events في `app/Events`، وListeners في `app/Listeners`، وNotifications في `app/Notifications`. ضع تعريف صلاحية قنوات البث في `routes/channels.php` أو الموضع المعتمد في إصدار Laravel المستخدم.

استخدم Broadcasting وWebSocket للأحداث اللحظية مثل حالة الجلسة، الإشارات، ومزامنة علامة المصحف. لا تضع الصوت أو الفيديو داخل Laravel؛ الوسائط المباشرة تسير عبر WebRTC، بينما يحفظ Laravel الحالة والبيانات الرسمية.

### قاعدة البيانات والاختبارات

ضع migrations في `database/migrations`, factories في `database/factories`, seeders في `database/seeders`. لا تنفذ تغييرات Schema من Controller أو Service.

ضع اختبارات HTTP وFeature في `tests/Feature`، واختبارات الوحدات في `tests/Unit`. اختبر Controller من خلال HTTP ونتيجة Resource، واختبر Service على حالات النجاح والفشل، واختبر Policy وRequest بقواعد مستقلة.

## مصفوفة القرار السريعة

| الحاجة | الطبقة الإلزامية |
|---|---|
| استقبال الطلب وإرجاع الاستجابة | Controller |
| التحقق من المدخلات | Form Request |
| تحويل المخرجات إلى JSON API | Resource |
| استعلام بسيط متكرر | Model Scope |
| أكثر من خطوة أو Model أو Transaction | Service |
| تنفيذ طويل أو قابل للتأجيل | Service + Job |
| صلاحية مرتبطة بمورد | Policy |
| حدث لحظي أو بث | Event + Broadcast/Notification |
| استعلام تقريري مركب | Query Service أو Service مخصص |
| استدعاء REST API خارجي | Service + Client class عند تكرار التكامل |
| تخزين أو تعديل Schema | Migration |

## سير العمل حسب نوع المهمة

### إنشاء Endpoint جديد

حدد المورد وحالة الاستخدام أولًا، ثم أنشئ Request للمدخلات، وService إذا تجاوزت العملية CRUD البسيط، وResource للمخرج، وPolicy عند وجود ملكية أو علاقة، وبعد ذلك اربط Controller بالطبقات. حدّث الاختبارات قبل اعتبار العمل مكتملًا.

### تعديل Endpoint قائم

افحص Controller وRequest وResource وService وPolicy قبل التعديل. حافظ على العقد الحالي للاستجابة ما لم يطلب المستخدم تغييره. إذا وجدت منطقًا موضوعًا في الطبقة الخطأ، انقله تدريجيًا مع اختبارات تمنع الانحدار.

### عملية طويلة أو متعددة

أنشئ Service منسقة للعملية، واستخدم `DB::transaction` عندما تكون الخطوات ذرية، ثم استخدم Job للعمل الذي لا يحتاج نتيجة فورية. أعد إلى العميل معرف العملية أو حالة واضحة إذا كان التنفيذ غير متزامنًا.

### مراجعة معمارية

اعرض شجرة `tree /f`، ثم راجع كل Controller بحثًا عن استعلامات وقواعد أعمال زائدة، وكل Resource بحثًا عن منطق غير خاص بالتحويل، وكل Request بحثًا عن منطق أعمال، وكل Service بحثًا عن اعتماد مباشر على HTTP. استخدم قائمة التحقق في `references/validation-checklist.md`.

## أخطاء يجب منعها

لا تنشئ مجلدًا باسم `app/Resources` لواجهات API؛ استخدم `app/Http/Resources`. لا تنشئ `app/Http/Request` بالمفرد؛ استخدم `app/Http/Requests`. لا تضع SQL أو Eloquent queries طويلة داخل Controller. لا تعيد `Model::all()` الخام إلى العميل. لا تضع `try/catch` عامًا يخفي الخطأ بدل معالجة الحالة أو تحويلها إلى Exception مناسبة. لا تكرر الاستعلام نفسه في عدة Controllers؛ استخرج Scope أو Service.

لا تنشئ Repository لكل Model تلقائيًا؛ استخدمه فقط إذا احتاجت الوحدة إلى مصدر بيانات متعدد أو تعقيد تخزين مستقل. لا تنشئ Service لكل سطر CRUD بسيط؛ استخدم Service عندما توجد قيمة فعلية في عزل حالة الاستخدام أو تجميع الخطوات.

## شكل الإجابة الإلزامي للوكيل

عند تسليم أي تعديل Laravel، اذكر باختصار: حالة الاستخدام، الملفات التي أُنشئت أو عُدلت، سبب وضع كل ملف، مسار البيانات من Request إلى Controller إلى Service إلى Resource، الصلاحيات المطبقة، الاختبارات المنفذة، وأي استثناء عن الشجرة المرجعية.

اقرأ الملفات المرجعية بحسب الحاجة:

- استخدم [references/canonical-tree.md](references/canonical-tree.md) عند إنشاء أو نقل ملفات.
- استخدم [references/placement-rules.md](references/placement-rules.md) عند اختيار الطبقة المناسبة.
- استخدم [references/realtime-and-webrtc.md](references/realtime-and-webrtc.md) عند بناء WebSocket أو WebRTC أو مزامنة المصحف.
- استخدم [references/validation-checklist.md](references/validation-checklist.md) عند مراجعة أو اعتماد التعديل.
- استخدم [templates/feature-contract.md](templates/feature-contract.md) قبل بناء ميزة جديدة متعددة الطبقات.
