# عقد ميزة Laravel: التوافر وخطة المتابعة

## تعريف الميزة

- **الفاعل الأساسي:** `student` أو `teacher` المرتبط به الطالب.
- **النطاق:** `GET/PUT /api/v1/students/{student}/availability` و`GET/PUT /api/v1/students/{student}/follow-up-plan`.
- **الاستجابة:** `attendance_preferences` أو `follow_up_plan`، ويُمنع `data`.
- **الطبيعة:** بيانات رسمية محفوظة متزامنة داخل Transaction.
- **منع التكرار:** تحديث التوافر يستبدل مجموعة الفترات ذريًا؛ تحديث الخطة يزيد `version` ويحذف تفاصيل النسخة السابقة داخل نفس المعاملة.

## المدخلات والمخرجات

| المجال | المدخلات | المخرجات | القيود |
|---|---|---|---|
| التوافر | `timezone`, `weekly_slots`, `preferred_session_duration_minutes` | `attendance_preferences` | فترة واحدة على الأقل، يوم 0–6، 10–180 دقيقة، ومنع التداخل |
| الخطة | `frequency`, `details`, `starts_on`, `ends_on` | `follow_up_plan` | frequency متعاقدة، تفاصيل غير فارغة، amount أكبر من صفر، version متزايد |

## الصلاحيات

يستطيع الطالب قراءة وتعديل بياناته. يستطيع المعلم المرتبط بعضوية فعالة قراءة وتعديل بيانات الطالب؛ لا يكفي وجود معلم آخر أو طلب عام غير مقبول. يطبق `StudentLearningPolicy` عبر `Gate`.

## توزيع الطبقات

```text
Request -> StudentLearningPolicy -> Controller -> UpdateStudentAvailabilityService/FollowUpPlanService -> Models/Transaction -> Resource
```

توجد Requests في `app/Http/Requests/Api/V1/Progress`، وResources في `app/Http/Resources/Api/V1/Progress`، وServices في `app/Services/Progress`. اختبارات Feature في `tests/Feature/ProgressPlanTest.php` تغطي النجاح، الحقول المجهولة، التداخل، النسخ، والوصول.

## ملاحظات التخزين

تطابق الميزة جداول `student_availability_profiles`, `student_availability_slots`, `follow_up_plans`, و`follow_up_plan_details`. عناصر المواعيد الفعلية في `follow_up_items` مهيأة Migration/Model وستنفذ في شريحة الجدولة اللاحقة عبر Service مستقلة، ولا تنشئها Controllers.
