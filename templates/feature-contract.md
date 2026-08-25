# عقد ميزة Laravel

استخدم هذا القالب قبل تنفيذ ميزة جديدة أو تعديل ميزة متعددة الطبقات.

## تعريف الميزة

- **اسم الميزة:**
- **الفاعل الأساسي:** teacher أو student فقط
- **النتيجة المطلوبة:**
- **نطاق API:**
- **مخطط الطلب:** اسم OpenAPI ومصدر كل حقل
- **مفتاح الاستجابة الجذري:** مثل `user` أو `session` أو `tasks`، ويُمنع `data`
- **هل العملية متزامنة أم مؤجلة؟**
- **هل البيانات رسمية محفوظة أم حدث لحظي مؤقت؟**
- **آلية منع التكرار:** `client_operation_id` أو `Idempotency-Key`

## المدخلات

| الاسم | النوع | مطلوب؟ | قاعدة التحقق |
|---|---|---:|---|
|  |  |  |  |

## المخرجات

| العنصر | Resource | الحقول المسموح بها | المصدر أو الاشتقاق |
|---|---|---|---|
|  |  |  |  |

لا تُخرج Laravel Resource حقولًا غير موجودة في OpenAPI. إذا كان الحقل مشتقًا من أكثر من جدول، فاذكر Query/Service المسؤول عن اشتقاقه. لا تستخدم غلافًا عامًا باسم `data` ولا `additionalProperties: true`.

## الصلاحيات

- المستخدم المسموح له: teacher أو student فقط
- علاقة المستخدم بالمورد:
- الإسقاط العام والحقول الحساسة:
- Policy/Gate المطلوب:
- هل يتغير الوصول بعد القبول أو إنشاء العضوية؟

## توزيع الملفات

```text
app/Http/Controllers/Api/V1/<Context>/<Action>Controller.php
app/Http/Requests/Api/V1/<Context>/<Action>Request.php
app/Http/Resources/Api/V1/<Context>/<Resource>Resource.php
app/Services/<Context>/<Action>Service.php
app/Queries/<Context>/<Query>Query.php       # عند الحاجة فقط
app/Policies/<Resource>Policy.php             # عند الحاجة
app/Events/<Context>/<Event>.php              # عند الحاجة
app/Jobs/<Context>/<Job>.php                  # عند الحاجة
```

## مسار التنفيذ

```text
Request -> Policy -> Controller -> Service/Query -> Model/Event/Job -> Resource
```

## قواعد القرار

- إذا كان CRUD بسيطًا دون قواعد إضافية، اذكر سبب عدم إنشاء Service.
- إذا كان هناك أكثر من Model أو Transaction أو تفرع حالة، أنشئ Service.
- إذا كانت القراءة تقريرية أو مركبة، أنشئ Query Service.
- إذا كان التنفيذ طويلًا أو قابلًا لإعادة المحاولة، أضف Job خلف Service.
- إذا كانت الميزة لحظية، حدد Event/Channel، ولا تنقل الوسائط عبر Laravel.

## الاختبارات المطلوبة

- حالة النجاح.
- بيانات غير صالحة وحدود الأرقام والتواريخ والتعدادات.
- حقل غير معروف مرفوض بسبب `additionalProperties: false`.
- مستخدم غير مصرح أو علاقة غير صحيحة.
- مورد غير موجود.
- انتقالات الحالة المسموحة والمرفوضة.
- تكرار الطلب أو إعادة المحاولة عبر `client_operation_id` أو `Idempotency-Key`.
- اتساق Resource مع جدول MySQL وقيوده وFK وUnique وSoft Delete.
- عزل البيانات الحساسة قبل القبول وبعده.
- انقطاع WebSocket أو فشل P2P المباشر عند الحاجة، دون STUN/TURN/Relay أو نقل وسائط عبر Laravel.
