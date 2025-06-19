<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="" />
    <meta name="author" content="" />
    <meta name="robots" content="" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aplu Push</title>

    <meta name="description" content="Fastest Push Notification Service in India" />
    <meta name="robots" content="follow, index, max-snippet:-1, max-video-preview:-1, max-image-preview:large" />
    <link rel="canonical" href="https://aplu.io/" />
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="Push Notification Service" />
    <meta property="og:description" content="Fastest Push Notification Service in India" />
    <meta property="og:url" content="https://aplu.io/" />
    <meta property="og:site_name" content="APLU" />
    <meta property="og:updated_time" content="2024-07-08T12:59:03+05:30" />
    <meta property="og:image" content="{{asset('images/aplu.png')}}" />
    <meta property="og:image:secure_url" content="{{asset('images/aplu.png')}}" />
    <meta property="og:image:width" content="1200" />
    <meta property="og:image:height" content="675" />
    <meta property="og:image:alt" content="Aplu Login Page" />
    <meta property="og:image:type" content="image/jpeg" />

    <!-- FAVICONS ICON -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicon_io/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon_io/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon_io/favicon-16x16.png') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('/images/favicon.ico') }}">
    <link href="{{ asset('vendor/datatables/css/jquery.dataTables.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/jqueryui/css/jquery-ui.min.css') }}">
    <link href="{{ asset('css/multiselect.css') }}" rel="stylesheet">
    <!-- Style css -->
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('css/main.css') }}" rel="stylesheet">
    <link href="{{ asset('css/responsive.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/iziToast.css') }}">

     @stack('styles')


</head>

