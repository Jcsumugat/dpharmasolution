<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MJ\'s Pharmacy')</title>
</head>
<body>
    @include('admin.admin-header')

    <main>
        @yield('content')
    </main>

    @include('admin.admin-footer')
    @stack('scripts')
</body>
</html>
