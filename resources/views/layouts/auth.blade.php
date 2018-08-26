<!doctype html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'DriveSaver') }}</title>

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,300i,400,400i,500,500i,600,600i,700,700i&amp;subset=latin-ext">

    <!-- Scripts -->
    <script>
        window.CamelThemes = <?php echo json_encode([
            'user' => auth()->user(),
            'csrfToken' => csrf_token(),
        ]);?>;
    </script>

    <script src="{{ mix('js/app.js') }}" defer></script>

    <!-- Styles -->
    <style type="text/css">
        [v-cloak] { display: none }
    </style>

    <link href="{{ mix('css/app.css') }}" rel="stylesheet">

</head>
<body>
    <div class="page" id="app">
        @yield('content')
    </div>
</body>
</html>
