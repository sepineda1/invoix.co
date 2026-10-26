<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'API de INVOIX INC · Documentación' }}</title>
    <meta name="description" content="{{ $description ?? 'Documentación de la API REST de INVOIX INC: terceros, productos, facturas electrónicas, notas, compras, recibos, comprobantes contables, reportes y webhooks.' }}">
    @isset($canonical)<link rel="canonical" href="{{ $canonical }}">@endisset
    <link rel="icon" href="/brand/favicon.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root { --brand: #0284c7; --ink: #131920; --bar: 56px; }
        html, body { margin: 0; padding: 0; }
        body { font-family: Inter, system-ui, -apple-system, 'Segoe UI', sans-serif; color: var(--ink); }
        .docs-bar { position: fixed; inset: 0 0 auto 0; height: var(--bar); z-index: 50; display: flex; align-items: center; gap: 16px; padding: 0 20px;
            background: var(--ink); color: #e2e8f0; box-shadow: 0 1px 0 rgba(255,255,255,.06); }
        .docs-bar img { height: 36px; width: auto; display: block; background: #fff; border-radius: 6px; padding: 3px 8px; }
        .docs-bar .sep { width: 1px; height: 22px; background: rgba(255,255,255,.18); }
        .docs-bar .title { font-weight: 600; font-size: 15px; letter-spacing: .01em; }
        .docs-bar .version { font: 500 11px 'JetBrains Mono', monospace; padding: 2px 7px; border-radius: 999px; background: rgba(2,132,199,.25); color: #7dd3fc; }
        .docs-bar nav { margin-left: auto; display: flex; gap: 6px; align-items: center; }
        .docs-bar a { color: #cbd5e1; text-decoration: none; font-size: 13.5px; padding: 7px 11px; border-radius: 8px; }
        .docs-bar a:hover { background: rgba(255,255,255,.08); color: #fff; }
        .docs-bar a.cta { background: var(--brand); color: #fff; font-weight: 600; }
        .docs-bar a.cta:hover { background: #0369a1; }
        #docs { padding-top: var(--bar); }
        .docs-loading { padding: 120px 24px; text-align: center; color: #64748b; font-size: 15px; }
        @media (max-width: 720px) { .docs-bar .title, .docs-bar .sep, .docs-bar a:not(.cta) { display: none; } }
    </style>
</head>
<body>
<header class="docs-bar">
    <a href="{{ $home }}" style="padding:0"><img src="/brand/invoix.png" alt="invoix.co"></a>
    <span class="sep"></span>
    <span class="title">API</span>
    <span class="version">v1</span>
    <nav>
        <a href="{{ $specUrl }}" target="_blank" rel="noopener">OpenAPI JSON</a>
        <a href="{{ $home }}">Inicio</a>
        @isset($appUrl)<a class="cta" href="{{ $appUrl }}">Entrar</a>@endisset
    </nav>
</header>
<div id="docs"><div class="docs-loading">Cargando la documentación…</div></div>

<script src="https://cdn.jsdelivr.net/npm/redoc@2/bundles/redoc.standalone.js"></script>
<script>
    Redoc.init(@json($spec), {
        scrollYOffset: 56,
        hideDownloadButton: true,
        expandResponses: '200,201',
        jsonSampleExpandLevel: 3,
        requiredPropsFirst: true,
        pathInMiddlePanel: true,
        sortTagsAlphabetically: false,
        hideHostname: false,
        menuToggle: true,
        theme: {
            spacing: { unit: 5, sectionHorizontal: 36, sectionVertical: 28 },
            breakpoints: { medium: '60rem', large: '86rem' },
            colors: {
                primary: { main: '#0284c7' },
                success: { main: '#15803d' },
                warning: { main: '#b45309' },
                error: { main: '#b91c1c' },
                text: { primary: '#131920', secondary: '#475569' },
                http: { get: '#0284c7', post: '#15803d', put: '#b45309', delete: '#b91c1c', patch: '#7c3aed' },
            },
            typography: {
                fontSize: '15px', lineHeight: '1.6em', fontFamily: 'Inter, system-ui, sans-serif',
                headings: { fontFamily: 'Inter, system-ui, sans-serif', fontWeight: '700' },
                code: { fontFamily: '"JetBrains Mono", ui-monospace, monospace', fontSize: '13px', color: '#0369a1', backgroundColor: '#f0f9ff' },
                links: { color: '#0284c7', visited: '#0284c7', hover: '#0369a1' },
            },
            sidebar: {
                width: '280px', backgroundColor: '#f8fafc', textColor: '#1e293b', activeTextColor: '#0284c7',
                groupItems: { activeBackgroundColor: '#e0f2fe', activeTextColor: '#0369a1', textTransform: 'uppercase' },
                level1Items: { activeBackgroundColor: '#e0f2fe', activeTextColor: '#0369a1' },
                arrow: { color: '#64748b' },
            },
            rightPanel: { backgroundColor: '#131920', width: '42%', textColor: '#e2e8f0' },
            codeBlock: { backgroundColor: '#0b1016' },
            fab: { backgroundColor: '#0284c7', color: '#ffffff' },
        },
    }, document.getElementById('docs'));
</script>
</body>
</html>
