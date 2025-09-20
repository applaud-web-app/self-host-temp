{{-- resources/views/campaigns/reports_dummy.blade.php --}}
@extends('layouts.master')


@push('styles')
    <!-- Bootstrap Date Range Picker CSS -->
    <link rel="stylesheet" href="{{ asset('/vendor/bootstrap-daterangepicker/daterangepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
    <style>
        #hiddenSelect {
            display: none;
        }

        .invalid-feedback {
            display: none;
            font-size: 0.875em;
            color: #dc3545;
        }

        .is-invalid+.invalid-feedback {
            display: block;
        }

        .filter-group .form-control {
            max-width: 200px;
        }

        .table-responsive {
            margin-top: 1rem;
        }

        /* Modal enhancements */
        #reportModal .modal-header {
            border-bottom: 1px solid #e9ecef;
        }

        #reportModal .modal-footer {
            border-top: 1px solid #e9ecef;
        }

        /* Notification preview styling */
        .windows_view {
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .windows_view:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }

        /* Text truncation */
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

    </style>
@endpush

@section('content')
<section class="content-body view_notification_page">
    <div class="container-fluid">
        <!-- Header Section -->
        <div class="d-flex flex-wrap align-items-center text-head">
            <h2 class="mb-3 me-auto">Campaign Reports</h2>
            <div class="mb-3">
                <a href="{{ route('migrate.send-notification') }}" class="btn btn-primary">
                    <i class="far fa-plus-circle me-2"></i> Add New
                </a>
            </div>
        </div>

        <!-- Static Filter Form -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card h-auto mb-3">
                    <div class="card-body p-3">
                        <form id="filterForm">
                            <div class="row g-2 align-items-end">
                                <!-- Campaign Name Filter -->
                                <div class="col-xl-3 col-md-6">
                                    <div class="position-relative">
                                        <input type="text" class="form-control" name="campaign_name"
                                            id="filter_campaign_name" placeholder="Search Campaign Name...">
                                        <div class="invalid-feedback"></div>
                                        <i class="far fa-search text-primary position-absolute top-50 translate-middle-y" style="right: 10px;"></i>
                                    </div>
                                </div>

                                <!-- Status Filter -->
                                <div class="col-xl-2 col-md-6">
                                    <select class="form-control form-select" id="filter_status" name="status">
                                        <option value="">Select Status</option>
                                        <option value="sent">Sent</option>
                                        <option value="queued">Processing</option>
                                        <option value="pending">Pending</option>
                                        <option value="failed">Failed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <!-- Domain Filter -->
                                <div class="col-xl-3 col-md-6">
                                    <select class="form-select filter_site_web form-control" id="filter_domain" name="site_web" style="width:100%"></select>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <!-- Last Send Date Range Filter -->
                                <div class="col-xl-2 col-md-6">
                                    <input type="text" class="form-control" id="daterange" name="last_send" readonly placeholder="Select date range">
                                    <div class="invalid-feedback"></div>
                                </div>

                                <!-- Reset Button -->
                                <div class="col-xl-2 col-md-6 text-end">
                                    <button type="button" id="resetBtn" class="btn btn-danger light w-100" title="Click here to remove filter">
                                        <i class="fas fa-undo me-1"></i> Reset
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Campaign Type Radio Buttons -->
            <div class="col-lg-12">
                <div class="custom-radio justify-content-start">
                    <label class="mb-2 mb-lg-3 w-auto d-inline-block" for="campaign_type_all">
                        <input type="radio" name="campaign_type" id="campaign_type_all" value="all" checked>
                        <span>All</span>
                    </label>
                    <label class="mb-2 mb-lg-3 w-auto d-inline-block" for="campaign_type_push">
                        <input type="radio" name="campaign_type" id="campaign_type_push" value="push">
                        <span>Web Push</span>
                    </label>
                    <label class="mb-2 mb-lg-3 w-auto d-inline-block" for="campaign_type_email">
                        <input type="radio" name="campaign_type" id="campaign_type_email" value="email">
                        <span>Email</span>
                    </label>
                    <label class="mb-2 mb-lg-3 w-auto d-inline-block" for="campaign_type_sms">
                        <input type="radio" name="campaign_type" id="campaign_type_sms" value="sms">
                        <span>SMS</span>
                    </label>
                </div>
            </div>

            <!-- Static DataTable -->
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body p-3" id="tableData">
                        <div class="table-responsive">
                            <table class="table display" id="datatable" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>S.No</th>
                                        <th>Campaign Name</th>
                                        <th>Domain</th>
                                        <th>Status</th>
                                        <th>Sent Time</th>
                                        <th>Clicks</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Campaign Modal -->
        <div class="modal fade" id="reportModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title d-flex align-items-center">
                            <i class="fas fa-bullhorn text-primary me-2"></i>
                            <span id="campaign_name" class="text-truncate" style="max-width: 80%"></span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body position-relative p-0">
                        <!-- Spinner overlay -->
                        <div id="modalSpinner"
                             class="position-absolute top-0 bottom-0 start-0 end-0 d-flex justify-content-center align-items-center bg-white bg-opacity-75">
                            <div class="spinner-border text-primary"></div>
                        </div>

                        <!-- Content -->
                        <div id="modalContent" class="d-none p-4">
                            <div class="row">
                                <!-- Analytics Section -->
                                <div id="analyticsSection" class="col-lg-6 mb-4 mb-lg-0">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0"><i class="fas fa-chart-pie text-primary me-2"></i>Performance Analytics</h6>
                                        <div class="badge bg-primary rounded-pill">Live</div>
                                    </div>
                                    <div id="chart" class="border rounded p-3 bg-light"></div>
                                </div>

                                <!-- Notification Preview -->
                                <div class="col-lg-6">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <h6 class="mb-0"><i class="fas fa-bell text-primary me-2"></i>Push Preview</h6>
                                        <div class="d-flex align-items-center mb-3">
                                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/8/87/Google_Chrome_icon_%282011%29.png/48px-Google_Chrome_icon_%282011%29.png" class="me-1" alt="Browser Icon" height="18">
                                            <span class="text-muted small">Chrome</span>
                                        </div>
                                    </div>

                                    <div class="windows_view border rounded p-3 bg-white">
                                        <!-- Banner Image -->
                                        <img id="message_image" class="img-fluid rounded mb-3"
                                             style="height:199px;width:100%;object-fit:cover" alt="Campaign Image">

                                        <!-- Notification Content -->
                                        <div class="d-flex">
                                            <img id="icon_prv" style="height:40px;width:40px;object-fit:cover"
                                                 class="rounded me-3" alt="Icon">
                                            <div class="flex-grow-1" style="min-width:0">
                                                <div class="text-truncate fw-bold" id="prv_title" title=""></div>
                                                <div class="text-muted small mb-2 line-clamp-2" id="prv_desc"></div>
                                                <a href="#" target="_blank"
                                                   class="text-primary small text-truncate d-block" id="prv_link"></a>
                                            </div>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="row g-2 mt-3">
                                            <div class="col-6 d-none" id="btn_prv">
                                                <a target="_blank" class="text-decoration-none">
                                                    <span id="btn_title1"
                                                          class="btn btn-sm btn-outline-primary w-100 text-truncate"></span>
                                                </a>
                                            </div>
                                            <div class="col-6 d-none" id="btn2_prv">
                                                <a target="_blank" class="text-decoration-none">
                                                    <span id="btn_title2"
                                                          class="btn btn-sm btn-outline-secondary w-100 text-truncate"></span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div><!-- /Preview -->
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-sm btn-outline-secondary py-2" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/bootstrap-daterangepicker/daterangepicker.js') }}"></script>
    <script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Dummy data injected from Blade (pure front-end demo) --}}
   @php
    $dummyDomains = ['example.com','shop.example.com','newsportal.io','myapp.io','blogspace.dev','acme.co','contoso.net'];

    $dummyCampaigns = [
        ['id'=>1,'type'=>'push','campaign_name'=>'Weekend Flash Sale','domain'=>'shop.example.com','status'=>'sent','sent_time'=>'2025-09-10 14:25','clicks'=>1850],
        ['id'=>2,'type'=>'email','campaign_name'=>'September Newsletter','domain'=>'example.com','status'=>'pending','sent_time'=>'2025-09-20 09:00','clicks'=>0],
        ['id'=>3,'type'=>'sms','campaign_name'=>'OTP Blast – Login','domain'=>'myapp.io','status'=>'queued','sent_time'=>'2025-09-18 11:30','clicks'=>0],
        ['id'=>4,'type'=>'push','campaign_name'=>'Breaking: Product Drop','domain'=>'newsportal.io','status'=>'failed','sent_time'=>'2025-09-15 08:05','clicks'=>0],
        ['id'=>5,'type'=>'push','campaign_name'=>'Price Drop Alert','domain'=>'acme.co','status'=>'sent','sent_time'=>'2025-09-12 18:00','clicks'=>742],
        ['id'=>6,'type'=>'email','campaign_name'=>'Onboarding Tips','domain'=>'example.com','status'=>'sent','sent_time'=>'2025-08-28 10:20','clicks'=>621],
        ['id'=>7,'type'=>'push','campaign_name'=>'Welcome Back!','domain'=>'blogspace.dev','status'=>'cancelled','sent_time'=>'2025-09-11 16:10','clicks'=>0],
        ['id'=>8,'type'=>'sms','campaign_name'=>'Delivery ETA','domain'=>'acme.co','status'=>'sent','sent_time'=>'2025-09-13 12:00','clicks'=>95],
        ['id'=>9,'type'=>'push','campaign_name'=>'Cart Reminder','domain'=>'shop.example.com','status'=>'sent','sent_time'=>'2025-09-14 20:40','clicks'=>1304],
        ['id'=>10,'type'=>'email','campaign_name'=>'Reactivation Win-back','domain'=>'contoso.net','status'=>'sent','sent_time'=>'2025-09-01 07:30','clicks'=>402],
        ['id'=>11,'type'=>'push','campaign_name'=>'Holiday Mega Sale','domain'=>'shop.example.com','status'=>'sent','sent_time'=>'2025-09-05 09:15','clicks'=>2100],
        ['id'=>12,'type'=>'email','campaign_name'=>'Product Updates','domain'=>'example.com','status'=>'sent','sent_time'=>'2025-09-06 11:00','clicks'=>350],
        ['id'=>13,'type'=>'sms','campaign_name'=>'2FA Codes','domain'=>'myapp.io','status'=>'sent','sent_time'=>'2025-09-07 14:50','clicks'=>65],
        ['id'=>14,'type'=>'push','campaign_name'=>'Breaking News Alert','domain'=>'newsportal.io','status'=>'sent','sent_time'=>'2025-09-08 07:30','clicks'=>980],
        ['id'=>15,'type'=>'push','campaign_name'=>'Clearance Deals','domain'=>'acme.co','status'=>'pending','sent_time'=>'2025-09-09 16:45','clicks'=>0],
        ['id'=>16,'type'=>'email','campaign_name'=>'Weekly Digest','domain'=>'blogspace.dev','status'=>'sent','sent_time'=>'2025-09-03 08:10','clicks'=>220],
        ['id'=>17,'type'=>'push','campaign_name'=>'Breaking Weather Alert','domain'=>'newsportal.io','status'=>'failed','sent_time'=>'2025-09-02 12:20','clicks'=>0],
        ['id'=>18,'type'=>'sms','campaign_name'=>'Delivery Confirmation','domain'=>'acme.co','status'=>'sent','sent_time'=>'2025-09-04 13:00','clicks'=>77],
        ['id'=>19,'type'=>'push','campaign_name'=>'Cart Abandonment Reminder','domain'=>'shop.example.com','status'=>'sent','sent_time'=>'2025-09-10 20:40','clicks'=>845],
        ['id'=>20,'type'=>'email','campaign_name'=>'Customer Survey','domain'=>'contoso.net','status'=>'queued','sent_time'=>'2025-09-15 09:20','clicks'=>0],
    ];

    $dummyReports = [
        1 => [
            'title'=>'Weekend Flash Sale',
            'description'=>"Up to 70% off on select items. Today only!",
            'link'=>'https://shop.example.com/sale',
            'banner_image'=>'https://images.unsplash.com/photo-1542834369-f10ebf06d3cb?q=80&w=1600&auto=format&fit=crop',
            'banner_icon'=>'https://api.iconify.design/mdi-light:cart.svg?color=%230d6efd',
            'btns'=>[['title'=>'Shop Now','url'=>'https://shop.example.com/sale'],['title'=>'View Details','url'=>'https://shop.example.com/faq']],
            'analytics'=>['sent'=>25000,'received'=>18200,'clicked'=>1850],
        ],
        2 => [
            'title'=>'September Newsletter',
            'description'=>"What's new this month across our products.",
            'link'=>'https://example.com/newsletter',
            'banner_image'=>'https://images.unsplash.com/photo-1515378791036-0648a3ef77b2?q=80&w=1600&auto=format&fit=crop',
            'banner_icon'=>'https://api.iconify.design/mdi-light:email.svg?color=%230d6efd',
            'btns'=>[['title'=>'Read','url'=>'https://example.com/newsletter']],
            'analytics'=>['sent'=>0,'received'=>0,'clicked'=>0],
        ],
        3 => [
            'title'=>'OTP Blast – Login',
            'description'=>'Security verification codes for user logins.',
            'link'=>'https://myapp.io',
            'banner_image'=>'https://images.unsplash.com/photo-1515879218367-8466d910aaa4?q=80&w=1600&auto=format&fit=crop',
            'banner_icon'=>'https://api.iconify.design/mdi-light:shield.svg?color=%230d6efd',
            'btns'=>[],
            'analytics'=>['sent'=>12000,'received'=>11920,'clicked'=>40],
        ],
        4 => [
            'title'=>'Product Drop Failed',
            'description'=>'Delivery service outage at 8:00 UTC.',
            'link'=>'https://newsportal.io/status',
            'banner_image'=>'https://images.unsplash.com/photo-1548095115-45697e51339e?q=80&w=1600&auto=format&fit=crop',
            'banner_icon'=>'https://api.iconify.design/mdi-light:alert.svg?color=%23e74a3b',
            'btns'=>[['title'=>'Status Page','url'=>'https://newsportal.io/status']],
            'analytics'=>['sent'=>15000,'received'=>2000,'clicked'=>15],
        ],
        5 => [
            'title'=>'Price Drop Alert',
            'description'=>'The item you viewed is now cheaper!',
            'link'=>'https://acme.co/deals',
            'banner_image'=>'https://images.unsplash.com/photo-1516637090014-cb1ab0d08fc7?q=80&w=1600&auto=format&fit=crop',
            'banner_icon'=>'https://api.iconify.design/mdi-light:tag.svg?color=%230d6efd',
            'btns'=>[['title'=>'See Deals','url'=>'https://acme.co/deals']],
            'analytics'=>['sent'=>10000,'received'=>8700,'clicked'=>742],
        ],
        6 => [
            'title'=>'Onboarding Tips',
            'description'=>'Make the most out of your account in 5 steps.',
            'link'=>'https://example.com/welcome',
            'banner_image'=>'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?q=80&w=1600&auto=format&fit=crop',
            'banner_icon'=>'https://api.iconify.design/mdi-light:account.svg?color=%230d6efd',
            'btns'=>[['title'=>'Get Started','url'=>'https://example.com/welcome']],
            'analytics'=>['sent'=>30000,'received'=>22800,'clicked'=>621],
        ],
        7 => [
            'title'=>'Welcome Back! (Cancelled)',
            'description'=>"We missed you—come see what's new.",
            'link'=>'https://blogspace.dev',
            'banner_image'=>'https://images.unsplash.com/photo-1517504734587-2890819debab?q=80&w=1600&auto=format&fit=crop',
            'banner_icon'=>'https://api.iconify.design/mdi-light:bell.svg?color=%230d6efd',
            'btns'=>[],
            'analytics'=>['sent'=>0,'received'=>0,'clicked'=>0],
        ],
        8 => [
            'title'=>'Delivery ETA',
            'description'=>'Your package will arrive today between 4–6 PM.',
            'link'=>'https://acme.co/orders/123',
            'banner_image'=>'https://images.unsplash.com/photo-1515168833906-d2a3b82b302a?q=80&w=1600&auto=format&fit=crop',
            'banner_icon'=>'https://api.iconify.design/mdi-light:truck.svg?color=%230d6efd',
            'btns'=>[],
            'analytics'=>['sent'=>5000,'received'=>4800,'clicked'=>95],
        ],
        9 => [
            'title'=>'Cart Reminder',
            'description'=>'Forgot something? Your cart misses you.',
            'link'=>'https://shop.example.com/cart',
            'banner_image'=>'https://images.unsplash.com/photo-1491553895911-0055eca6402d?q=80&w=1600&auto=format&fit=crop',
            'banner_icon'=>'https://api.iconify.design/mdi-light:cart-outline.svg?color=%230d6efd',
            'btns'=>[['title'=>'Checkout','url'=>'https://shop.example.com/cart']],
            'analytics'=>['sent'=>22000,'received'=>17150,'clicked'=>1304],
        ],
        10 => [
            'title'=>'Reactivation Win-back',
            'description'=>'We saved a seat for you—come back!',
            'link'=>'https://contoso.net/reactivate',
            'banner_image'=>'https://images.unsplash.com/photo-1492724441997-5dc865305da7?q=80&w=1600&auto=format&fit=crop',
            'banner_icon'=>'https://api.iconify.design/mdi-light:account-check.svg?color=%230d6efd',
            'btns'=>[['title'=>'Reactivate','url'=>'https://contoso.net/reactivate']],
            'analytics'=>['sent'=>18000,'received'=>15020,'clicked'=>402],
        ],
        11 => [
            'title'=>'Holiday Mega Sale',
            'description'=>"Huge savings this holiday season.",
            'link'=>'https://shop.example.com/holiday',
            'banner_image'=>'https://images.unsplash.com/photo-1607083207069-56f1f8c2e5b9?q=80&w=1600&auto=format&fit=crop',
            'banner_icon'=>'https://api.iconify.design/mdi-light:gift.svg?color=%230d6efd',
            'btns'=>[['title'=>'Shop Now','url'=>'https://shop.example.com/holiday']],
            'analytics'=>['sent'=>27000,'received'=>19000,'clicked'=>2100],
        ],
        12 => [
            'title'=>'Product Updates',
            'description'=>"See what's new in our platform this week.",
            'link'=>'https://example.com/updates',
            'banner_image'=>'https://images.unsplash.com/photo-1523475496153-3d6ccf0b76d9?q=80&w=1600&auto=format&fit=crop',
            'banner_icon'=>'https://api.iconify.design/mdi-light:update.svg?color=%230d6efd',
            'btns'=>[['title'=>'Read More','url'=>'https://example.com/updates']],
            'analytics'=>['sent'=>15000,'received'=>12000,'clicked'=>350],
        ],
        13 => [
            'title'=>'2FA Codes',
            'description'=>'Your login security codes.',
            'link'=>'https://myapp.io/2fa',
            'banner_image'=>'https://images.unsplash.com/photo-1605902711622-cfb43c4437f0?q=80&w=1600&auto=format&fit=crop',
            'banner_icon'=>'https://api.iconify.design/mdi-light:shield-key.svg?color=%230d6efd',
            'btns'=>[],
            'analytics'=>['sent'=>6000,'received'=>5900,'clicked'=>65],
        ],
        14 => [
            'title'=>'Breaking News Alert',
            'description'=>'Stay updated with the latest headlines.',
            'link'=>'https://newsportal.io/latest',
            'banner_image'=>'https://images.unsplash.com/photo-1504711434969-e33886168f5c?q=80&w=1600&auto=format&fit=crop',
            'banner_icon'=>'https://api.iconify.design/mdi-light:newspaper.svg?color=%230d6efd',
            'btns'=>[['title'=>'Read Now','url'=>'https://newsportal.io/latest']],
            'analytics'=>['sent'=>12000,'received'=>11000,'clicked'=>980],
        ],
        15 => [
            'title'=>'Clearance Deals',
            'description'=>'Massive discounts while stocks last.',
            'link'=>'https://acme.co/clearance',
            'banner_image'=>'https://images.unsplash.com/photo-1580910051074-cf3f2370d6f9?q=80&w=1600&auto=format&fit=crop',
            'banner_icon'=>'https://api.iconify.design/mdi-light:tag-multiple.svg?color=%230d6efd',
            'btns'=>[['title'=>'Shop Clearance','url'=>'https://acme.co/clearance']],
            'analytics'=>['sent'=>0,'received'=>0,'clicked'=>0],
        ],
        16 => [
            'title'=>'Weekly Digest',
            'description'=>'Catch up on the top stories from the week.',
            'link'=>'https://blogspace.dev/digest',
            'banner_image'=>'https://images.unsplash.com/photo-1507842217343-583bb7270b66?q=80&w=1600&auto=format&fit=crop',
            'banner_icon'=>'https://api.iconify.design/mdi-light:book.svg?color=%230d6efd',
            'btns'=>[['title'=>'Read Digest','url'=>'https://blogspace.dev/digest']],
            'analytics'=>['sent'=>9000,'received'=>8000,'clicked'=>220],
        ],
        17 => [
            'title'=>'Breaking Weather Alert',
            'description'=>'Severe weather warning in your area.',
            'link'=>'https://newsportal.io/weather',
            'banner_image'=>'https://images.unsplash.com/photo-1501973801540-537f08ccae7b?q=80&w=1600&auto=format&fit=crop',
            'banner_icon'=>'https://api.iconify.design/mdi-light:weather-lightning.svg?color=%230d6efd',
            'btns'=>[['title'=>'View Weather','url'=>'https://newsportal.io/weather']],
            'analytics'=>['sent'=>10000,'received'=>1500,'clicked'=>0],
        ],
        18 => [
            'title'=>'Delivery Confirmation',
            'description'=>'Your order has been delivered successfully.',
            'link'=>'https://acme.co/orders/confirm',
            'banner_image'=>'https://images.unsplash.com/photo-1617957741649-4215e7fa9afd?q=80&w=1600&auto=format&fit=crop',
            'banner_icon'=>'https://api.iconify.design/mdi-light:package-variant.svg?color=%230d6efd',
            'btns'=>[],
            'analytics'=>['sent'=>5200,'received'=>5000,'clicked'=>77],
        ],
        19 => [
            'title'=>'Cart Abandonment Reminder',
            'description'=>'Don’t forget to complete your purchase!',
            'link'=>'https://shop.example.com/cart',
            'banner_image'=>'https://images.unsplash.com/photo-1515169067868-5387ec356754?q=80&w=1600&auto=format&fit=crop',
            'banner_icon'=>'https://api.iconify.design/mdi-light:cart-arrow-down.svg?color=%230d6efd',
            'btns'=>[['title'=>'Checkout Now','url'=>'https://shop.example.com/cart']],
            'analytics'=>['sent'=>15000,'received'=>12000,'clicked'=>845],
        ],
        20 => [
            'title'=>'Customer Survey',
            'description'=>'Tell us about your experience.',
            'link'=>'https://contoso.net/survey',
            'banner_image'=>'https://images.unsplash.com/photo-1581090700227-4c4f02b1a3c6?q=80&w=1600&auto=format&fit=crop',
            'banner_icon'=>'https://api.iconify.design/mdi-light:form-select.svg?color=%230d6efd',
            'btns'=>[['title'=>'Take Survey','url'=>'https://contoso.net/survey']],
            'analytics'=>['sent'=>0,'received'=>0,'clicked'=>0],
        ],
    ];
