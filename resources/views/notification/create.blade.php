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
    <section class="content-body">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center text-head">
                <h2 class="mb-3 me-auto">Add Notifications</h2>
            </div>
            <form action="{{ route('notification.send') }}" method="post" id="notificationform" enctype="multipart/form-data">
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
                                                <input type="url" class="form-control" name="banner_image" id="image_input"
                                                    placeholder="e.g.: https://example.com/image.jpg"
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
                                                <input type="text" class="form-control banner_icon_trans" name="banner_icon"
                                                    id="target" placeholder="Select Icon"
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
                        </div>

                        <!-- Domain Selection -->
                        <div class="card h-auto" id="domainCard">
                            <div class="card-header">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="select_all"
                                        id="select_all_domain" value="Select All">
                                    <label class="form-check-label" for="select_all_domain">Select All</label>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row scrollbar" id="domain-list">
                                    @foreach ($domains as $domain)
                                        <div class="col-lg-6 col-md-6 col-12 domain-item">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" name="domain_name[]"
                                                    id="domain-{{ $domain->id }}" value="{{ $domain->name }}">
                                                <label class="form-check-label" for="domain-{{ $domain->id }}">
                                                    {{ $domain->name }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
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
                                            value="Instant" onchange="hideorshow(event)" checked>
                                        <span>Instant Notification</span>
                                    </label>
                                    <label class="mb-3 me-3 w-auto d-inline-block" for="Schedule">
                                        <input class="radio-option" type="radio" name="schedule_type" id="Schedule"
                                            value="Schedule" onchange="hideorshow(event)">
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
                                            <!-- Static min datetime set to June 2, 2025 00:00 -->
                                            <div class="row">
                                                <div class="col-12 mb-3">
                                                    <div class="form-group">
                                                        <label for="one_time_start_date">Start Date & Time <span
                                                                class="text-danger">*</span></label>
                                                        <input type="datetime-local" class="form-control"
                                                            name="one_time_datetime" id="one_time_start_date"
                                                            min="2025-06-02T00:00" required>
                                                        <div class="invalid-feedback"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Recurring Tab -->
                                        <div class="tab-pane fade mb-3 p-3 border" id="recurring" role="tabpanel">
                                            <div class="row">
                                                <div class="col-lg-6 col-md-6 col-12 mb-3">
                                                    <div class="form-group">
                                                        <label for="recurring_start_date">Start Date <span
                                                                class="text-danger">*</span></label>
                                                        <input type="date" class="form-control"
                                                            name="recurring_start_date" id="recurring_start_date"
                                                            placeholder="Enter date" min="2025-06-02" required>
                                                        <div class="invalid-feedback"></div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 col-md-6 col-12 mb-3">
                                                    <div class="form-group">
                                                        <div class="d-flex justify-content-between">
                                                            <label for="recurring_end_date">End Date <span
                                                                    class="text-danger">*</span></label>
                                                            <div>
                                                                <input class="form-check-input" type="checkbox"
                                                                    id="end_date" onchange="disableDate()">
                                                                <label class="form-check-label mt-0" for="end_date">Never
                                                                    Ends</label>
                                                            </div>
                                                        </div>
                                                        <input type="date" class="form-control"
                                                            id="recurring_end_date" name="recurring_end_date"
                                                            placeholder="Enter date" min="2025-06-02" required>
                                                        <div class="invalid-feedback"></div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 col-md-6 col-12 mb-3">
                                                    <div class="form-group">
                                                        <label for="occurrence">Repeat <span
                                                                class="text-danger">*</span></label>
                                                        <select class="form-control form-select" id="occurrence"
                                                            name="occurrence" required>
                                                            <option value="">Select</option>
                                                            <option value="daily">Daily</option>
                                                            <option value="weekly">Weekly</option>
                                                            <option value="monthly">Monthly</option>
                                                        </select>
                                                        <div class="invalid-feedback"></div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 col-md-6 col-12 mb-3">
                                                    <div class="form-group">
                                                        <label for="recurring_start_time">Start Time <span
                                                                class="text-danger">*</span></label>
                                                        <input type="time" class="form-control"
                                                            name="recurring_start_time" id="recurring_start_time"
                                                            placeholder="Enter time" required>
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
                                        <div class="windows_body mt-3">
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
                <div class="modal-body d-flex flex-wrap">
                    @php
                        use Illuminate\Support\Facades\File;
                        // Fetch every file in public/images/push/icons
                        $files = File::files(public_path('images/push/icons'));
                    @endphp

                    @foreach ($files as $file)
                        @php
                            $filename = $file->getFilename();
                        @endphp
                        <div class="m-1">
                            <img src="{{ asset('images/push/icons/' . $filename) }}" class="img-thumbnail p-2"
                                alt="{{ $filename }}" onclick="setImageUrl(this.src)" width="52"
                                height="52">
                        </div>
                    @endforeach
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
        // Show or hide schedule fields
        function hideorshow(e) {
            const scheduleOptions = document.getElementById("schedule_options");
            const oneTimeDateInput = document.getElementById("one_time_start_date");
            const recurringStartDateInput = document.getElementById("recurring_start_date");
            const recurringEndDateInput = document.getElementById("recurring_end_date");
            const occurrenceSelect = document.getElementById("occurrence");
            const recurringStartTimeInput = document.getElementById("recurring_start_time");

            if (e.target.value === 'Instant') {
                scheduleOptions.style.display = 'none';
                oneTimeDateInput.removeAttribute('required');
                recurringStartDateInput.removeAttribute('required');
                recurringEndDateInput.removeAttribute('required');
                occurrenceSelect.removeAttribute('required');
                recurringStartTimeInput.removeAttribute('required');
            } else if (e.target.value === 'Schedule') {
                scheduleOptions.style.display = 'block';
                oneTimeDateInput.setAttribute('required', true);
                recurringStartDateInput.setAttribute('required', true);
                recurringEndDateInput.setAttribute('required', true);
                occurrenceSelect.setAttribute('required', true);
                recurringStartTimeInput.setAttribute('required', true);
            }
        }

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

        // Form validation using <div> for errors
        // $(document).ready(function() {
        //     $("#notificationform").validate({
        //         errorElement: "div",
        //         errorClass: "invalid-feedback",
        //         rules: {
        //             target_url: {
        //                 required: true,
        //                 url: true
        //             },
        //             campaign_name: { required: true },
        //             title: { required: true },
        //             description: { required: true },
        //             // Removed CTA-related rules
        //             one_time_datetime: { required: function() {
        //                 return $('#Schedule').is(':checked');
        //             }},
        //             recurring_start_date: { required: function() {
        //                 return $('#Schedule').is(':checked');
        //             }},
        //             recurring_end_date: { required: function() {
        //                 return $('#Schedule').is(':checked') && !$('#end_date').is(':checked');
        //             }},
        //             occurrence: { required: function() {
        //                 return $('#Schedule').is(':checked');
        //             }},
        //             recurring_start_time: { required: function() {
        //                 return $('#Schedule').is(':checked');
        //             }}
        //         },
        //         messages: {
        //             target_url: "Please enter a valid URL.",
        //             title: "Title is required.",
        //             description: "Notification Message is required.",
        //             one_time_datetime: "Start Date & Time is required when scheduling.",
        //             recurring_start_date: "Recurring Start Date is required when scheduling.",
        //             recurring_end_date: "Recurring End Date is required unless 'Never Ends' is checked.",
        //             occurrence: "Please select a repeat interval.",
        //             recurring_start_time: "Recurring Start Time is required when scheduling."
        //         },
        //         errorPlacement: function(error, element) {
        //             // Insert <div class="invalid-feedback"> immediately after the input
        //             error.insertAfter(element);
        //         },
        //         highlight: function(element) {
        //             $(element).addClass("is-invalid");
        //         },
        //         unhighlight: function(element) {
        //             $(element).removeClass("is-invalid");
        //         },
        //         submitHandler: function(form) {
        //             var $btn = $('#sendNotification');
        //             $btn.prop('disabled', true);
        //             $('#sendNotification').text(' Processing...');
        //             $btn.prepend('<i class="fas fa-spinner fa-spin me-2"></i>');
        //             form.submit();
        //         }
        //     });
        // });
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
                ignore: [], // include checkboxes
                rules: {
                    target_url: {
                        required: true,
                        url: true
                    },
                    campaign_name: {
                        required: true,
                        campformat: true
                    },
                    title: 'required',
                    description: 'required',
                    'domain_name[]': {
                        required: true
                    },
                    one_time_datetime: {
                        required: function() {
                            return $('#Schedule').is(':checked');
                        }
                    },
                    recurring_start_date: {
                        required: function() {
                            return $('#Schedule').is(':checked');
                        }
                    },
                    recurring_end_date: {
                        required: function() {
                            return $('#Schedule').is(':checked') && !$('#end_date').is(':checked');
                        }
                    },
                    occurrence: {
                        required: function() {
                            return $('#Schedule').is(':checked');
                        }
                    },
                    recurring_start_time: {
                        required: function() {
                            return $('#Schedule').is(':checked');
                        }
                    }
                },
                messages: {
                    target_url: 'Please enter a valid URL.',
                    campaign_name: 'Campaign Name is required and must match CAMP#1234.',
                    title: 'Title is required.',
                    description: 'Notification Message is required.',
                    'domain_name[]': 'Please select at least one domain.',
                    one_time_datetime: 'Start Date & Time is required when scheduling.',
                    recurring_start_date: 'Recurring Start Date is required.',
                    recurring_end_date: 'Recurring End Date is required unless “Never Ends” is checked.',
                    occurrence: 'Please select a repeat interval.',
                    recurring_start_time: 'Recurring Start Time is required.'
                },
                errorPlacement: function(err, el) {
                    if (el.attr('name') === 'domain_name[]') {
                        err.appendTo('#domain-error-span');
                    } else {
                        err.insertAfter(el);
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
        $(function() {
            // — GET META DATA —
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

                var $btn = $(this)
                    .prop('disabled', true)
                    .html('<span class="spinner-border spinner-border-sm me-2"></span>Fetching...');

                $.ajax({
                        url: "{{ route('notification.fetchMeta') }}",
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            target_url: url
                        }
                    })
                    .done(function(res) {
                        if (res.success) {
                            if (res.data.title) {
                                $('#title').val(res.data.title).trigger('keyup');
                            }
                            if (res.data.description) {
                                $('#description').val(res.data.description).trigger('keyup');
                            }
                            if (res.data.image) {
                                $('#image_input').val(res.data.image);
                                changeBanner(res.data.image);
                            }
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
                        $btn.prop('disabled', false)
                            .html('<i class="far fa-wand"></i> Get Data');
                    });
            });

            // — FORM VALIDATION —
            $("#notificationform").validate({
                errorElement: 'div',
                errorClass: 'invalid-feedback',
                ignore: [], // so unchecked checkboxes get validated
                rules: {
                    target_url: {
                        required: true,
                        url: true
                    },
                    campaign_name: 'required',
                    title: 'required',
                    description: 'required',

                    // scheduling rules omitted for brevity…

                    'domain_name[]': {
                        required: true
                    }
                },
                messages: {
                    target_url: 'Please enter a valid URL.',
                    title: 'Title is required.',
                    description: 'Notification Message is required.',
                    'domain_name[]': 'Please select at least one domain.'
                },
                errorPlacement: function(error, element) {
                    if (element.attr('name') === 'domain_name[]') {
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
                    var $btn = $('#sendNotification');
                    $btn.prop('disabled', true)
                        .html('<i class="fas fa-spinner fa-spin me-2"></i> Processing...');
                    form.submit();
                }
            });
        });
    </script>
@endpush