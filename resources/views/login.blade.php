<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aplu Push - Login</title>

    <meta name="keywords" content="push notifications, push notification service, push.apu.io, Aplu Push, notification delivery, real-time notifications, mobile notifications, web notifications, user engagement, notification platform, push notification SEO, Aplu Push service">
    <meta name="description" content="Aplu Push offers a powerful push notification service through push.apu.io, providing reliable real-time notifications for both mobile and web platforms. Improve user engagement and communication with our advanced notification platform.">
    <meta property="og:image" content="{{ asset('images/aplu.png') }}" />
    <meta property="og:image:secure_url" content="{{ asset('images/aplu.png') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicon_io/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon_io/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon_io/favicon-16x16.png') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon.ico') }}">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('css/main.css') }}" rel="stylesheet">
    <link href="{{ asset('css/responsive.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/iziToast.css') }}">
    <style>
     
        .gradient-bg {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }
        
        .auth-illustration {
            max-width: 100%;
            height: auto;
            animation: float 6s ease-in-out infinite;
        }
        
       
          
       
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--dark-gray);
        }
        
        .password-wrapper {
            position: relative;
        }
        
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }
        
       
        .footer-links a {
            color: var(--dark-gray);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: var(--primary-color);
        }
        
    
    </style>
</head>

<body class="h-100">
    <div class="container-fluid h-100">
        <div class="row h-100 align-items-center justify-content-center">
            <div class="col-xl-4 col-lg-5 col-md-8 col-sm-10">
                <div class="card p-3 p-md-5">
                    <div class="text-center mb-4">
                        <a href="{{ url('/') }}">
                            <img src="{{ asset('images/logo-full.png') }}" alt="Aplu Push" class="img-fluid" style="height: 72px;">
                        </a>
                        <h2 class="mt-3 font-weight-bold">Welcome Back</h2>
                        <p class="text-muted">Sign in to continue to your Aplu Push account</p>
                    </div>
                    
                    <form action="{{route('login.doLogin')}}" method="POST"  id="login">
                        @csrf
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" name="email" placeholder="Enter your email">
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="password-wrapper">
                                <input type="password" name="password" class="form-control" id="password" placeholder="Enter your password">
                                <span class="password-toggle" id="togglePassword">
                                    <i class="far fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="remember_me" id="remember_check" value="1" checked>
                                <label class="form-check-label" for="remember_check">Remember me</label>
                            </div>
                            <a href="#" class="text-primary small">Forgot password?</a>
                        </div>
                       
                        <div class="d-grid gap-2 mb-3">
                            <button type="submit" class="btn btn-primary">Sign In</button>
                        </div>
                        
                        
                        
                      
                    </form>
                </div>
                
                <div class="text-center mt-3">
                    <p class="text-muted mb-0 footer-links">
                        &copy; {{ now()->year }} Aplu Push. 
                        <a href="https://aplu.io/terms-conditions/" target="_blank">Terms</a> | 
                        <a href="https://aplu.io/privacy-policy/" target="_blank">Privacy</a>
                    </p>
                </div>
            </div>
            
            <div class="col-xl-7 col-lg-6 d-none d-lg-block">
                <div class="p-5 text-center">
                    <img src="https://aplu.io/assets/images/aplu-image-1.png" alt="Login illustration" class="auth-illustration">
                    <h3 class="mt-4">Powerful Push Notifications</h3>
                    <p class="text-muted">Engage your users with real-time notifications delivered through our reliable platform.</p>
                    <div class="mt-4">
                        <div class="d-flex justify-content-center">
                            <div class="mx-3 text-center">
                                <i class="fas fa-bolt text-primary mb-2" style="font-size: 2rem;"></i>
                                <h5>Fast Delivery</h5>
                                <p class="text-muted small">Instant notifications to your users</p>
                            </div>
                            <div class="mx-3 text-center">
                                <i class="fas fa-chart-line text-primary mb-2" style="font-size: 2rem;"></i>
                                <h5>Analytics</h5>
                                <p class="text-muted small">Track engagement and performance</p>
                            </div>
                            <div class="mx-3 text-center">
                                <i class="fas fa-shield-alt text-primary mb-2" style="font-size: 2rem;"></i>
                                <h5>Secure</h5>
                                <p class="text-muted small">Enterprise-grade security</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('vendor/global/global.min.js') }}"></script>
    <script src="{{ asset('js/custom.min.js') }}"></script>
    <script src="{{ asset('js/deznav-init.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/jquery.validate.min.js"></script>
    <script src="{{ asset('js/iziToast.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Password toggle functionality
            const togglePassword = document.querySelector('#togglePassword');
            const password = document.querySelector('#password');
            
            togglePassword.addEventListener('click', function() {
                // Toggle the type attribute
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                
                // Toggle the eye icon
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
            
            // Form validation
            $("#login").validate({
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
                        required: "Email is required", 
                        email: "Enter a valid email address" 
                    },
                    password: { 
                        required: "Password is required",
                        minlength: "Password must be at least 6 characters"
                    }
                },
                errorElement: "div",
                errorPlacement: function(error, element) {
                    error.addClass("invalid-feedback");
                    if (element.prop("type") === "checkbox") {
                        error.insertAfter(element.parent("label"));
                    } else {
                        error.insertAfter(element);
                    }
                },
                highlight: function(element, errorClass, validClass) {
                    $(element).addClass("is-invalid").removeClass("is-valid");
                },
                unhighlight: function(element, errorClass, validClass) {
                    $(element).removeClass("is-invalid").addClass("is-valid");
                },
                submitHandler: function(form) {
                    // Add loading state to button
                    const submitButton = $(form).find("button[type='submit']");
                    submitButton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Signing in...');
                    submitButton.prop('disabled', true);
                    
                    // Simulate form submission (replace with actual AJAX call)
                    setTimeout(function() {
                        form.submit();
                    }, 1500);
                }
            });
        });
    </script>
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
</body>
</html>