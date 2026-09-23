<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · {{ config('app.name') }}</title>
    <style>
        :root{color-scheme:light dark;font-family:system-ui,sans-serif;background:#f4f4f5;color:#18181b}
        body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;box-sizing:border-box}
        main{max-width:460px;padding:40px;background:#fff;border:1px solid #e4e4e7;border-radius:20px}
        .code{color:#4f46e5;font-weight:700;font-size:14px;letter-spacing:.1em}h1{font-size:28px;letter-spacing:-.03em}
        p{line-height:1.65;color:#52525b}a{display:inline-block;margin-top:20px;padding:12px 18px;border-radius:10px;background:#4f46e5;color:white;text-decoration:none}a:focus-visible{outline:3px solid #818cf8;outline-offset:4px}
        @media(prefers-color-scheme:dark){:root{background:#09090b;color:#fafafa}main{background:#18181b;border-color:#3f3f46}p{color:#a1a1aa}.code{color:#a5b4fc}}
    </style>
</head>
<body><main><div class="code">@yield('code')</div><h1>@yield('title')</h1><p>@yield('message')</p><a href="{{ route('login') }}">Return to sign in</a></main></body>
</html>
