<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $success ? 'تم تفعيل الحساب' : 'تعذر تفعيل الحساب' }} - حلقة القرآن</title>
    <style>
        :root { color-scheme: light; font-family: Tahoma, Arial, sans-serif; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: linear-gradient(135deg,#0e4f52,#d8eee9); color: #143b3b; }
        main { width: min(92vw, 520px); padding: 42px 34px; border-radius: 24px; background: rgba(255,255,255,.96); box-shadow: 0 24px 70px rgba(8,50,50,.22); text-align: center; }
        .mark { width: 72px; height: 72px; margin: 0 auto 18px; display: grid; place-items: center; border-radius: 50%; background: {{ $success ? '#dff5ea' : '#fde8e7' }}; color: {{ $success ? '#17754f' : '#b42318' }}; font-size: 38px; }
        h1 { margin: 0 0 12px; font-size: 28px; } p { margin: 8px 0; line-height: 1.9; color: #526767; }
        .hint { margin-top: 22px; font-size: 13px; }
    </style>
</head>
<body>
<main>
    <div class="mark">{{ $success ? '✓' : '!' }}</div>
    <h1>{{ $success ? 'تم تفعيل بريدك بنجاح' : 'تعذر تفعيل البريد' }}</h1>
    <p>{{ $message }}</p>
    <p class="hint">{{ $success ? 'يمكنك العودة إلى تطبيق حلقة القرآن وتسجيل الدخول الآن.' : 'اطلب رسالة تفعيل جديدة من التطبيق وحاول مرة أخرى.' }}</p>
</main>
</body>
</html>
