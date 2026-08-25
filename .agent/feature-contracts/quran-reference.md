# عقد ميزة Laravel: المصحف المرجعي والقراءة

## تعريف الميزة

- **الفاعل الأساسي:** `teacher` أو `student` مصادق عليه.
- **النطاق:** `GET /api/v1/quran/surahs`, `GET /api/v1/quran/pages/{pageNumber}`, و`GET /api/v1/quran/ayahs/{ayahId}`.
- **البيانات:** نص المصحف الرسمي محفوظ محليًا في MySQL ومُحمّل عبر `QuranReferenceSeeder` من ملف نصي محلي مستخلص من مرجع Shafeea؛ حُذفت روابط الصوت الخارجية بالكامل.
- **الاستجابة:** `surahs`, `quran_page`, أو `ayah`، وتحتوي كل آية وسورة وصفحة على `edition_id` صريح. يُمنع `data`.
- **الصلاحيات:** المصادقة فقط في هذه الشريحة؛ لا يكتب العميل بيانات المصحف ولا يغير النسخة المرجعية.

## الطبقات

```text
Sanctum -> QuranController -> QuranEdition/QuranSurah/QuranPage/QuranAyah -> QuranResource
```

وضعت الـMigration في `database/migrations`، والنماذج في `app/Models`، ومصدر البيانات في `database/seeders/data` و`QuranReferenceSeeder`، وController/Resource في مسارات Quran المعيارية. لا توجد خدمة HTTP خارجية ولا نقل صوت أو فيديو.

## القيود والاختبارات

تحافظ الجداول على أرقام السور 1–114 والصفحات 1–604 والآيات 1–6236، وتفصل النسخة عبر `edition_id` مع uniqueness لكل إصدار. يثبت `QuranReferenceTest` قراءة 114 سورة والصفحة الأولى والآية الأولى، ووجود الحقول الصريحة وعدم غلاف `data`. تُشغل migrations وseed وPint وPHPUnit ومدققات العقود وLaravel-only/P2P-only قبل Commit.
