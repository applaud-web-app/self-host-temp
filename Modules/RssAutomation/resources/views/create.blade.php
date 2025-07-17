@extends('layouts.master')

@push('styles')
    <style>
        .sticky {
            position: -webkit-sticky;
            position: sticky;
            top: 100px;
        }

        #randomFeed {
            display: none;
        }

        .feed-item {
            display: flex;
            align-items: flex-start;
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 12px;
            border: 1px solid #dcdcdc;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .feed-item:hover {
            background: #e9e9e9;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .feed-item.active {
            background: #e0f7fa;
            border-color: #4dd0e1;
        }

        .feed-item .preview-image {
            flex: 0 0 80px;
            margin-right: 12px;
        }

        .feed-item .preview-image img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }

        .feed-item .feed-content {
            flex: 1;
        }

        .feed-item .feed-content h5 {
            margin: 0 0 6px;
            font-size: 16px;
            font-weight: 600;
            color: #333;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .feed-item .feed-content p {
            margin: 0;
            font-size: 14px;
            line-height: 1.4;
            color: #555;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .error {
            font-size: 14px;
            color: #ff0000;
        }

        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(0, 0, 0, .1);
            border-radius: 50%;
            border-top-color: #007bff;
            animation: spin 1s ease-in-out infinite;
            margin-left: 10px;
            vertical-align: middle;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        #feed-container {
            max-height: 400px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .time-options {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .time-option {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .time-option:hover {
            background: #f0f0f0;
        }

        .time-option.active {
            background: #f93a0b;
            color: white;
            border-color: #f93a0b;
        }

        .custom-time-input {
            display: none;
            margin-top: 10px;
        }

        .banner-icon {
            width: 42px;
            height: 42px;
            object-fit: fill;
            border-radius: 2px;
        }

        .banner-preview {
            width: 100%;
            max-height: 120px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
        }

        .cta-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            border: 1px solid #dee2e6;
        }

        .cta-section .form-label {
            font-weight: 600;
        }

        .time-range-container {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .time-range-container .form-group {
            flex: 1;
        }

        .time-difference {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            font-weight: 500;
        }

        .time-difference-value {
            color: #f93a0b;
            font-weight: bold;
        }

        .invalid-time {
            color: #ff0000;
            font-size: 0.875em;
            margin-top: 5px;
        }
        .upload-icon-btn {
            padding: 14px;
            border-radius: 5px;
            background: red;
            color: #fff;
            border: none;
        }

        .time-option.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        .time-difference-error {
            display: block;
            margin-top: 5px;
            font-size: 14px;
        }
    </style>
@endpush

@section('content')
    <section class="content-body">
        <div class="container-fluid">
            <div class="mb-sm-3 d-flex flex-wrap align-items-center text-head">
                <h2 class="me-auto">RSS Feed</h2>
            </div>
            <form action="{{ route('rss.store') }}" id="rss_create" method="post">
                @csrf
                <div class="row">
                    <div class="col-lg-7 col-md-7 col-12">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card ">
                                    <div class="card-header">
                                        <h4 class="card-title fs-20">Get Feed</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="rssfeedname" class="form-label">Feed Name <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="rssfeedname" id="rssfeedname"
                                                placeholder="Enter Feed Name" maxlength="100" required>
                                            <div class="invalid-feedback">Please provide Feed Name.</div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="rssFeedUrl" class="form-label">Feed URL <small
                                                    class="text-muted">(Url with https or http)</small><span
                                                    class="text-danger">*</span></label>
                                            <input type="url" class="form-control" name="rssFeedUrl" id="rssFeedUrlLink"
                                                placeholder="Enter the Feed Url" required>
                                            <div id="message"></div>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="button" class="btn btn-secondary w-100" id="fetchRssData">
                                                <span id="fetchText">Fetch Feed</span>
                                                <span id="fetchSpinner" class="loading-spinner"
                                                    style="display: none;"></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-12" id="feedPreview" style="display: none">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title fs-20 mb-0">Feed Preview</h4>
                                    </div>
                                    <div class="card-body">
                                        <div id="feed-container"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-12" id="feedNotifyData" style="display: none">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="campaign_name">Feed Title <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control emoji_picker notification"
                                                name="campaign_name" id="campaign_name" placeholder="@rss_title" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label for="description">Notification Message <span
                                                    class="text-danger">*</span></label>
                                            <textarea type="text" class="form-control emoji_picker notification" name="description" id="description"
                                                placeholder="@rss_message" style="height: 100px;" readonly></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="link_url" class="form-label">Landing Page URL <span
                                                    class="text-danger">*</span></label>
                                            <input type="url" class="form-control" id="link_url" name="link_url"
                                                placeholder="@rss_link" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-12" id="additionalOptions" style="display: none">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="fs-20 mb-0">Additional Options</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="banner_image" class="form-label">Banner image <span
                                                    class="text-danger">*</span></label>
                                            <div class="d-flex gap-2 align-items-center">
                                                <img src="{{ asset('images/default.png') }}" id="banner_url" alt=""
                                                    class="message_image img-fluid banner-icon">
                                                <input type="url" class="form-control" id="banner_image"
                                                    name="banner_image" placeholder="@rss_bannerimage" readonly>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="banner_icon" class="form-label">Banner icon <span
                                                    class="text-danger">*</span></label>
                                            <div class="d-flex gap-2 align-items-center">
                                                <img src="{{ asset('images/push/icons/alarm-1.png') }}"
                                                    id="banner_icon_img" alt=""
                                                    class="message_image img-fluid banner-icon">
                                                <input type="text" class="form-control" id="banner_icon"
                                                    name="banner_icon"
                                                    value="{{ asset('images/push/icons/alarm-1.png') }}" readonly>
                                                <button class="input-group-text upload-icon-btn" type="button" style="margin:inherit"
                                                    data-bs-toggle="modal" data-bs-target="#staticBackdrop"
                                                    id="button2-addon1">
                                                    <i class="fas fa-upload"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="ctaCheckbox">
                                            <label class="form-check-label fw-bold" for="ctaCheckbox">Enable
                                                Call-to-Action Buttons</label>
                                        </div>
                                        <div id="ctaSection" class="cta-section" style="display: none">
                                            <div class="mb-2" id="firstCta">
                                                <h6 class="mb-3">Primary Button</h6>
                                                <div class="row">
                                                    <div class="col-md-4 mb-3">
                                                        <label for="button_1_title" class="form-label">Button Text <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" name="button_1_title"
                                                            id="button_1_title" placeholder="e.g. Read More">
                                                    </div>
                                                    <div class="col-md-8 mb-3">
                                                        <label for="button_1_url" class="form-label">Button URL <span
                                                                class="text-danger">*</span></label>
                                                        <input type="url" class="form-control" name="button_1_url"
                                                            id="button_1_url" placeholder="https://example.com">
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="secondCta" style="display: none">
                                                <hr>
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <h6 class="mb-0">Secondary Button</h6>
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                        id="removeSecondCtaBtn">
                                                        <i class="fas fa-trash"></i> Remove
                                                    </button>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-4 mb-3">
                                                        <label for="button_2_title" class="form-label">Button Text <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" name="button_2_title"
                                                            id="button_2_title" placeholder="e.g. Learn More">
                                                    </div>
                                                    <div class="col-md-8 mb-3">
                                                        <label for="button_2_url" class="form-label">Button URL <span
                                                                class="text-danger">*</span></label>
                                                        <input type="url" class="form-control" name="button_2_url"
                                                            id="button_2_url" placeholder="https://example.com">
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                id="addSecondCtaBtn">
                                                <i class="fas fa-plus"></i> Add Secondary Button
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-12" id="timeIntervalSection" style="display: none">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="fs-20 mb-0">Notification Schedule</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="time-range-container">
                                            <div class="form-group">
                                                <label for="daily_start_time" class="form-label">Daily Start Time <span class="text-danger">*</span></label>
                                                <input type="time" class="form-control" name="daily_start_time" id="daily_start_time" value="09:00" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="daily_end_time" class="form-label">Daily End Time <span class="text-danger">*</span></label>
                                                <input type="time" class="form-control" name="daily_end_time" id="daily_end_time" value="18:00" required>
                                            </div>
                                        </div>
                                        <div class="time-difference">
                                            Available Time Window: <span class="time-difference-value" id="time-difference">9 hours</span>
                                        </div>
                                        <div class="mt-4">
                                            <label class="form-label">Time Difference Between Notifications <span class="text-danger">*</span></label>
                                            <div class="time-options">
                                                <div class="time-option active" data-minutes="5">5 Minutes</div>
                                                <div class="time-option" data-minutes="15">15 Minutes</div>
                                                <div class="time-option" data-minutes="30">30 Minutes</div>
                                                <div class="time-option" data-minutes="60">1 Hour</div>
                                                <div class="time-option" data-minutes="custom">Custom</div>
                                            </div>
                                            <div class="custom-time-input mt-3" style="display: none;">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <label>Hours</label>
                                                        <input type="number" class="form-control" id="customHours" min="0" max="23" value="0">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label>Minutes</label>
                                                        <input type="number" class="form-control" id="customMinutes" min="5" max="59" value="30">
                                                    </div>
                                                </div>
                                            </div>
                                            <input type="hidden" name="interval_minutes" id="interval_minutes" value="5">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-12" id="rssFeedType" style="display: none">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="fs-20 mb-0">RSS Feed Type</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="justify-content-start">
                                            <label class="mb-1 me-3 w-auto d-inline-block" for="rssTypeLatest">
                                                <input class="radio-option" type="radio" name="feed_type"
                                                    id="rssTypeLatest" value="latest" checked>
                                                <span>Latest Feed</span>
                                            </label>
                                            <label class="mb-1 me-3 w-auto d-inline-block" for="rssTypeRandom">
                                                <input class="radio-option" type="radio" name="feed_type"
                                                    id="rssTypeRandom" value="random">
                                                <span>Random Feed</span>
                                            </label>
                                        </div>

                                        <div class="form-group mt-2" id="randomFeed">
                                            <label for="random_feed">Select Number of Random Feeds <span
                                                    class="text-danger">*</span></label>
                                            <select name="random_feed" id="random_feed" class="form-control">
                                                <option value="" disabled selected>Select count</option>
                                                @for ($i = 2; $i <= 20; $i++)
                                                    <option value="{{ $i }}">{{ $i }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

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
                                        <img src="" id="message_image" class="feat_img message_image img-fluid"
                                            alt="" style="display: none;">
                                        <div class="windows_body">
                                            <div class="d-flex align-items-center">
                                                <img src="{{ asset('images/chrome.png') }}" class="me-2"
                                                    alt="">
                                                <span>Google Chrome</span>
                                                <i class="far fa-window-close ms-auto"></i>
                                            </div>
                                            <div class="preview_content">
                                                <div class="flex-shrink-0">
                                                    <img src="{{ asset('images/push/icons/alarm-1.png') }}" id="icon_prv"
                                                        class="img-fluid" alt="">
                                                </div>
                                                <div class="flex-grow-1">
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
                                        <div class="android_body">
                                            <div class="d-flex align-items-center">
                                                <img src="{{ asset('images/chrome.png') }}" class="me-2"
                                                    alt="">
                                                <span>Google Chrome</span>
                                                <span class="ms-auto">
                                                    <i class="far fa-chevron-circle-down fa-lg"></i>
                                                </span>
                                            </div>
                                            <div class="preview_content">
                                                <div class="flex-grow-1">
                                                    <span class="fs-16 text-black prv_title" id="prv_title_android">Title
                                                        Preview</span>
                                                    <p class="card-text fs-14 prv_desc" id="prv_desc_android">Preview of
                                                        Description</p>
                                                </div>
                                                <div class="flex-shrink-0">
                                                    <img src="{{ asset('images/push/icons/alarm-1.png') }}"
                                                        id="icon_prv_android" class="img-fluid" alt="">
                                                </div>
                                            </div>
                                            <img src="" id="message_image_android" class="feat_img img-fluid"
                                                alt="">
                                            <div class="d-flex align-items-center">
                                                <div class="mt-3 me-3 btn_prv" style="display:none;"
                                                    id="btn_prv_android">
                                                    <span id="btn_title1_android"
                                                        class="btn_title1 text-secondary fs-16">Click Here</span>
                                                </div>
                                                <div class="mt-3 me-3 btn2_prv" style="display:none;"
                                                    id="btn2_prv_android">
                                                    <span id="btn_title2_android"
                                                        class="btn_title2 text-secondary fs-16">Click Here</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6" id="btnSaveDiv" style="display: none">
                    <button type="submit" class="btn btn-primary" id="sendNotification">
                        <i class="far fa-save pe-2"></i>Save & Exit
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
                <div class="modal-body d-flex flex-wrap justify-content-between" id="icon-container">
                    <!-- spinner until icons load -->
                    <div id="icon-loader" class="spinner-border m-auto" role="status">
                        <span class="visually-hidden">Loading…</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    @php
        use Illuminate\Support\Facades\File;
        $files = File::files(public_path('images/push/icons'));
        $iconUrls = collect($files)->map(fn($f) => asset('images/push/icons/' . $f->getFilename()))->toJson();
    @endphp
    <script>
        const ICON_URLS = {!! $iconUrls !!};
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

        function setImageUrl(url) {
            document.getElementById('banner_icon').value = url;
            previewIcons(url);
            var modal = document.getElementById('staticBackdrop');
            var modalInstance = bootstrap.Modal.getInstance(modal);
            modalInstance.hide();
        }

        function previewIcons(url){
            $('#banner_icon_img').attr('src', url);
            $('#banner_icon').attr('src', url);
            $('#icon_prv').attr('src', url);
            $('#icon_prv_android').attr('src', url);
        }
    </script>
    <script>
        // Time validation functions
        function calculateTimeDifference() {
            const startTime = $('#daily_start_time').val();
            const endTime = $('#daily_end_time').val();
            
            if (!startTime || !endTime) return true;
            
            const startDate = new Date('2000-01-01 ' + startTime);
            const endDate = new Date('2000-01-01 ' + endTime);
            const minEndDate = new Date(startDate.getTime() + 5 * 60000); // 5 minutes later
            
            // Clear previous errors
            $('#daily_end_time').removeClass('is-invalid');
            $('.time-difference-error').remove();
            $('.time-option').removeClass('disabled');
            
            if (endDate < startDate) {
                showTimeError('End time must be after start time');
                return false;
            }
            
            if (endDate < minEndDate) {
                showTimeError('End time must be at least 5 minutes after start time');
                return false;
            }
            
            const diffMs = endDate - startDate;
            const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
            const diffMinutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
            const totalMinutes = (diffHours * 60) + diffMinutes;
            
            let diffText = '';
            if (diffHours > 0) {
                diffText += diffHours + ' hour' + (diffHours !== 1 ? 's' : '');
            }
            if (diffMinutes > 0) {
                if (diffText) diffText += ' ';
                diffText += diffMinutes + ' minute' + (diffMinutes !== 1 ? 's' : '');
            }
            
            $('#time-difference').html('<span class="time-difference-value">' + diffText + '</span>');
            
            // Disable options that exceed available time
            $('.time-option').each(function() {
                const optionMinutes = $(this).data('minutes');
                if (optionMinutes !== 'custom' && optionMinutes > totalMinutes) {
                    $(this).addClass('disabled');
                }
            });
            
            // Validate current interval selection
            validateCurrentInterval(totalMinutes);
            return true;
        }

        function showTimeError(message) {
            $('#daily_end_time').addClass('is-invalid');
            $('.time-difference').html(
                '<span class="text-danger time-difference-error">' + message + '</span>'
            );
            $('.time-option').addClass('disabled');
            $('#interval_minutes').val('');
        }


        function validateCurrentInterval(availableMinutes) {
            const selectedOption = $('.time-option.active');
            if (selectedOption.length === 0) return;

            if (selectedOption.data('minutes') === 'custom') {
                const hours = parseInt($('#customHours').val()) || 0;
                const minutes = parseInt($('#customMinutes').val()) || 0;
                const totalCustomMinutes = (hours * 60) + minutes;
                
                if (totalCustomMinutes > availableMinutes) {
                    showIntervalError('Custom interval exceeds available time window');
                    return false;
                } else if (totalCustomMinutes < 5) {
                    showIntervalError('Minimum interval is 5 minutes');
                    return false;
                } else {
                    clearIntervalError();
                    $('#interval_minutes').val(totalCustomMinutes);
                    return true;
                }
            } else {
                const optionMinutes = parseInt(selectedOption.data('minutes'));
                if (optionMinutes > availableMinutes) {
                    showIntervalError('Selected interval exceeds available time window');
                    return false;
                } else {
                    clearIntervalError();
                    $('#interval_minutes').val(optionMinutes);
                    return true;
                }
            }
        }

        function showIntervalError(message) {
            clearIntervalError();
            $('.time-difference').append(
                '<div class="text-danger time-difference-error">' + message + '</div>'
            );
            return false;
        }

        function clearIntervalError() {
            $('.time-difference-error').remove();
        }


        function updateCustomTime() {
            const startTime = $('#daily_start_time').val();
            const endTime = $('#daily_end_time').val();
            
            if (!startTime || !endTime) return false;
            
            const startDate = new Date('2000-01-01 ' + startTime);
            const endDate = new Date('2000-01-01 ' + endTime);
            const availableMinutes = Math.floor((endDate - startDate) / (1000 * 60));
            
            const hours = parseInt($('#customHours').val()) || 0;
            const minutes = parseInt($('#customMinutes').val()) || 0;
            const totalCustomMinutes = (hours * 60) + minutes;
            
            if (totalCustomMinutes > availableMinutes) {
                return showIntervalError('Custom interval exceeds available time window');
            } else if (totalCustomMinutes < 5) {
                return showIntervalError('Minimum interval is 5 minutes');
            } else {
                clearIntervalError();
                $('#interval_minutes').val(totalCustomMinutes);
                return true;
            }
        }

        // Event handlers
        $(document).ready(function() {
            // Initialize time difference calculation
            calculateTimeDifference();

            // Time input change handlers
            $('#daily_start_time, #daily_end_time').on('change', function() {
                calculateTimeDifference();
            });

            // Time option selection
            $(document).on('click', '.time-option:not(.disabled)', function() {
                if ($(this).hasClass('disabled')) return;
                
                $('.time-option').removeClass('active');
                $(this).addClass('active');
                
                if ($(this).data('minutes') === 'custom') {
                    $('.custom-time-input').show();
                    updateCustomTime();
                } else {
                    $('.custom-time-input').hide();
                    const startTime = $('#daily_start_time').val();
                    const endTime = $('#daily_end_time').val();
                    
                    if (startTime && endTime) {
                        const startDate = new Date('2000-01-01 ' + startTime);
                        const endDate = new Date('2000-01-01 ' + endTime);
                        const availableMinutes = Math.floor((endDate - startDate) / (1000 * 60));
                        const optionMinutes = $(this).data('minutes');
                        
                        if (optionMinutes > availableMinutes) {
                            showIntervalError('Selected interval exceeds available time window');
                            $('#interval_minutes').val('');
                        } else {
                            clearIntervalError();
                            $('#interval_minutes').val(optionMinutes);
                        }
                    }
                }
            });

            // Custom time input changes
            $('#customHours, #customMinutes').on('input', function() {
                if ($('.time-option.active').data('minutes') === 'custom') {
                    updateCustomTime();
                }
            });

            // Form submission validation
            $('#rss_create').on('submit', function(e) {
                // First validate time range
                if (!calculateTimeDifference()) {
                    e.preventDefault();
                    return false;
                }
                
                // Then validate interval selection
                const selectedOption = $('.time-option.active');
                if (selectedOption.length === 0) {
                    showIntervalError('Please select a time interval');
                    e.preventDefault();
                    return false;
                }
                
                if (selectedOption.data('minutes') === 'custom') {
                    if (!updateCustomTime()) {
                        e.preventDefault();
                        return false;
                    }
                }
                
                return true;
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            // Form validation
            $('#rss_create').validate({
                rules: {
                    rssfeedname: {
                        required: true,
                        maxlength: 100
                    },
                    rssFeedUrl: {
                        required: true,
                        url: true
                    },
                    random_feed: {
                        required: function() {
                            return $('#rssTypeRandom').is(':checked');
                        }
                    },
                    banner_image: {
                        required: true,
                        url: true
                    },
                    daily_start_time: {
                        required: true
                    },
                    daily_end_time: {
                        required: true,
                        greaterThanStart: true
                    },
                    button_1_title: {
                        required: function() {
                            return $('#ctaCheckbox').is(':checked');
                        }
                    },
                    button_1_url: {
                        required: function() {
                            return $('#ctaCheckbox').is(':checked');
                        },
                        url: true
                    },
                    button_2_title: {
                        required: function() {
                            return $('#ctaCheckbox').is(':checked') && $('#secondCta').is(':visible');
                        }
                    },
                    button_2_url: {
                        required: function() {
                            return $('#ctaCheckbox').is(':checked') && $('#secondCta').is(':visible');
                        },
                        url: true
                    }
                },
                messages: {
                    rssfeedname: {
                        required: "Please enter a feed name",
                        maxlength: "Feed name cannot exceed 100 characters"
                    },
                    rssFeedUrl: {
                        required: "Please enter a valid RSS feed URL",
                        url: "Please enter a valid URL starting with http:// or https://"
                    },
                    random_feed: "Please select number of random feeds",
                    banner_image: {
                        required: "Please select a banner image",
                        url: "Please enter a valid image URL"
                    },
                    daily_start_time: "Please select a start time",
                    daily_end_time: {
                        required: "Please select an end time",
                        greaterThanStart: "End time must be after start time"
                    },
                    button_1_title: "Please enter button text",
                    button_1_url: {
                        required: "Please enter button URL",
                        url: "Please enter a valid URL"
                    },
                    button_2_title: "Please enter button text",
                    button_2_url: {
                        required: "Please enter button URL",
                        url: "Please enter a valid URL"
                    }
                },
                errorElement: 'div',
                errorPlacement: function(error, element) {
                    error.addClass('invalid-feedback');
                    element.closest('.form-group').append(error);
                },
                highlight: function(element, errorClass, validClass) {
                    $(element).addClass('is-invalid').removeClass('is-valid');
                },
                unhighlight: function(element, errorClass, validClass) {
                    $(element).removeClass('is-invalid').addClass('is-valid');
                }
            });

            // Custom validation method for end time
            $.validator.addMethod("greaterThanStart", function(value, element) {
                const startTime = $('#daily_start_time').val();
                const endTime = $(element).val();
                
                if (!startTime || !endTime) return true;
                
                const startDate = new Date('2000-01-01 ' + startTime);
                const endDate = new Date('2000-01-01 ' + endTime);
                const minEndDate = new Date(startDate.getTime() + 5 * 60000); // 5 minutes later
                
                return endDate >= minEndDate;
            }, "End time must be at least 5 minutes after start time");

            // Add event handler to prevent form submission if time validation fails
            $('#rss_create').on('submit', function(e) {
                if (!calculateTimeDifference()) {
                    e.preventDefault();
                    return false;
                }
                return true;
            })


            // Show/hide random feed options
            $('input[name="feed_type"]').change(function() {
                if ($(this).val() === 'random') {
                    $('#randomFeed').show();
                    $('#random_feed').attr('required', true);
                } else {
                    $('#randomFeed').hide();
                    $('#random_feed').removeAttr('required');
                }
            });

            // Time interval selection
            $('.time-option').click(function() {
                $('.time-option').removeClass('active');
                $(this).addClass('active');

                if ($(this).data('minutes') === 'custom') {
                    $('.custom-time-input').show();
                    updateCustomTime();
                } else {
                    $('.custom-time-input').hide();
                    $('#interval_minutes').val($(this).data('minutes'));
                }
            });

            // Update custom time when inputs change
            $('#customHours, #customMinutes').change(function() {
                updateCustomTime();
            });

            function updateCustomTime() {
                const hours = parseInt($('#customHours').val()) || 0;
                const minutes = parseInt($('#customMinutes').val()) || 0;
                const totalMinutes = (hours * 60) + minutes;
                $('#interval_minutes').val(totalMinutes);
            }

            // Preview type toggle
            $('input[name="preview_type"]').change(function() {
                if ($(this).val() === 'preview_windows') {
                    $('.windows_view').show();
                    $('.android_view').hide();
                } else {
                    $('.windows_view').hide();
                    $('.android_view').show();
                }
            });

            function updatePreview(title, description, image) {
                // Update Windows preview
                $('#prv_title').text(title);
                $('#prv_desc').text(description);

                // Update Android preview
                $('#prv_title_android').text(title);
                $('#prv_desc_android').text(description);

                // Update image if available
                if (image) {
                    $('#message_image').attr('src', image).show();
                    $('#message_image_android').attr('src', image).show();
                } else {
                    $('#message_image').hide();
                    $('#message_image_android').hide();
                }
            }

            // Fetch RSS data
            $('#fetchRssData').click(function() {
                const feedUrl = $('#rssFeedUrlLink').val().trim();
                const feedName = $('#rssfeedname').val().trim();

                if (!feedName) {
                    $('#rssfeedname').addClass('is-invalid');
                    return;
                }

                if (!feedUrl) {
                    $('#rssFeedUrlLink').addClass('is-invalid');
                    $('#message').html('<span class="text-danger">Feed URL cannot be empty!</span>');
                    return;
                }

                // Validate URL format
                if (!isValidUrl(feedUrl)) {
                    $('#message').html(
                        '<span class="text-danger">Please enter a valid URL starting with http:// or https://</span>'
                        );
                    return;
                }

                $('#fetchRssData').prop('disabled', true);
                $('#fetchText').text('Fetching...');
                $('#fetchSpinner').show();
                $('#message').html('<span class="text-info">Fetching feed, please wait...</span>');

                $.ajax({
                    url: '{{ route('rss.fetch') }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        feed: feedUrl
                    },
                    success: function(response) {
                        $('#fetchText').text('Fetch Feed');
                        $('#fetchSpinner').hide();

                        if (response.status) {
                            $('#message').html(
                                '<span class="text-success">Feed loaded successfully!</span>'
                                );
                            displayFeedItems(response.items);

                            // Show additional sections
                            $('#feedPreview, #feedNotifyData, #rssFeedType, #timeIntervalSection, #additionalOptions, #btnSaveDiv')
                                .show();

                            // Disable the URL field after successful fetch
                            $('#rssFeedUrlLink').prop('readonly', true);
                            $('#fetchRssData').prop('disabled', true);

                            // Update banner image preview if available
                            if (response.items.length > 0 && response.items[0].image) {
                                const firstItem = response.items[0];

                                $('#banner_url').attr('src', firstItem.image);
                                $('#banner_image').val(firstItem.image);
                                $('#message_image').attr('src', firstItem.image).show();
                                $('#message_image_android').attr('src', firstItem.image).show();

                                // Update preview
                                updatePreview(firstItem.title, firstItem.description, firstItem
                                    .image);
                            }

                            // Initialize time difference calculation
                            calculateTimeDifference();
                        } else {
                            $('#fetchRssData').prop('disabled', false);
                            $('#message').html('<span class="text-danger">' + (response
                                .message || 'Failed to load feed') + '</span>');
                        }
                    },
                    error: function(xhr) {
                        $('#fetchRssData').prop('disabled', false);
                        $('#fetchText').text('Fetch Feed');
                        $('#fetchSpinner').hide();
                        let errorMsg = 'Error fetching feed';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        $('#message').html('<span class="text-danger">' + errorMsg + '</span>');
                    }
                });
            });

            function isValidUrl(string) {
                try {
                    new URL(string);
                    return true;
                } catch (_) {
                    return false;
                }
            }

            function displayFeedItems(items) {
                const container = $('#feed-container');
                container.empty();

                if (items.length === 0) {
                    container.html('<div class="alert alert-warning">No feed items found</div>');
                    return;
                }

                items.forEach((item, index) => {
                    const feedItem = $(`
                <div class="feed-item" data-index="${index}">
                    <div class="preview-image">
                        <img src="${item.image || '{{ asset('images/default-feed.png') }}"'}" alt="Feed Image">
                    </div>
                    <div class="feed-content">
                        <h5>${item.title}</h5>
                        <p>${item.description}</p>
                    </div>
                </div>
            `);

                    feedItem.click(function() {
                        $('.feed-item').removeClass('active');
                        $(this).addClass('active');

                        // Update banner image preview if available
                        if (item.image) {
                            $('#banner_url').attr('src', item.image);
                            $('#banner_image').val(item.image);
                            $('#message_image').attr('src', item.image ||
                                '{{ asset('images/default-banner.png') }}').show();
                            $('#message_image_android').attr('src', item.image ||
                                '{{ asset('images/default-banner.png') }}').show();
                        }

                        updatePreview(item.title, item.description, item.image);
                    });

                    container.append(feedItem);
                });

                // Select first item by default
                container.find('.feed-item:first').click();
            }

            $(document).on('change', '#ctaCheckbox', function() {
                toggleCtaSection();
            });

            // CTA section toggle
            function toggleCtaSection() {
                if ($('#ctaCheckbox').is(':checked')) {
                    $('#ctaSection').show();
                    $('#btn_prv, #btn_prv_android').show();
                } else {
                    $('#ctaSection').hide();
                    $('#btn_prv, #btn_prv_android').hide();
                    $('#secondCta').hide();
                    $('#btn2_prv, #btn2_prv_android').hide();
                    $('#addSecondCtaBtn').show();
                }
            }

            $(document).on('click', '#addSecondCtaBtn', function() {
                addSecondCta();
            });

            // Add second CTA button
            function addSecondCta() {
                $('#secondCta').show();
                $('#btn2_prv, #btn2_prv_android').show();
                $('#addSecondCtaBtn').hide();
            }

            $(document).on('click', '#removeSecondCtaBtn', function() {
                removeSecondCta();
            });

            // Remove second CTA button
            function removeSecondCta() {
                $('#secondCta').hide();
                $('#btn2_prv, #btn2_prv_android').hide();
                $('#addSecondCtaBtn').show();
                $('#button_2_title, #button_2_url').val('');
            }


            $(document).on('input', '#button_1_title', function() {
                $(`#btn_title1`).text($(this).val() || 'Click Here');
                $(`#btn_title1_android`).text($(this).val() || 'Click Here');
            });

            $(document).on('input', '#button_2_title', function() {
                $(`#btn_title2`).text($(this).val() || 'Click Here');
                $(`#btn_title2_android`).text($(this).val() || 'Click Here');
            });

            // Initialize sections
            $('#feedPreview, #feedNotifyData, #rssFeedType, #timeIntervalSection, #additionalOptions, #btnSaveDiv')
                .hide();
            $('#ctaSection').hide();
            $('#secondCta').hide();
            $('#btn2_prv, #btn2_prv_android').hide();
            $('#addSecondCtaBtn').show();
            $('#randomFeed').hide();
            $('#random_feed').removeAttr('required');
            $('#btn_prv, #btn_prv_android').hide();
            $('#btn2_prv, #btn2_prv_android').hide();

            // Initialize event handlers
            $('#ctaCheckbox').change(function() {
                toggleCtaSection();
            });

            $('input[name="feed_type"]').change(function() {
                if ($(this).val() === 'random') {
                    $('#randomFeed').show();
                } else {
                    $('#randomFeed').hide();
                }
            });

            $('input[name="preview_type"]').change(function() {
                if ($(this).val() === 'preview_windows') {
                    $('.windows_view').show();
                    $('.android_view').hide();
                } else {
                    $('.windows_view').hide();
                    $('.android_view').show();
                }
            });

            // Initialize time difference calculation
            calculateTimeDifference();
        });
    </script>
@endpush
