<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | Grinta</title>

    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $shareUrl }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Grinta">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:image" content="{{ $image }}">
    <meta property="og:url" content="{{ $shareUrl }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $image }}">

    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
               background: #0d1117; color: #f5f7fa; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Tahoma, sans-serif; }
        .card { width: min(100% - 32px, 520px); background: #161b22; border: 1px solid #30363d;
                border-radius: 16px; padding: 28px; text-align: center; }
        .cover { width: 100%; max-height: 260px; object-fit: cover; border-radius: 12px; background: #21262d; }
        h1 { font-size: 1.5rem; margin: 20px 0 8px; }
        p { color: #b7c0cc; line-height: 1.7; margin: 0 0 24px; }
        .actions { display: grid; gap: 12px; }
        a.btn { display: block; padding: 13px 18px; border-radius: 10px; text-decoration: none;
                font-weight: 600; background: #e53935; color: #fff; }
        a.btn.secondary { background: #21262d; color: #f5f7fa; border: 1px solid #30363d; }
        .claim { margin-top: 22px; padding: 14px; border: 1px dashed #58a6ff; border-radius: 10px; }
        .claim small { display: block; color: #8b949e; margin-bottom: 6px; }
        .claim code { font-size: 1.25rem; letter-spacing: 2px; color: #58a6ff; user-select: all; }
    </style>
</head>
<body>
    <main class="card">
        @if(!empty($image))
            <img class="cover" src="{{ $image }}" alt="{{ $title }}">
        @endif

        <h1>{{ $title }}</h1>
        <p>{{ $description }}</p>

        <div class="actions">
            @if(!empty($appUrl))
                <a class="btn" href="{{ $appUrl }}">{{ app()->getLocale() === 'ar' ? 'افتح في التطبيق' : 'Open in the app' }}</a>
            @endif

            @if(!empty($playUrl))
                <a class="btn secondary" href="{{ $playUrl }}">{{ app()->getLocale() === 'ar' ? 'تحميل من Google Play' : 'Get it on Google Play' }}</a>
            @endif

            @if(!empty($appStoreUrl))
                <a class="btn secondary" href="{{ $appStoreUrl }}">{{ app()->getLocale() === 'ar' ? 'تحميل من App Store' : 'Download on the App Store' }}</a>
            @endif
        </div>

        @if(!empty($claimCode))
            <div class="claim">
                <small>{{ app()->getLocale() === 'ar' ? 'كود الاسترداد للمستخدم الجديد على iOS' : 'Claim code for new iOS users' }}</small>
                <code>{{ $claimCode }}</code>
            </div>
        @endif
    </main>
</body>
</html>
