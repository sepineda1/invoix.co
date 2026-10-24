<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0275b3">
    <title inertia>{{ config('app.name') }}</title>
    <link rel="icon" href="/brand/favicon.png" type="image/png">
    <link rel="apple-touch-icon" href="/brand/favicon.png">
    <link rel="manifest" href="/manifest.webmanifest">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