<body>
    <div id="preloader">
        <div class="gooey">
            <span class="dot"></span>
            <div class="dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </div>
    <div id="main-wrapper">
        <div class="nav-header">
            <a href="#" class="brand-logo main-logo">
                <img src="{{ asset('images/logo-main.png') }}" alt="main-logo" class="img-fluid header-desklogo" />
                <img src="{{ asset('images/logo.png') }}" alt="main-logo" class="img-fluid header-moblogo" />
            </a>
            <div class="nav-control">
                <div class="hamburger">
                    <span class="line"></span><span class="line"></span><span class="line"></span>
                </div>
            </div>
        </div>
        {{-- <div class="chatbox">
            <div class="chatbox-close"></div>
            <div class="card mb-sm-3 mb-md-0 contacts_card">
                <div class="card-header chat-list-header text-center">
                    <h6 class="mb-1"><b>📢 Announcement</b></h6>
                    <a href="javascript:void(0);" class="notice-close-btn"><i class="fal fa-times"></i></a>
                </div>
                <div class="card-body contacts_body scrollbar p-0">
                    <ul class="contacts" id="noticBar">
                    </ul>
                </div>
            </div>
        </div> --}}
        <div class="header">
            <div class="header-content">
                <nav class="navbar navbar-expand">
                    <div class="collapse navbar-collapse justify-content-between">
                        <div class="header-left">
                            <div class="nav-item">
                            </div>
                        </div>
                        <ul class="navbar-nav header-right">
                            <li class="nav-item recipe">
                                <a href="{{route('domain.view')}}" class="btn btn-secondary btn-rounded">
                                    <i class="fas fa-plus pe-2"></i>Domain
                                </a>
                            </li>
                            <li class="nav-item recipe">
                                <a href="{{route('notification.create')}}" class="btn btn-primary btn-rounded">
                                    <i class="fas fa-paper-plane pe-2"></i>Send
                                </a>
                            </li>
                            {{-- <li class="nav-item dropdown notification_dropdown">
                                <a class="nav-link bell-link ai-icon" href="javascript:void(0);" id="notificationIcon">
                                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M22.75 15.8385V13.0463C22.7471 10.8855 21.9385 8.80353 20.4821 7.20735C19.0258 5.61116 17.0264 4.61555 14.875 4.41516V2.625C14.875 2.39294 14.7828 2.17038 14.6187 2.00628C14.4546 1.84219 14.2321 1.75 14 1.75C13.7679 1.75 13.5454 1.84219 13.3813 2.00628C13.2172 2.17038 13.125 2.39294 13.125 2.625V4.41534C10.9736 4.61572 8.97429 5.61131 7.51794 7.20746C6.06159 8.80361 5.25291 10.8855 5.25 13.0463V15.8383C4.26257 16.0412 3.37529 16.5784 2.73774 17.3593C2.10019 18.1401 1.75134 19.1169 1.75 20.125C1.75076 20.821 2.02757 21.4882 2.51969 21.9803C3.01181 22.4724 3.67904 22.7492 4.375 22.75H9.71346C9.91521 23.738 10.452 24.6259 11.2331 25.2636C12.0142 25.9013 12.9916 26.2497 14 26.2497C15.0084 26.2497 15.9858 25.9013 16.7669 25.2636C17.548 24.6259 18.0848 23.738 18.2865 22.75H23.625C24.321 22.7492 24.9882 22.4724 25.4803 21.9803C25.9724 21.4882 26.2492 20.821 26.25 20.125C26.2486 19.117 25.8998 18.1402 25.2622 17.3594C24.6247 16.5786 23.7374 16.0414 22.75 15.8385ZM7 13.0463C7.00232 11.2113 7.73226 9.45223 9.02974 8.15474C10.3272 6.85726 12.0863 6.12732 13.9212 6.125H14.0788C15.9137 6.12732 17.6728 6.85726 18.9703 8.15474C20.2677 9.45223 20.9977 11.2113 21 13.0463V15.75H7V13.0463ZM14 24.5C13.4589 24.4983 12.9316 24.3292 12.4905 24.0159C12.0493 23.7026 11.716 23.2604 11.5363 22.75H16.4637C16.284 23.2604 15.9507 23.7026 15.5095 24.0159C15.0684 24.3292 14.5411 24.4983 14 24.5ZM23.625 21H4.375C4.14298 20.9999 3.9205 20.9076 3.75644 20.7436C3.59237 20.5795 3.50014 20.357 3.5 20.125C3.50076 19.429 3.77757 18.7618 4.26969 18.2697C4.76181 17.7776 5.42904 17.5008 6.125 17.5H21.875C22.571 17.5008 23.2382 17.7776 23.7303 18.2697C24.2224 18.7618 24.4992 19.429 24.5 20.125C24.4999 20.357 24.4076 20.5795 24.2436 20.7436C24.0795 20.9076 23.857 20.9999 23.625 21Z" fill="#9B9B9B"></path>
                                    </svg>
                                    <span class="badge light text-white bg-primary rounded-circle" id="unReadCount">0</span>
                                </a>
                            </li> --}}
                           @php
                                $defaultAvatar = asset('images/user.png');
                                $userAvatar = Auth::user() && Auth::user()->image
                                    ? asset(Auth::user()->image)
                                    : $defaultAvatar;
                                // If the file exists, use its modified time to bust cache:
                                $timestamp = (Auth::user() && Auth::user()->image && file_exists(public_path(Auth::user()->image)))
                                    ? filemtime(public_path(Auth::user()->image))
                                    : now()->timestamp;
                            @endphp

                            <li class="nav-item dropdown header-profile">
                                <a class="nav-link" href="#" role="button" data-bs-toggle="dropdown">
                                    <img
                                    src="{{ $userAvatar }}?v={{ $timestamp }}"
                                    width="56"
                                    alt="User Avatar"
                                    class="rounded-circle"
                                    />
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a href="{{ route('user.profile') }}" class="dropdown-item ai-icon">
                                        <i class="far fa-user text-primary"></i>
                                        <span class="ms-1">Profile</span>
                                    </a>
                                    <a 
                                    class="dropdown-item ai-icon text-danger" 
                                    href="" 
                                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                                    >
                                        <i class="far fa-sign-out text-danger"></i>
                                        <span class="ms-1">Logout</span>
                                    </a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>

                        </ul>
                    </div>
                </nav>
            </div>
        </div>
     <div class="deznav">
    <div class="deznav-scroll">
   <ul class="metismenu" id="menu">
    <!-- Dashboard -->
    <li>
        <a class="ai-icon" href="{{ route('dashboard') }}" aria-expanded="false">
            <i class="far fa-tachometer-alt-slowest"></i>
            <span class="nav-text">Dashboard</span>
        </a>
    </li>

    <!-- Domains -->
    <li>
        <a class="ai-icon" href="{{ route('domain.view') }}" aria-expanded="false">
            <i class="fal fa-globe"></i>
            <span class="nav-text">Domains</span>
        </a>
    </li>

    <!-- Notifications -->
    <li>
        <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false">
            <i class="fal fa-bell"></i>
            <span class="nav-text">Notifications</span>
        </a>
        <ul aria-expanded="false">
            <li><a href="{{ route('notification.create') }}">Send Notification</a></li>
            <li><a href="{{ route('notification.view') }}">Reports</a></li>
        </ul>
    </li>
 <!-- Segments  ← new -->
    <li>
        <a class="ai-icon" href="{{ route('segmentation.view') }}" aria-expanded="false">
            <i class="fal fa-layer-group"></i>
            <span class="nav-text">Segmentation</span>
        </a>
    </li>
    <!-- Subscriptions -->
    <li>
        <a class="ai-icon" href="{{ route('user.subscription') }}" aria-expanded="false">
            <i class="fal fa-credit-card"></i>
            <span class="nav-text">Subscriptions</span>
        </a>
    </li>
    {{-- Core app menus --}}
    @foreach (\Module::allEnabled() as $module)
        @includeIf(strtolower($module->getLowerName()) . '::partials.menu')
    @endforeach

    <!-- Addons -->
    <li>
        <a class="ai-icon" href="{{ route('addons.view') }}" aria-expanded="false">
            <i class="fal fa-credit-card"></i>
            <span class="nav-text">Addons</span>
        </a>
    </li>

    <!-- Settings -->
    <li>
        <a class="has-arrow ai-icon" href="javascript:void(0)" aria-expanded="false">
            <i class="fal fa-cog"></i>
            <span class="nav-text">Settings</span>
        </a>
        <ul aria-expanded="false">
            <li><a href="{{ route('settings.general') }}">General</a></li>
            <li><a href="{{ route('settings.email') }}">Email</a></li>
            <li><a href="{{ route('settings.server-info') }}">Server Info</a></li>
            <li><a href="{{ route('settings.utilities') }}">Utilities</a></li>
            <li><a href="{{ route('settings.upgrade') }}">Upgrade</a></li>
            <li><a href="{{ route('settings.firebase-setup') }}">Firebase Setup</a></li>
            <li><a href="{{ route('settings.backup-subscribers') }}">Backup Subscribers</a></li> 
        </ul>
    </li>

    
    <!-- push config -->
    <li>
        <a class="ai-icon" href="{{ route('settings.push.show') }}" aria-expanded="false">
            <i class="fal fa-credit-card"></i>
            <span class="nav-text">Push Config</span>
        </a>
    </li>

    <!-- Support -->
    <li>
        <a href="https://aplu.io/contact" class="ai-icon" aria-expanded="false" target="_blank">
            <i class="fal fa-user-headset"></i>
            <span class="nav-text">Support</span>
        </a>
    </li>