@endphp


    <script>
        // ----- hydrate JS from Blade -----
        const DUMMY_DOMAINS  = @json($dummyDomains);
        let   DUMMY_CAMPAIGNS = @json($dummyCampaigns);
        const DUMMY_REPORTS  = @json($dummyReports);

        // ---------- Helpers ----------
        function parseDateStr(s) { // "YYYY-MM-DD HH:mm"
            return moment(s, "YYYY-MM-DD HH:mm").toDate();
        }
        function withinRange(dateStr, start, end) {
            if (!start || !end) return true;
            const dt = parseDateStr(dateStr);
            return dt >= start && dt <= end;
        }
        function renderActions(id, status) {
            const viewBtn = `<button class="btn btn-sm btn-primary report-btn" data-id="${id}"> <i class="fas fa-analytics"></i>
</button>`;
            const cancelBtn = status === 'pending' || status === 'queued'
                ? `<button class="btn btn-sm btn-outline-danger ms-1 cancel-btn" data-id="${id}"><i class="fas fa-ban me-1"></i></button>`
                : '';
            return `<div class="d-flex">${viewBtn}${cancelBtn}</div>`;
        }

        $(function () {
            // ---------- Select2 (domains) ----------
            const $domain = $('#filter_domain').select2({
                placeholder: 'Search for Domain…',
                allowClear: true,
                minimumInputLength: 0,
                data: DUMMY_DOMAINS.map(d => ({ id: d, text: d }))
            });
            $domain.on('select2:open', () => {
                const search = $domain.data('select2').dropdown.$search;
                if (!search.val()) $domain.select2('trigger', 'query', { term: '' });
            });

            // ---------- Date-range picker ----------
            let rangeStart = null, rangeEnd = null;
            $('#daterange').daterangepicker({
                autoUpdateInput: false,
                locale: { format: 'MM/DD/YYYY', cancelLabel: 'Clear' },
                maxDate: new Date(),
                opens: 'left'
            })
            .on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
                rangeStart = picker.startDate.startOf('day').toDate();
                rangeEnd   = picker.endDate.endOf('day').toDate();
                rebuildTable();
            })
            .on('cancel.daterangepicker', function() {
                $(this).val('');
                rangeStart = rangeEnd = null;
                rebuildTable();
            });

            // ---------- DataTable (client-side) ----------
            const table = $('#datatable').DataTable({
                searching: false,
                paging: true,
                lengthChange: false,
                processing: true,
                serverSide: false,
                data: [], // filled by rebuildTable()
                columns: [
                    { data: null, render: (d,t,r,meta) => meta.row + 1, orderable:false, searchable:false },
                    { data: 'campaign_name' },
                    { data: 'domain', orderable:false, searchable:false },
                    { data: 'status' },
                    { data: 'sent_time' },
                    { data: 'clicks' },
                    { data: null, orderable:false, searchable:false, render: row => renderActions(row.id, row.status) },
                ],
                order: [[1, 'desc']],
                language: {
                    paginate: {
                        previous: '<i class="fas fa-angle-double-left"></i>',
                        next: '<i class="fas fa-angle-double-right"></i>'
                    }
                }
            });

            function currentType() {
                return $('input[name="campaign_type"]:checked').val() || 'all';
            }

            function rebuildTable() {
                const name = $('#filter_campaign_name').val().toLowerCase();
                const status = $('#filter_status').val();
                const domain = $('#filter_domain').val();
                const type = currentType();

                const filtered = DUMMY_CAMPAIGNS.filter(c => {
                    const byName   = !name   || c.campaign_name.toLowerCase().includes(name);
                    const byStatus = !status || c.status === status;
                    const byDomain = !domain || c.domain === domain;
                    const byType   = type === 'all' || c.type === type;
                    const byDate   = withinRange(c.sent_time, rangeStart, rangeEnd);
                    return byName && byStatus && byDomain && byType && byDate;
                });

                table.clear().rows.add(filtered).draw();
            }

            // initial load
            rebuildTable();

            // redraw when any filter changes
            $('#filterForm').on('change', 'input, select', rebuildTable)
                            .on('keyup', 'input[name="campaign_name"]', rebuildTable);
            $('input[name="campaign_type"]').on('change', rebuildTable);

            $('#resetBtn').on('click', function(){
                $('#filter_campaign_name').val('');
                $('#filter_status').val('').trigger('change');
                $('#filter_domain').val(null).trigger('change');
                $('#daterange').val('');
                rangeStart = rangeEnd = null;
                $('input[name="campaign_type"][value="all"]').prop('checked', true);
                rebuildTable();
            });

            // ---------- Modal + Charts ----------
            let deliveryChart, engagementChart, totalSent = 0;

            function renderDeliveryChart(sent, received) {
                const receivedPct = sent ? (received / sent) * 100 : 0;
                const notReceivedPct = 100 - receivedPct;

                if (deliveryChart) deliveryChart.destroy();
                deliveryChart = new ApexCharts(document.querySelector('#delivery-chart'), {
                    chart: { type: 'pie', height: 250, animations: { enabled: true, easing: 'easeinout', speed: 800 } },
                    series: [receivedPct, notReceivedPct],
                    labels: [`Delivered (${received.toLocaleString()})`, `Offline Users (${(sent - received).toLocaleString()})`],
                    legend: { position: 'bottom', markers: { radius: 3 } },
                    colors: ['#1cc88a', '#e74a3b'],
                    dataLabels: { enabled: true, formatter: (val) => Math.round(val) + '%' },
                    tooltip: { y: { formatter: (value, { seriesIndex }) => {
                        const counts = [received, sent - received];
                        return `${counts[seriesIndex].toLocaleString()} users (${Math.round(value)}%)`;
                    }}}
                });
                deliveryChart.render();
            }

            function renderEngagementChart(received, clicked) {
                const clickedPct = totalSent ? (clicked / totalSent) * 100 : 0;
                const notClickedPct = 100 - clickedPct;

                if (engagementChart) engagementChart.destroy();
                engagementChart = new ApexCharts(document.querySelector('#engagement-chart'), {
                    chart: { type: 'pie', height: 250, animations: { enabled: true, easing: 'easeinout', speed: 800 } },
                    series: [clickedPct, notClickedPct],
                    labels: [`Clicked (${clicked.toLocaleString()})`, `Not Clicked (${(received - clicked).toLocaleString()})`],
                    legend: { position: 'bottom', markers: { radius: 3 } },
                    colors: ['#36b9cc', '#f6c23e'],
                    dataLabels: { enabled: true, formatter: (val) => Math.round(val) + '%' },
                    tooltip: { y: { formatter: (value, { seriesIndex }) => {
                        const counts = [clicked, received - clicked];
                        return `${counts[seriesIndex].toLocaleString()} users (${Math.round(value)}%)`;
                    }}}
                });
                engagementChart.render();
            }

            function safeRenderChart(sent, received, clicked) {
                totalSent = sent;
                const el = document.querySelector('#chart');
                if (deliveryChart) deliveryChart.destroy();
                if (engagementChart) engagementChart.destroy();

                const chartContainer = $(el);
                chartContainer.empty().html(`
                    <div class="text-center mb-3">
                        <span class="badge bg-primary">Total Sent: ${sent.toLocaleString()}</span>
                    </div>
                    <ul class="nav nav-tabs mb-3" id="chartTabs" role="tablist">
                        <li class="nav-item w-50" role="presentation" data-bs-toggle="tooltip" data-bs-placement="top" title="Delivered vs undelivered (offline users).">
                            <button class="nav-link w-100 active" id="delivery-tab" data-bs-toggle="tab" data-bs-target="#delivery-chart" type="button" role="tab">
                                Delivery <i class="fas fa-info-circle ms-1"></i>
                            </button>
                        </li>
                        <li class="nav-item w-50" role="presentation" data-bs-toggle="tooltip" data-bs-placement="top" title="Users who clicked vs those who didn't.">
                            <button class="nav-link w-100" id="engagement-tab" data-bs-toggle="tab" data-bs-target="#engagement-chart" type="button" role="tab">
                                Engagement <i class="fas fa-info-circle ms-1"></i>
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="delivery-chart" role="tabpanel"></div>
                        <div class="tab-pane fade" id="engagement-chart" role="tabpanel"></div>
                    </div>
                `);

                renderDeliveryChart(sent, received);
                renderEngagementChart(received, clicked);

                document.querySelectorAll('#chartTabs button[data-bs-toggle="tab"]').forEach(tabEl => {
                    tabEl.addEventListener('shown.bs.tab', event => {
                        if (event.target.id === 'delivery-tab') {
                            renderDeliveryChart(totalSent, received);
                        } else if (event.target.id === 'engagement-tab') {
                            renderEngagementChart(received, clicked);
                        }
                    });
                });
                new bootstrap.Tab(document.querySelector('#delivery-tab')).show();
            }

            // open modal (dummy fetch)
            $('body').on('click', '.report-btn', function() {
                const id = Number($(this).data('id'));
                const d = DUMMY_REPORTS[id];

                $('#modalSpinner').removeClass('d-none');
                $('#modalContent').addClass('d-none');
                $('#reportModal').modal('show');

                setTimeout(() => { // simulate network
                    if (!d) {
                        $('#modalSpinner').addClass('d-none');
                        Swal.fire('Error', 'Failed to load report', 'error');
                        return;
                    }
                    // fill preview
                    $('#campaign_name').text(d.title).attr('title', d.title);
                    $('#prv_title').text(d.title).attr('title', d.title);
                    $('#prv_desc').text(d.description).attr('title', d.description);
                    $('#message_image').attr('src', d.banner_image);
                    $('#icon_prv').attr('src', d.banner_icon);
                    try {
                        $('#prv_link').text(new URL(d.link).hostname).attr('href', d.link);
                    } catch (e) {
                        $('#prv_link').text(d.link).attr('href', d.link);
                    }

                    // buttons
                    $('#btn_prv, #btn2_prv').addClass('d-none');
                    if (d.btns && d.btns.length) {
                        $('#btn_prv').removeClass('d-none')
                            .find('#btn_title1').text(d.btns[0].title)
                            .parent().attr('href', d.btns[0].url);

                        if (d.btns[1]) {
                            $('#btn2_prv').removeClass('d-none')
                                .find('#btn_title2').text(d.btns[1].title)
                                .parent().attr('href', d.btns[1].url);
                        }
                    }

                    // chart
                    safeRenderChart(
                        d.analytics.sent || d.analytics.delivered || 0,
                        d.analytics.received || 0,
                        d.analytics.clicked || 0
                    );

                    $('#modalSpinner').addClass('d-none');
                    $('#modalContent').removeClass('d-none');
                }, 400);
            });

            // cancel (dummy) -> flips status + toast
            $('body').on('click', '.cancel-btn', function(e) {
                e.preventDefault();
                const id  = Number($(this).data('id'));
                const idx = DUMMY_CAMPAIGNS.findIndex(c => c.id === id);
                if (idx === -1) return;

                const $btn = $(this);
                $btn.prop('disabled', true);

                Swal.fire({
                    title: 'Cancel this scheduled notification?',
                    text: 'Once cancelled, it will no longer be sent.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, cancel it',
                    cancelButtonText: 'No, keep it',
                }).then((result) => {
                    if (result.isConfirmed) {
                        // update local data
                        DUMMY_CAMPAIGNS[idx].status = 'cancelled';
                        Swal.fire({
                            icon: 'success',
                            title: 'Cancelled',
                            text: 'The campaign was marked as cancelled (dummy).',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        rebuildTable();
                    }
                    $btn.prop('disabled', false);
                });
            });
        });
    </script>
@endpush
