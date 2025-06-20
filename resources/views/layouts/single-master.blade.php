<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title','Aplu Push')</title>
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicon_io/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images//favicon_io/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images//favicon_io/favicon-16x16.png') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('/images/favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    <link rel="stylesheet" href="{{ asset('css/responsive.css') }}">
    <link rel="stylesheet" href="{{ asset('css/iziToast.css') }}">
    <style>
        /* Layout Structure */
         html {
            height: 100%;
           
        }

        body {
            height: 100%;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            background-color: #f8f9fa;
            background-image: url('https://img.freepik.com/premium-photo/free-seamless-pattern-abstract-texture-geometric-vector-illustration-design-wallpaper-background_1226483-21619.jpg?semt=ais_hybrid&w=740')
        }
        
        /* Header */
        .custom-header {
            flex-shrink: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 10px 20px;
            background-color: #fff;
            border-bottom: 1px solid #e3e6ea;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            z-index: 1000;
        }

        .custom-header .logo img {
            height: 52px;
            transition: all 0.3s ease;
        }

        .custom-header .logo img:hover {
            transform: scale(1.05);
        }

        /* Main Content */
        #page-content {
            flex: 1 0 auto;
            width: 100%;
            
           
        }

        /* Footer */
        .custom-footer {
            flex-shrink: 0;
            text-align: center;
            padding: 15px 0;
            background-color: #fff;
            border-top: 1px solid #e3e6ea;
            font-size: 0.875rem;
            color: #6c757d;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .custom-header .logo img {
                height: 42px;
            }
            
         
        }

        @media (max-width: 576px) {
            .custom-header {
                padding: 8px 15px;
            }
            
            .custom-header .logo img {
                height: 36px;
            }
            
            .custom-footer {
                padding: 10px 0;
                font-size: 0.75rem;
            }
        }
    </style>
    @stack('styles')
</head>

<body>
    <!-- Fixed Header -->
    <header class="custom-header">
        <div class="logo">
            <a href="{{url('/')}}"><img src="{{ asset('images/logo-main.png') }}" alt="Aplu Logo" class="img-fluid"></a>
        </div>
    </header>

    <!-- Main Content -->
    <main id="page-content">
        @yield('content')
    </main>

    <!-- Fixed Footer -->
    <footer class="custom-footer">
        &copy; {{date('Y')}} Aplu Push Notification Service. All Rights Reserved.
    </footer>

    <script src="{{ asset('vendor/global/global.min.js') }}"></script>
    <script src="{{ asset('js/iziToast.js') }}"></script>

    
    {{-- MESSAGE ALERT --}}
    @if (Session::has('error'))
        <script>
            iziToast.error({
                title: 'Error',
                message: '{{ Session::get('error') }}',
                position: 'topRight'
            });
        </script>
    @endif
    @if (Session::has('success'))
        <script>
            iziToast.success({
                title: 'Success',
                message: '{{ Session::get('success') }}',
                position: 'topRight'
            });
        </script>
    @endif
    @if (Session::has('warning'))
        <script>
            iziToast.warning({
                title: 'Warning',
                message: '{{ Session::get('success') }}',
                position: 'topRight'
            });
        </script>
    @endif
    @if (isset($errors->any()))
        @foreach ($errors->all() as $error)
            <script>
                iziToast.error({
                    title: 'Error',
                    message: '{{ $error }}',
                    position: 'topRight'
                });
            </script>
        @endforeach
    @endif
    @stack('scripts')
</body>

</html>