</ul>

        <div class="plus-box">
					<img src="{{ asset('images/plus.png') }}" alt="">
					<h5 class="fs-18 font-w700">Add Menus</h5>
					<p class="fs-14 font-w400">Manage your food <br>and beverages menus<i class="fas fa-arrow-right ms-3"></i></p>
				</div>
    </div>
</div>

        @yield('content')
        <div class="footer sticky-bottom">
            <div class="copyright">
                <p>Copyright © {{ date('Y') }} <a href="https://applaudwebmedia.com/" target="_blank">Applaud Web Media PVT. LTD.</a></p>
            </div>
        </div>
        <div class="whatsapp-icon">
            <a href="https://api.whatsapp.com/send/?phone=919997526894&text=Hi%2C+I+need+help+with+Aplu+Push.&type=phone_number&app_absent=0" target="_blank">
                <img src="{{ asset('images/whatsapp.gif') }}" alt="" class="img-fluid">
            </a>
        </div>
    </div>

    <!-- Required vendors -->
    <script src="{{ asset('vendor/global/global.min.js') }}"></script>
    <script src="{{ asset('vendor/jqueryui/js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('vendor/moment/moment.min.js') }}"></script>
    <!-- Datatable -->
    <script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/plugins-init/datatables.init.js') }}"></script>
    <!-- multiselect -->
    <script src="{{ asset('js/multiselect.js') }}"></script>
    <!-- uipluploaded -->
    <script src="{{ asset('js/custom.min.js') }}"></script>
    <script src="{{ asset('js/deznav-init.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/jquery.validate.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/additional-methods.min.js"></script>

    <script src="{{ asset('js/iziToast.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
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
    @if ($errors->any())
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
