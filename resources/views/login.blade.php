{{-- resources/views/login.blade.php --}}
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aplu Push – Login</title>

    {{-- SEO / Social --}}
    <meta name="keywords" content="push notifications, push notification service, Aplu Push, real-time notifications">
    <meta name="description" content="Aplu Push delivers reliable real-time push notifications for web and mobile.">
    <meta property="og:image" content="{{ asset('images/aplu.png') }}" />
    <meta property="og:image:secure_url" content="{{ asset('images/aplu.png') }}" />

    {{-- Favicon --}}
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicon_io/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32"  href="{{ asset('images/favicon_io/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16"  href="{{ asset('images/favicon_io/favicon-16x16.png') }}">
    <link rel="icon" type="image/x-icon"              href="{{ asset('images/favicon.ico') }}">

    {{-- Styles --}}
    <link href="{{ asset('css/style.css') }}"       rel="stylesheet">
    <link href="{{ asset('css/main.css') }}"        rel="stylesheet">
    <link href="{{ asset('css/responsive.css') }}"  rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/iziToast.css') }}">

    <style>
        .password-toggle { position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer; }
        .password-wrapper { position:relative; }
    </style>
    {{-- Add this right before the closing </body> tag --}}
<style>
    /* Bubble Animation Styles */
    .bubbles {
        position: fixed;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        pointer-events: none;
        z-index: -1;
        overflow: hidden;
    }
    
    .bubble {
        position: absolute;
        bottom: -100px;
        background: #f93a0b;
        border-radius: 50%;
        /* filter: blur(5px); */
        animation: float linear infinite;
    }
    
    @keyframes float {
        0% {
            transform: translateY(0) rotate(0deg);
            opacity: 1;
        }
        100% {
            transform: translateY(-100vh) rotate(360deg);
            opacity: 0;
        }
    }
</style>
</head>
<body class="h-100">
<div class="container-fluid h-100">
    <div class="row h-100 align-items-center justify-content-center">

        {{-- Login Card --}}
        <div class="col-xl-4 col-lg-5 col-md-8 col-sm-10">
            <div class="card p-3 p-md-5">

                {{-- Header --}}
                <div class="text-center mb-4">
                    <a href="{{ url('/') }}">
                        <img src="{{ asset('images/logo-full.png') }}" alt="Aplu Push" class="img-fluid" style="height:72px;">
                    </a>
                    <h2 class="mt-3 fw-bold">Welcome Back</h2>
                    <p class="text-muted">Sign in to continue to your Aplu Push account</p>
                </div>

                {{-- Form --}}
                <form id="login" method="POST" action="{{ route('login.doLogin') }}">
                    @csrf

                    {{-- Email --}}
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email') }}" placeholder="you@example.com" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   placeholder="********" required>
                            <span class="password-toggle" id="togglePassword"><i class="far fa-eye"></i></span>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Remember me & forgot --}}
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="remember_check" name="remember_me" value="1"
                                   {{ old('remember_me', true) ? 'checked' : '' }}>
                            <label for="remember_check" class="form-check-label">Remember me</label>
                        </div>
                        <a href="#" class="text-primary small">Forgot password?</a>
                    </div>

                    {{-- Submit --}}
                    <div class="d-grid gap-2 mb-3">
                        <button type="submit" class="btn btn-primary" id="loginBtn">
                            <span class="sign-in-text">Sign In</span>
                            <span id="loginSpinner" class="spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true"></span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Footer --}}
            <div class="text-center mt-3">
                <p class="text-muted mb-0">
                    &copy; {{ now()->year }} Aplu Push.
                    <a href="https://aplu.io/terms-conditions/" target="_blank">Terms</a> |
                    <a href="https://aplu.io/privacy-policy/" target="_blank">Privacy</a>
                </p>
            </div>
        </div>


<div class="bubbles">
    <!-- Bubbles will be added dynamically by JS -->
</div>

    </div>
</div>

{{-- Scripts --}}
<script src="{{ asset('vendor/global/global.min.js') }}"></script>
<script src="{{ asset('js/custom.min.js') }}"></script>
<script src="{{ asset('js/deznav-init.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/jquery.validate.min.js"></script>
<script src="{{ asset('js/iziToast.js') }}"></script>

<script>
    // Toggle password visibility
    document.getElementById('togglePassword').addEventListener('click', function () {
        const input = document.getElementById('password');
        const eye = this.querySelector('i');
        input.type = input.type === 'password' ? 'text' : 'password';
        eye.classList.toggle('fa-eye');
        eye.classList.toggle('fa-eye-slash');
    });

    // Validate and show spinner
    $('#login').validate({
        rules: {
            email: { 
                required: true,
                email: true
            },
            password: { 
                required: true,
                minlength: 6
            }
        },
        messages: {
            email: { 
                required: 'Email is required',
                email: 'Enter a valid email'
            },
            password: { 
                required: 'Password is required',
                minlength: 'Min 6 characters' 
            }
        },
        errorElement: 'div',
        errorPlacement: function (error, el) {
            error.addClass('invalid-feedback').insertAfter(el);
        },
        highlight: function (el) {
            $(el).addClass('is-invalid');
        },
        unhighlight: function (el) {
            $(el).removeClass('is-invalid');
        },
        submitHandler: function (form) {
            document.getElementById('loginBtn').disabled = true;
            document.getElementById('loginSpinner').classList.remove('d-none');
            form.submit();
        }
    });
</script>

{{-- Flash Messages --}}
@if (Session::has('success'))
    <script> iziToast.success({ title:'Success', message:'{{ Session::get('success') }}', position:'topRight' }); </script>
@endif

@if (Session::has('error'))
    <script> iziToast.error({ title:'Error', message:'{{ Session::get('error') }}', position:'topRight' }); </script>
@endif

@if (Session::has('warning'))
    <script> iziToast.warning({ title:'Warning', message:'{{ Session::get('warning') }}', position:'topRight' }); </script>
@endif

@if ($errors->any())
    <script>
        @foreach ($errors->all() as $msg)
            iziToast.error({ title:'Error', message:'{{ $msg }}', position:'topRight' });
        @endforeach
    </script>
@endif

<script>
    // Bubble Animation Script
    document.addEventListener('DOMContentLoaded', function() {
        const bubblesContainer = document.querySelector('.bubbles');
        const colors = ['#f93a0b33', '#f93a0b70', '#d9185947'];
        const shapes = ['50%'];
        
        function createBubble() {
            const bubble = document.createElement('div');
            bubble.className = 'bubble';
            
            // Random properties
            const size = Math.random() * 40 + 50;
            const posX = Math.random() * 100;
            const duration = Math.random() * 25 + 10;
            const delay = Math.random() * 4;
            const color = colors[Math.floor(Math.random() * colors.length)];
            const shape = shapes[Math.floor(Math.random() * shapes.length)];
            
            // Apply styles
            bubble.style.width = `${size}px`;
            bubble.style.height = `${size}px`;
            bubble.style.left = `${posX}%`;
            bubble.style.background = color;
            bubble.style.borderRadius = shape;
            bubble.style.animationDuration = `${duration}s`;
            bubble.style.animationDelay = `${delay}s`;
            
            bubblesContainer.appendChild(bubble);
            
            // Remove bubble after animation completes
            setTimeout(() => {
                bubble.remove();
            }, duration * 2000);
        }
        
        // Create initial bubbles
        for (let i = 0; i < 25; i++) {
            createBubble();
        }
        
        // Add new bubbles periodically
        setInterval(createBubble, 2000);
    });
</script>
</body>
</html>