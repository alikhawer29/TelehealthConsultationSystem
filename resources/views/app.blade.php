// resources/views/layouts/app.blade.php

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <!-- Add CSS or any other meta -->
</head>
<body>
    <div class="container">
        @yield('content')
    </div>
</body>
</html>
