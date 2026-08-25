# عقد ميزة Laravel

استخدم هذا القالب قبل تنفيذ ميزة جديدة أو تعديل ميزة متعددة الطبقات.

## تعريف الميزة

- **اسم الميزة:**
- **الفاعل الأساسي:**
- **النتيجة المطلوبة:**
- **نطاق API:**
- **هل العملية متزامنة أم مؤجلة؟**

## المدخلات

| الاسم | النوع | مطلوب؟ | قاعدة التحقق |
|---|---|---:|---|
|  |  |  |  |

## المخرجات

| العنصر | Resource | الحقول المسموح بها |
|---|---|---|
|  |  |  |

## الصلاحيات

- المستخدم المسموح له:
- علاقة المستخدم بالمورد:
- Policy/Gate المطلوب:

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
- بيانات غير صالحة.
- مستخدم غير مصرح.
- مورد غير موجود.
- تكرار الطلب أو إعادة المحاولة.
- انقطاع خدمة خارجية أو WebSocket عند الحاجة.
