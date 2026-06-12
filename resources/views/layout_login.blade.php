<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>@yield('title') - SB Admin 2</title>

    <!-- Custom fonts for this template-->
    <link href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet" type="text/css">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">

    <!-- Custom styles for this template-->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @vite(['resources/js/submit-form.js'])
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <!-- Core plugin JavaScript-->
    <script src="{{ asset('vendor/jquery-easing/jquery.easing.min.js') }}"></script>

    <!-- Dashboard layout -->
    @vite(['resources/js/dashboard_layout/switch_lang.js'])

</head>

<body class="flex flex-row h-screen p-[35px] lg:justify-between justify-center gap-9 overflow-auto">
    @yield('content')
</body>

</html>