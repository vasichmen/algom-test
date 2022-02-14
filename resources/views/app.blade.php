<!doctype html>
<html class="page" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Проверка ссылок</title>
    <link href="/favicon.ico" rel="shortcut icon" type="image/x-icon">
    <link href="{{ mix('css/app.css') }}" type="text/css" rel="stylesheet" />
</head>
<body class="page__body">
<div class="page__wrapper" id="app"></div>
<script src="{{ mix('js/app.js') }}" type="text/javascript"></script>
</body>
</html>
