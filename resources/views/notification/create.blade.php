@extends('layouts.master')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
    <style>
        @media (min-width: 768px) {
            .sticky {
                position: -webkit-sticky;
                position: sticky;
                top: 100px;
            }
        }

        #domain-list {
            margin-bottom: 10px;
            max-height: 320px;
        }

        .domain-input {
            height: 40px;
            width: 100%;
            padding-left: 10px;
            padding-right: 30px;
        }

        .invalid-feedback {
            display: none;
        }

        .is-invalid+.invalid-feedback {
            display: block;
        }
    </style>
@endpush

@section('content')
    <section class="content-body" id="create_notification_page">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center text-head">
                <h2 class="mb-3 me-auto">Add Notifications</h2>
            </div>
            <form action="{{ route('notification.send') }}" method="post" id="notificationform" enctype="multipart/form-data"
                novalidate>
                @csrf
                <div class="row">
                    <!-- LEFT COLUMN: Form Inputs -->
                    <div class="col-lg-7 col-md-7 col-12">
                        <!-- Landing Page URL & Get Data -->
                        <div class="card h-auto">
                            <div class="card-body">
                                <label for="target_url">
                                    Landing Page URL
                                    <i class="fal fa-info-circle" data-bs-toggle="tooltip" data-bs-placement="top"
                                        title="Warning: Do not use Bitly or any URL shorteners."></i>
                                    <small class="text-muted">(include <u>'https'</u> while entering URL)</small>
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="d-flex align-items-start">
                                    <div class="form-group flex-grow-1">
                                        <input type="text" class="form-control notification" name="target_url"
                                            id="target_url" placeholder="https://example.com" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="ms-2">
                                        <button type="button" class="btn btn-outline-secondary text-wrap notification"
                                            id="getData">
                                            <i class="far fa-wand"></i> Get Data
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Title, Description -->
                        <div class="card h-auto">
                            <div class="card-body">
                                <div class="row" id="metaForm">
                                    <div class="col-12 mb-3">
                                        <label for="title">Title <span class="text-danger">*</span></label>
                                        <input type="text" onkeyup="titleText(this)"
                                            class="form-control emoji_picker notification" name="title" id="title"
                                            placeholder="Enter Title" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label for="description">Notification Message <span
                                                class="text-danger">*</span></label>
                                        <textarea class="form-control emoji_picker notification" onkeyup="descriptionText(this.value)" name="description"
                                            id="description" required placeholder="Notification description" style="height: 100px;"></textarea>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notification Images -->
                        <div class="card h-auto">
                            <div class="card-header">
                                <h4 class="card-title fs-20 mb-0">Notification Image</h4>
                            </div>
                            <div class="card-body">
                                <!-- Banner Image -->
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <h5>Banner Image</h5>
                                        <div class="userprofile">
                                            <img src="{{ asset('images/default.png') }}" id="banner_image" alt="Banner"
                                                class="img-fluid upimage">
                                            <div class="input-group">
                                                <input type="url" class="form-control" name="banner_image"
                                                    id="image_input" placeholder="e.g.: https://example.com/image.jpg"
                                                    aria-label="Banner Image URL" value="{{ asset('images/default.png') }}"
                                                    onchange="changeBanner(this.value)" />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Banner Icon -->
                                    <div class="col-12 mb-3">
                                        <h5>Banner Icon <span class="text-danger">*</span></h5>
                                        <div class="userprofile">
                                            <img src="{{ asset('images/push/icons/alarm-1.png') }}" id="banner_icon"
                                                alt="Icon" class="img-fluid upimage">
                                            <div class="input-group">
                                                <input type="text" class="form-control banner_icon_trans"
                                                    name="banner_icon" id="target" placeholder="Select Icon"
                                                    value="{{ asset('images/push/icons/alarm-1.png') }}"
                                                    onchange="prv_icons(this.value)">
                                                <button class="input-group-text" type="button" style="margin:inherit"
                                                    data-bs-toggle="modal" data-bs-target="#staticBackdrop"
                                                    id="button2-addon1">
                                                    <i class="fas fa-upload"></i> Upload
                                                </button>
                                                <button class="input-group-text d-none" type="button"
                                                    style="margin:inherit" id="button2-reset" onclick="resetIcon()">
                                                    Reset
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <div class="form-check mb-2">
                                    <input type="checkbox" class="form-check-input" id="ctaCheckbox" name="cta_enabled"
                                        value="1">
                                    <label class="form-check-label" for="ctaCheckbox">Enable CTA's Section</label>
                                </div>
                                <div id="cardContainer" class="cardContainer" style="display: none">
                                    <div class="border p-3 rounded-1 mb-2" id="first_btn">
                                        <h5 class="mb-2 badge badge-primary rounded-0">Button 1</h5>
                                        <div class="row">
                                            <div class="col-lg-3 col-12">
                                                <label for="titleInput1" class="form-label">Title: <span
                                                        class="text-danger">*</span>
                                                </label>
                                                <input type="text" class="form-control" id="titleInput1"
                                                    placeholder="Click Here" onkeyup="btn_title_prv1(this.value)"
                                                    name="btn_1_title" required>
                                            </div>
                                            <div class="col-lg-9 col-12">
                                                <label for="urlInput1" class="form-label">Landing URL: <span
                                                        class="text-danger">*</span>
                                                </label>
                                                <input type="url" class="form-control" name="btn_1_url"
                                                    id="urlInput1" placeholder="Enter URL" required>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm mt-3 btn-outline-secondary"
                                            onclick="toggleSecondBtn()">
                                            <i class="fas fa-plus"></i> Add Another </button>
                                    </div>
                                    <div id="second_btn" class="border p-3 rounded-1 mb-2" style="display: none">
                                        <h5 class="mb-2 badge badge-secondary rounded-0">Button 2</h5>
                                        <div class="row">
                                            <div class="col-lg-3">
                                                <label for="btn_title_2" class="form-label">Title: <span
                                                        class="text-danger">*</span>
                                                </label>
                                                <input type="text" class="form-control" name="btn_title_2"
                                                    placeholder="Click Here" onkeyup="btn_title_prv2(this.value)"
                                                    required>
                                            </div>
                                            <div class="col-lg-9">
                                                <label for="btn_url_2" class="form-label">Landing URL: <span
                                                        class="text-danger">*</span>
                                                </label>
                                                <input type="url" class="form-control" name="btn_url_2"
                                                    id="urlInput2" placeholder="Enter URL" required>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm mt-3 btn-outline-danger "
                                            onclick="removeSecondBtn()">
                                            <i class="fas fa-trash"></i> Delete </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- SEGMENT SECTION --}}
                        <div class="card h-auto">
                            <div class="card-header">
                                <h4 class="card-title fs-20 mb-0">Segment</h4>
                            </div>
                            <div class="card-body">
                                <div class="col-lg-12">
                                    <div class="custom-radio justify-content-start">
                                        <label class="mb-2 mb-lg-3 w-auto d-inline-block" for="segment_all">
                                            <input type="radio" name="segment_type" id="segment_all" value="all"
                                                checked>
                                            <span>Broadcast (All Subscribers)</span>
                                        </label>
                                        <label class="mb-2 mb-lg-3 w-auto d-inline-block" for="segment_particular">
                                            <input type="radio" name="segment_type" id="segment_particular"
                                                value="particular">
                                            <span id="selectedSegmentLabel">Send to Segment</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card h-auto segment-card d-none">
                            <div class="card-body">
                                <div class="mb-1">
                                    <label for="segmentSelect" class="form-label">Choose a Segment:</label>
                                    <select class="form-select select-segment" name="segment_id" id="segmentSelect" required>
                                        <option disabled selected>Select a segment</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Domain Selection -->
                        <div class="card h-auto" id="domainCard">
                            <div class="card-header">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="select_all"
                                        id="select_all_domain" value="Select All">
                                    <label class="form-check-label" for="select_all_domain">Select All</label>
                                </div>
                                <!-- filter box -->
                                <input type="search" id="domainFilter" class="form-control domain-input" placeholder="Search domains…" style="max-width: 200px;">
                            </div>
                            <div class="card-body">
                                <div id="domain-loader" class="text-center my-3" style="display: none;">
                                    <div class="spinner-border" role="status"><span class="visually-hidden">Loading…</span></div>
                                </div>
                                <div class="row scrollbar" id="domain-list">
                                    <!-- AJAX-injected items -->
                                </div>
                                <span id="domain-error-span"></span>
                            </div>
                        </div>

                        <!-- Notification Timing -->
                        <div class="card h-auto">
                            <div class="card-header">
                                <h4 class="card-title fs-20 mb-0">Notification Timing</h4>
                            </div>
                            <div class="card-body">
                                <div class="justify-content-start">
                                    <label class="mb-3 me-3 w-auto d-inline-block" for="sendnow">
                                        <input class="radio-option" type="radio" name="schedule_type" id="sendnow"
                                            value="Instant" checked>
                                        <span>Instant Notification</span>
                                    </label>
                                    <label class="mb-3 me-3 w-auto d-inline-block" for="Schedule">
                                        <input class="radio-option" type="radio" name="schedule_type" id="Schedule"
                                            value="Schedule">
                                        <span>Schedule Notification</span>
                                    </label>
                                </div>
                                <div class="custom-tab-1" id="schedule_options" style="display: none">
                                    <ul class="nav nav-tabs">
                                        <li class="nav-item">
                                            <a class="nav-link active" data-bs-toggle="tab" href="#one_time">
                                                <i class="fas fa-history"></i> One Time
                                            </a>
                                        </li>
                                    </ul>
                                    <div class="tab-content">
                                        <!-- One Time Tab -->
                                        <div class="tab-pane show active fade mb-3 p-3 border" id="one_time"
                                            role="tabpanel">
                                            @php
                                                $minDt   = \Carbon\Carbon::now()->format('Y-m-d\TH:i');
                                                $minLabel= \Carbon\Carbon::now()->format('j M Y, H:i');
                                            @endphp
                                            <div class="row">
                                                <div class="col-12 mb-3">
                                                    <div class="form-group">
                                                        <label for="one_time_start_date">Start Date & Time <span
                                                                class="text-danger">*</span></label>
                                                        <input type="datetime-local" class="form-control"
                                                            name="one_time_datetime" id="one_time_start_date"
                                                            min="{{ $minDt }}" value="{{ old('one_time_datetime', $minDt) }}" required>
                                                        <div class="invalid-feedback"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- RIGHT COLUMN: Preview Sidebar -->
                    <div class="col-lg-5 col-md-5 col-12">
                        <div id="stickyElement" class="sticky">
                            <div class="card h-auto">
                                <div class="card-body p-3">
                                    <div class="custom-radio justify-content-start">
                                        <label class="mb-3 w-auto d-inline-block" for="preview_windows">
                                            <input type="radio" name="preview_type" id="preview_windows"
                                                value="preview_windows" checked>
                                            <span>Windows</span>
                                        </label>
                                        <label class="mb-3 w-auto d-inline-block" for="preview_android">
                                            <input type="radio" name="preview_type" id="preview_android"
                                                value="preview_android">
                                            <span>Android</span>
                                        </label>
                                    </div>
                                    <div class="windows_view">
                                        <img src="" id="message_image" class="feat_img img-fluid message_image"
                                            alt="" style="display: none;">
                                        <div class="windows_body">
                                            <div class="d-flex align-items-center">
                                                <img src="{{ asset('images/chrome.png') }}" class="me-2"
                                                    alt="Chrome">
                                                <span>Google Chrome</span>
                                                <i class="far fa-window-close ms-auto"></i>
                                            </div>
                                            <div class="preview_content d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    <img src="{{ asset('images/push/icons/alarm-1.png') }}"
                                                        id="icon_prv" class="img-fluid" alt="Icon Preview">
                                                </div>
                                                <div class="flex-grow-1 ms-2">
                                                    <span class="fs-16 text-white prv_title" id="prv_title">Title
                                                        Preview</span>
                                                    <p class="card-text prv_desc" id="prv_desc">Preview of Description
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="row g-2">
                                                <div class="col-6 btn_prv" style="display:none;" id="btn_prv">
                                                    <span id="btn_title1"
                                                        class="btn_title1 btn btn-dark w-100 btn-sm">Click Here</span>
                                                </div>
                                                <div class="col-6 btn2_prv" style="display:none;" id="btn2_prv">
                                                    <span id="btn_title2"
                                                        class="btn_title2 btn btn-dark w-100 btn-sm">Click Here</span>
                                                </div>
                                            </div>


                                        </div>
                                    </div>
                                    <div class="android_view" style="display: none;">
                                        <div class="android_body mt-3">
                                            <div class="d-flex align-items-center">
                                                <img src="{{ asset('images/chrome.png') }}" class="me-2"
                                                    alt="Chrome">
                                                <span>Google Chrome</span>
                                                <span class="ms-auto"><i
                                                        class="far fa-chevron-circle-down fa-lg"></i></span>
                                            </div>
                                            <div class="preview_content d-flex justify-content-between align-items-center">
                                                <div class="flex-grow-1">
                                                    <span class="fs-16 text-black prv_title" id="prv_title">Title
                                                        Preview</span>
                                                    <p class="card-text fs-14 prv_desc" id="prv_desc">Preview of
                                                        Description</p>
                                                </div>
                                                <div class="flex-shrink-0">
                                                    <img src="{{ asset('images/push/icons/alarm-1.png') }}"
                                                        id="icon_prv" class="img-fluid" alt="Icon Preview">
                                                </div>
                                            </div>
                                            <img src="" id="message_image"
                                                class="feat_img message_image img-fluid mt-3" alt="">

                                            <div class="d-flex align-items-center">
                                                <div class="mt-3 me-3 btn_prv" style="display:none;" id="btn_prv">
                                                    <span id="btn_title1" class="btn_title1 text-primary fs-16">Click
                                                        Here</span>
                                                </div>
                                                <div class="mt-3 me-3 btn2_prv" style="display:none;" id="btn2_prv">
                                                    <span id="btn_title2" class="btn_title2 text-primary fs-16">Click
                                                        Here</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- /.row -->

                <!-- Submit Buttons -->
                <div class="mt-3">
                    <button type="submit" id="sendNotification" class="btn btn-primary send_btn mb-2">
                        <i class="far fa-check-square pe-2"></i>Send Now
                    </button>
                    <button type="reset" class="btn basic-btn mb-2">
                        <i class="far fa-window-close pe-2"></i>Reset
                    </button>
                </div>
            </form>
        </div>
    </section>

    <!-- Modal: Select Icons -->
    <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fs-20" id="staticBackdropLabel">Select Icons</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body d-flex flex-wrap" id="icon-container">
                    <!-- spinner until icons load -->
                    <div id="icon-loader" class="spinner-border m-auto" role="status">
                        <span class="visually-hidden">Loading…</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-danger" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>
    
    <script>
        $(document).ready(function() {
            $('input[name="segment_type"]').on('change', function() {
                if ($('#segment_particular').is(':checked')) {
                    $('.segment-card').removeClass('d-none');
                    $('#domainCard').addClass('d-none');
                } else {
                    $('.segment-card').addClass('d-none');
                    $('#domainCard').removeClass('d-none');
                }
            });
        });
    </script>
    <script>
        $(function () {
            let loaded = false;

            $('#segmentSelect').select2({
                placeholder: 'Select a segment',
                allowClear: true,
                width: '100%',

                ajax: {
                    transport: function (params, success, failure) {
                        if (loaded) {
                            success({ results: $('#segmentSelect').data('segments') });
                            return;
                        }

                        $.ajax({
                            url: "{{ route('segmentation.segment-list') }}",
                            dataType: 'json',
                            success: function (resp) {
                                if (resp.success) {
                                    $('#segmentSelect').data('segments', resp.data);
                                    loaded = true;
                                    success({ results: resp.data });
                                } else {
                                    failure();
                                }
                            },
                            error: failure
                        });
                    },
                    processResults: d => d
                },

                minimumInputLength: 0,

                // ✅ Format dropdown list items
                templateResult: function (data) {
                    if (!data.id) return data.text; // skip placeholder

                    const type = (data.text || '').toLowerCase().includes('device') ? 'device' :
                                (data.text || '').toLowerCase().includes('geo') ? 'geo' : '';

                    const icon = type === 'device' ? '📱' : type === 'geo' ? '🌍' : '';

                    return $('<span>' + icon + ' ' + data.text + '</span>');
                },

                // ✅ Format selected item
                templateSelection: function (data) {
                    if (!data.id) return data.text;

                    const type = (data.text || '').toLowerCase().includes('device') ? 'device' :
                                (data.text || '').toLowerCase().includes('geo') ? 'geo' : '';

                    const icon = type === 'device' ? '📱' : type === 'geo' ? '🌍' : '';

                    return $('<span>' + icon + ' ' + data.text + '</span>');
                }
            });
        });
    </script>

    @php
        use Illuminate\Support\Facades\File;
        $files = File::files(public_path('images/push/icons'));
        $iconUrls = collect($files)->map(fn($f) => asset('images/push/icons/' . $f->getFilename()))->toJson();
    @endphp

    <script>
        const ICON_URLS = {!! $iconUrls !!};
    </script>
    
    <script>
        // unified show/hide + required toggling
        function hideOrShow() {
            const isSchedule = $('#Schedule').is(':checked');
            console.log('[hideOrShow] isSchedule =', isSchedule);

            $('#schedule_options').toggle(isSchedule);
            $('#one_time_start_date, #recurring_start_date, #recurring_end_date, #occurrence, #recurring_start_time')
                .prop('required', isSchedule);

            $('.send_btn').html(
                isSchedule ?
                '<i class="far fa-check-square pe-2"></i>Schedule Now' :
                '<i class="far fa-check-square pe-2"></i>Send Now'
            );
        }

        // Show/hide CTA section
        function toggleCTASection() {
            const $allCTAinputs = $('#first_btn input, #second_btn input');
            if ($('#ctaCheckbox').is(':checked')) {
                $allCTAinputs.prop('disabled', false);
                $('#cardContainer').slideDown(200);
            } else {
                // hide the whole UI, clear + disable inputs
                $('#second_btn').hide();
                $allCTAinputs.prop('disabled', true).val('');
                $('#btn_prv, .btn2_prv').hide();
                $('#cardContainer').slideUp(200);
            }
        }

        $('input[name="schedule_type"]').on('change', function() {
            console.log('[radio change] now checked =', this.id);
            hideOrShow();
        });
    </script>

    <script>
        // Preview functions
        function prv_icons(url) {
            var defaultImageSrc = "{{ asset('images/push/icons/alarm-1.png') }}";
            if (!url.trim()) {
                $('#icon_prv, #banner_icon').attr('src', defaultImageSrc);
            } else {
                $('#icon_prv, #banner_icon').attr('src', url);
            }
        }

        function titleText(input) {
            $('.prv_title').html(input.value);
        }

        function descriptionText(text) {
            $('.prv_desc').html(text);
        }

        function disableDate() {
            if ($('#end_date').is(':checked')) {
                $('#recurring_end_date').prop('disabled', true);
            } else {
                $('#recurring_end_date').prop('disabled', false);
            }
        }

        function changeBanner(imagesrc) {
            var defaultImageSrc = "{{ asset('images/default.png') }}";
            if (!imagesrc.trim()) {
                $('#banner_image').attr('src', defaultImageSrc);
                $('.message_image').attr('src', defaultImageSrc);
            } else {
                $('#banner_image').attr('src', imagesrc);
                $('.message_image').attr('src', imagesrc);
                $(".message_image").show();
            }
        }

        function setImageUrl(url) {
            document.getElementById('target').value = url;
            prv_icons(url);
            var modal = document.getElementById('staticBackdrop');
            var modalInstance = bootstrap.Modal.getInstance(modal);
            modalInstance.hide();
        }

        function resetIcon() {
            var defaultImageSrc = "{{ asset('images/push/icons/alarm-1.png') }}";
            $('#banner_icon,#icon_prv').attr('src', defaultImageSrc);
            $('#target').val('');
            $('#button2-addon1').attr('class', 'input-group-text d-flex');
        }

        function resetImage() {
            var defaultImageSrc = "{{ asset('images/default.png') }}";
            $('#banner_image').attr('src', defaultImageSrc);
            $('.message_image').attr('src', '');
            $(".message_image").hide();
            $('#image_input').val('');
            $('#button-addon2').attr('class', 'input-group-text d-flex');
        }

        function resetInputFields() {
            document.getElementById("one_time_start_date").value = "";
            document.getElementById("recurring_start_date").value = "";
            document.getElementById("recurring_end_date").value = "";
            document.getElementById("occurrence").selectedIndex = 0;
            document.getElementById("recurring_start_time").value = "";
        }

        function handleRadioChange(event) {
            resetInputFields();
            hideorshow(event);
        }

        // Change Send button text based on schedule type
        $(document).ready(function() {

            // initial state
            $('#cardContainer, #second_btn, .btn_prv, .btn2_prv').hide();

            // toggle CTA section
            $('#ctaCheckbox').on('change', toggleCTASection);

            $('input[name="schedule_type"]').change(function() {
                if ($('#sendnow').is(':checked')) {
                    $('.send_btn').html('<i class="far fa-check-square pe-2"></i>Send Now');
                } else if ($('#Schedule').is(':checked')) {
                    $('.send_btn').html('<i class="far fa-check-square pe-2"></i>Schedule Now');
                }
            });
        });

        // Preview switch between Windows and Android
        $(document).ready(function() {
            $('input[name="preview_type"]').on('change', function() {
                if ($(this).val() === 'preview_windows') {
                    $('.windows_view').show();
                    $('.android_view').hide();
                } else if ($(this).val() === 'preview_android') {
                    $('.windows_view').hide();
                    $('.android_view').show();
                }
            });
            // Initial state
            if ($('input[name="preview_type"]:checked').val() === 'preview_windows') {
                $('.windows_view').show();
                $('.android_view').hide();
            } else {
                $('.windows_view').hide();
                $('.android_view').show();
            }
        });

        // Select or deselect all domains
        $("#select_all_domain").on('click', function() {
            $("input[name='domain_name[]']:not(:disabled)").prop('checked', $(this).prop('checked'));
        });

        // Sticky sidebar behavior
        $(document).ready(function() {
            var stickyElement = $('#stickyElement');
            var stickyOffset = stickyElement.offset().top;
            $(window).scroll(function() {
                var scrollTop = $(window).scrollTop();
                var windowWidth = $(window).width();
                if (windowWidth > 768 && scrollTop >= stickyOffset) {
                    stickyElement.css({
                        position: 'fixed',
                        top: '100px',
                        width: stickyElement.parent().width()
                    });
                } else {
                    stickyElement.css({
                        position: 'static',
                        width: ''
                    });
                }
            });
        });
    </script>
    <script>
        $(function() {
            // — 1) Auto-generate campaign name —
            function genCampaign() {
                return 'CAMP#' + Math.floor(1000 + Math.random() * 9000);
            }
            $('#campaign_name').val(genCampaign());
            $('#notificationform').on('reset', function() {
                setTimeout(() => $('#campaign_name').val(genCampaign()), 0);
            });

            // — 2) GET META DATA (as before) —
            $('#getData').on('click', function() {
                var url = $.trim($('#target_url').val());
                if (!url) {
                    iziToast.error({
                        title: 'Warning!',
                        message: 'Please enter a URL first.',
                        position: 'topRight'
                    });
                    return;
                }
                var $btn = $(this).prop('disabled', true)
                    .html('<span class="spinner-border spinner-border-sm me-2"></span>Fetching...');
                $.post("{{ route('notification.fetchMeta') }}", {
                        _token: '{{ csrf_token() }}',
                        target_url: url
                    })
                    .done(function(res) {
                        if (res.success) {
                            res.data.title && $('#title').val(res.data.title).trigger('keyup');
                            res.data.description && $('#description').val(res.data.description).trigger(
                                'keyup');
                            res.data.image && $('#image_input').val(res.data.image) && changeBanner(res
                                .data.image);
                            iziToast.success({
                                title: 'Success',
                                message: 'Metadata loaded.',
                                position: 'topRight'
                            });
                        } else {
                            iziToast.error({
                                title: 'Error',
                                message: res.message,
                                position: 'topRight'
                            });
                        }
                    })
                    .fail(function() {
                        iziToast.error({
                            title: 'Error',
                            message: 'Failed to fetch metadata.',
                            position: 'topRight'
                        });
                    })
                    .always(function() {
                        $btn.prop('disabled', false).html('<i class="far fa-wand"></i> Get Data');
                    });
            });

            // — 3) Strong form validation —
            $.validator.addMethod("campformat", function(v) {
                return /^CAMP#\d{4}$/.test(v);
            }, "Must be in format CAMP#1234");

            $("#notificationform").validate({
                errorElement: 'div',
                errorClass: 'invalid-feedback',
                ignore: ':hidden',
                rules: {
                    target_url: {
                        required: true,
                        url: true
                    },
                    title: {
                        required: true,
                        maxlength: 100
                    },
                    description: {
                        required: true,
                        maxlength: 200
                    },
                    'domain_name[]': {
                        required: true
                    },
                    one_time_datetime: {
                        required: function() {
                            return $('#Schedule').is(':checked');
                        }
                    },
                    // recurring_start_date: {
                    //     required: function() {
                    //         return $('#Schedule').is(':checked');
                    //     }
                    // },
                    // recurring_end_date: {
                    //     required: function() {
                    //         return $('#Schedule').is(':checked') && !$('#end_date').is(':checked');
                    //     }
                    // },
                    occurrence: {
                        required: function() {
                            return $('#Schedule').is(':checked');
                        }
                    },
                    // recurring_start_time: {
                    //     required: function() {
                    //         return $('#Schedule').is(':checked');
                    //     }
                    // },
                    btn_1_title: {
                        required: function() {
                            return $('#ctaCheckbox').is(':checked');
                        }
                    },
                    btn_1_url: {
                        required: function() {
                            return $('#ctaCheckbox').is(':checked');
                        },
                        url: true
                    },
                    btn_title_2: {
                        required: function() {
                            return $('#ctaCheckbox').is(':checked') && $('#second_btn').is(':visible');
                        }
                    },
                    btn_url_2: {
                        required: function() {
                            return $('#ctaCheckbox').is(':checked') && $('#second_btn').is(':visible');
                        },
                        url: true
                    }
                },
                messages: {
                    target_url: 'Please enter a valid URL.',
                    campaign_name: 'Campaign Name is required and must match CAMP#1234.',
                    title: {
                        required: 'Title is required.',
                        maxlength: 'Title cannot exceed 100 characters.'
                    },
                    description: {
                        required: 'Notification Message is required.',
                        maxlength: 'Description cannot exceed 200 characters.'
                    },
                    'domain_name[]': 'Please select at least one domain.',
                    one_time_datetime: 'Please select a date & time for scheduling (must be now or later).',
                    // recurring_start_date: 'Recurring Start Date is required.',
                    // recurring_end_date: 'Recurring End Date is required unless “Never Ends” is checked.',
                    occurrence: 'Please select a repeat interval.',
                    // recurring_start_time: 'Recurring Start Time is required.',
                    btn_1_title: "Please enter a title for Button 1.",
                    btn_1_url: "Please enter a valid URL for Button 1.",
                    btn_title_2: "Please enter a title for Button 2.",
                    btn_url_2: "Please enter a valid URL for Button 2."
                },
                errorPlacement: function(error, element) {
                    const n = element.attr('name');
                    if (n === 'btn_1_title' || n === 'btn_1_url' ||
                        n === 'btn_title_2' || n === 'btn_url_2') {
                        // put CTA errors right after their inputs
                        error.insertAfter(element);
                    } else if (n === 'domain_name[]') {
                        error.appendTo('#domain-error-span');
                    } else {
                        error.insertAfter(element);
                    }
                },
                highlight: function(el) {
                    $(el).addClass('is-invalid');
                },
                unhighlight: function(el) {
                    $(el).removeClass('is-invalid');
                },
                submitHandler: function(form) {
                    var $btn = $('#sendNotification')
                        .prop('disabled', true)
                        .html('<i class="fas fa-spinner fa-spin me-2"></i> Processing...');
                    form.submit();
                }
            });
        });
    </script>

    <script>
        (function() {
            let injected = false;
            $('#staticBackdrop').on('show.bs.modal', function() {
                if (injected) return;
                const $ct = $('#icon-container').empty();
                ICON_URLS.forEach(url => {
                    $('<div class="m-1">')
                        .append($('<img>').attr({
                                src: url,
                                width: 52,
                                height: 52,
                                class: 'img-thumbnail p-2',
                                alt: 'icon'
                            })
                            .css('cursor', 'pointer')
                            .click(() => setImageUrl(url))
                        )
                        .appendTo($ct);
                });
                injected = true;
            });
        })();
    </script>
    <script>
        $(function() {
            // Cache selectors
            const $ctaCheckbox = $('#ctaCheckbox');
            const $cardContainer = $('#cardContainer');
            const $firstBtn = $('#first_btn');
            const $addAnotherBtn = $firstBtn.find('button');
            const $secondBtn = $('#second_btn');
            const $removeBtn = $secondBtn.find('button');
            const $btnPreview1 = $('.btn_prv');
            const $btnPreview2 = $('.btn2_prv');
            const $btnTitle1Spans = $('.btn_title1');
            const $btnTitle2Spans = $('.btn_title2');
            const $titleInput1 = $('#titleInput1');
            const $btn2Input = $('input[name="btn_title_2"]');

            // Update first CTA preview
            function updateFirstPreview(text) {
                $btnTitle1Spans.text(text || 'Click Here');
                if ($ctaCheckbox.is(':checked') && text.trim()) {
                    $btnPreview1.show();
                } else {
                    $btnPreview1.hide();
                }
            }

            // Update second CTA preview
            function updateSecondPreview(text) {
                $btnTitle2Spans.text(text || 'Click Here');
                if ($ctaCheckbox.is(':checked') && $secondBtn.is(':visible') && text.trim()) {
                    $btnPreview2.show();
                } else {
                    $btnPreview2.hide();
                }
            }

            // Reset the "Add Another" button text
            function setAddBtnDefault() {
                $addAnotherBtn.html('<i class="fas fa-plus"></i> Add Another');
            }

            // Clear second-CTA inputs
            function clearSecondFields() {
                $secondBtn.find('input').val('');
            }

            // Toggle second CTA on/off
            function toggleSecondBtn() {
                if ($secondBtn.is(':visible')) {
                    $secondBtn.slideUp(200, function() {
                        updateSecondPreview('');
                    });
                    setAddBtnDefault();
                    clearSecondFields();
                } else {
                    $secondBtn.slideDown(200, function() {
                        updateSecondPreview($btn2Input.val());
                    });
                    $addAnotherBtn.html('<i class="fas fa-minus"></i> Hide');
                }
            }

            // Remove/hide second CTA
            function removeSecondBtn() {
                $secondBtn.slideUp(200, function() {
                    updateSecondPreview('');
                });
                setAddBtnDefault();
                clearSecondFields();
            }

            // Wire up events
            $ctaCheckbox.on('change', toggleCTASection);
            $addAnotherBtn.on('click', toggleSecondBtn);
            $removeBtn.on('click', removeSecondBtn);
            $titleInput1.on('input', () => updateFirstPreview($titleInput1.val()));
            $btn2Input.on('input', () => updateSecondPreview($btn2Input.val()));

            // Initial state
            $cardContainer.hide();
            $secondBtn.hide();
            $btnPreview1.hide();
            $btnPreview2.hide();
        });
    </script>
    <script>
        // fetch & render domains
        function loadDomains(search = '') {
            const $loader = $('#domain-loader');
            const $list = $('#domain-list');
            $list.empty();
            $loader.show();

            $.ajax({
                url: "{{ route('domain.domain-list') }}",
                dataType: 'json',
                data: {
                    q: search
                },
                success(res) {
                    if (res.status) {
                        res.data.forEach(domain => {
                            $list.append(`<div class="col-lg-6 col-md-6 col-12 domain-item">
                                <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox"
                                        name="domain_name[]" id="domain-${domain.id}"
                                        value="${domain.text}">
                                <label class="form-check-label"
                                        for="domain-${domain.id}">
                                    ${domain.text}
                                </label>
                                </div>
                            </div>`);
                        });
                    } else {
                        $list.append(`<div class="text-danger">${res.message}</div>`);
                    }
                },
                error() {
                    $list.append('<div class="text-danger">Failed to load domains.</div>');
                },
                complete() {
                    $loader.hide();
                }
            });
        }

        $(document).ready(function() {
            // initially load all domains
            loadDomains();

             // 1) Client-side filter
            $('#domainFilter').on('input', function() {
                const term = this.value.trim().toLowerCase();
                $('#domain-list .domain-item').each(function() {
                const label = $(this).find('label').text().toLowerCase();
                $(this).toggle(label.includes(term));
                });
                // whenever you filter, uncheck the “Select All” box
                $('#select_all_domain').prop('checked', false);
            });

            // 2) “Select All” only for visible items
            $('#select_all_domain').on('click', function() {
                const shouldCheck = this.checked;
                // first, clear every checkbox
                $("input[name='domain_name[]']").prop('checked', false);
                if (shouldCheck) {
                // then check only the visible ones
                $('#domain-list .domain-item:visible')
                    .find("input[name='domain_name[]']")
                    .prop('checked', true);
                }
            });
        });
    </script>
@endpush