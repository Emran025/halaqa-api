<!doctype html>
<html lang="ar" dir="rtl">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>إعادة تعيين كلمة المرور - حلقة القرآن</title>
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:linear-gradient(135deg,#0e4f52,#d8eee9);font-family:Tahoma,Arial;color:#143b3b}main{width:min(92vw,460px);background:#fff;padding:34px;border-radius:22px;box-shadow:0 24px 70px #08323238}h1{margin-top:0}label{display:block;margin:14px 0 6px}input{box-sizing:border-box;width:100%;padding:12px;border:1px solid #c3cdd6;border-radius:8px;font-size:16px}button{width:100%;margin-top:22px;padding:13px;border:0;border-radius:8px;background:#16756d;color:#fff;font-size:16px;cursor:pointer}.error{background:#fde8e7;color:#b42318;padding:10px;border-radius:8px;line-height:1.8}</style></head>
<body><main><h1>إعادة تعيين كلمة المرور</h1><p>أدخل رمز التحقق وكلمة المرور الجديدة. يمكنك أيضًا استخدام هذه البيانات في تطبيق حلقة القرآن.</p>
@if($errors->any())<div class="error">{{ $errors->first() }}</div>@endif
<form method="post" action="{{ route('password.reset.submit') }}">@csrf
<label>البريد الإلكتروني</label><input type="email" name="email" value="{{ $email }}" required>
<label>رمز التحقق</label><input name="token" value="{{ $token }}" required dir="ltr">
<label>كلمة المرور الجديدة</label><input type="password" name="password" minlength="8" required>
<label>تأكيد كلمة المرور</label><input type="password" name="password_confirmation" minlength="8" required>
<button type="submit">حفظ كلمة المرور</button></form></main></body></html>
