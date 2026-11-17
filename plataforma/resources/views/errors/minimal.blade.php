<!DOCTYPE html>
<html lang="es-CO">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') · MarketCity</title>
    <style>
        body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:#f8fafc;color:#0f172a;display:flex;min-height:100vh;align-items:center;justify-content:center;padding:16px}
        main{max-width:28rem;text-align:center}
        .code{font-size:.875rem;font-weight:600;color:#64748b;letter-spacing:.05em}
        h1{font-size:1.5rem;margin:.5rem 0}
        p{color:#475569;line-height:1.5}
        a{display:inline-block;margin-top:1rem;background:#0f172a;color:#fff;padding:.75rem 1.25rem;border-radius:.5rem;text-decoration:none;font-weight:600}
        a:focus-visible{outline:3px solid #f59e0b;outline-offset:2px}
    </style>
</head>
<body>
<main>
    <p class="code">Error @yield('code')</p>
    <h1>@yield('title')</h1>
    <p>@yield('message')</p>
    <a href="{{ url('/') }}">Volver al inicio</a>
</main>
</body>
</html>
