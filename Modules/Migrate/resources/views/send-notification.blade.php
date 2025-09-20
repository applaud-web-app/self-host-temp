{{-- resources/views/notifications/send_dummy.blade.php --}}
@extends('layouts.master')

@push('styles')
    <style>
        @media (min-width: 768px) {
            .sticky { position: -webkit-sticky; position: sticky; top: 100px; }
        }
        #domain-list { margin-bottom: 10px; max-height: 320px; }
        .domain-input { height: 40px; width: 100%; padding-left: 10px; padding-right: 30px; }
        .invalid-feedback { display: none; }
        .is-invalid + .invalid-feedback { display: block; }
        .banner-model-img { height: 140px; object-fit: contain; width: 140px !important; }
    </style>
@endpush

@section('content')
<section class="content-body" id="create_notification_page">
    <div class="container-fluid">
        <div class="d-flex flex-wrap align-items-center text-head">
            <h2 class="mb-3 me-auto">Add Notifications</h2>
        </div>

        <form action="#" method="post" id="notificationform" enctype="multipart/form-data" novalidate>
            @csrf
            <div class="row">
                <!-- LEFT -->
                <div class="col-lg-7 col-md-7 col-12">

                    <!-- Landing URL -->
                    <div class="card h-auto">
                        <div class="card-body">
                            <label for="target_url">
                                Landing Page URL
                                <i class="fal fa-info-circle" data-bs-toggle="tooltip" data-bs-placement="top"
                                   title="Warning: Do not use Bitly or any URL shorteners."></i>
                                <small class="text-muted">(include <u>https</u> in the URL)</small>
                                <span class="text-danger">*</span>
                            </label>
                            <div class="d-flex align-items-start">
                                <div class="form-group flex-grow-1">
                                    <input type="text" class="form-control notification" name="target_url" id="target_url"
                                           placeholder="https://example.com" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="ms-2">
                                    <button type="button" class="btn btn-outline-secondary text-wrap notification" id="getData">
                                        <i class="far fa-wand"></i> Get Data
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Title / Description -->
                    <div class="card h-auto">
                        <div class="card-body">
                            <div class="row" id="metaForm">
                                <div class="col-12 mb-3">
                                    <label for="title">Title <span class="text-danger">*</span></label>
                                    <input type="text" onkeyup="titleText(this)" class="form-control emoji_picker notification"
                                           name="title" id="title" placeholder="Enter Title" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="description">Notification Message <span class="text-danger">*</span></label>
                                    <textarea class="form-control emoji_picker notification" onkeyup="descriptionText(this.value)"
                                              name="description" id="description" required placeholder="Notification description"
                                              style="height: 100px;"></textarea>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Images -->
                    <div class="card h-auto">
                        <div class="card-header">
                            <h4 class="card-title fs-20 mb-0">Notification Image</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Banner Image -->
                                <div class="col-12 mb-3">
                                    <h5 class="d-flex align-items-center justify-content-between mb-3">
                                        <span>Banner Image</span>
                                        <span class="custom-radio ms-auto d-inline-flex align-items-center gap-1">
                                            <label class="mb-0">
                                                <input type="radio" name="banner_src_type" id="banner_src_url" value="url" checked>
                                                <span class="py-2 px-3" style="font-size:12px;">URL</span>
                                            </label>
                                            <label class="mb-0">
                                                <input type="radio" name="banner_src_type" id="banner_src_upload" value="upload">
                                                <span class="py-2 px-3" style="font-size:12px;">Upload</span>
                                            </label>
                                        </span>
                                    </h5>
                                    <div class="userprofile align-items-start">
                                        <img src="{{ asset('images/default.png') }}" id="banner_image" alt="Banner" class="img-fluid upimage">
                                        <div class="input-group">
                                            <!-- URL mode -->
                                            <div id="banner_url_group" class="w-100">
                                                <div class="input-group">
                                                    <input type="url" class="form-control" name="banner_image" id="image_input"
                                                           placeholder="e.g.: https://example.com/image.jpg"
                                                           aria-label="Banner Image URL" onchange="changeBanner(this.value)" />
                                                    <button class="input-group-text" type="button" style="margin:inherit"
                                                            data-bs-toggle="modal" data-bs-target="#bannerImg" id="choose-banner">
                                                        <i class="fas fa-upload"></i> Choose
                                                    </button>
                                                </div>
                                                <small class="text-muted d-block mt-1">Paste a direct image URL (https://…)</small>
                                            </div>
                                            <!-- Upload mode -->
                                            <div id="banner_upload_group" class="w-100" style="display:none">
                                                <div class="input-group">
                                                    <input type="file" class="form-control" name="banner_image_file" id="banner_image_file" accept="image/*" disabled>
                                                </div>
                                                <small class="text-muted d-block mt-1">Max 1MB. JPG, JPEG, PNG, GIF, WEBP.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Banner Icon -->
                                <div class="col-12 mb-3">
                                    <h5>Banner Icon <span class="text-danger">*</span></h5>
                                    <div class="userprofile">
                                        <img src="{{ asset('images/push/icons/alarm-1.png') }}" id="banner_icon" alt="Icon" class="img-fluid upimage">
                                        <div class="input-group">
                                            <input type="text" class="form-control banner_icon_trans" name="banner_icon" id="target"
                                                   placeholder="Select Icon"
                                                   value="{{ asset('images/push/icons/alarm-1.png') }}"
                                                   onchange="prv_icons(this.value)">
                                            <button class="input-group-text" type="button" style="margin:inherit"
                                                    data-bs-toggle="modal" data-bs-target="#staticBackdrop" id="button2-addon1">
                                                <i class="fas fa-upload"></i> Choose
                                            </button>
                                            <button class="input-group-text d-none" type="button" style="margin:inherit"
                                                    id="button2-reset" onclick="resetIcon()">Reset</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CTA -->
                        <div class="card-footer">
                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input" id="ctaCheckbox" name="cta_enabled" value="1">
                                <label class="form-check-label" for="ctaCheckbox">Enable CTA's Section</label>
                            </div>
                            <div id="cardContainer" class="cardContainer" style="display:none">
                                <div class="border p-3 rounded-1 mb-2" id="first_btn">
                                    <h5 class="mb-2 badge badge-primary rounded-0">Button 1</h5>
                                    <div class="row">
                                        <div class="col-lg-3 col-12">
                                            <label for="titleInput1" class="form-label">Title: <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="titleInput1" placeholder="Click Here"
                                                   onkeyup="btn_title_prv1(this.value)" name="btn_1_title" required>
                                        </div>
                                        <div class="col-lg-9 col-12">
                                            <label for="urlInput1" class="form-label">Landing URL: <span class="text-danger">*</span></label>
                                            <input type="url" class="form-control" name="btn_1_url" id="urlInput1" placeholder="Enter URL" required>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm mt-3 btn-outline-secondary" onclick="toggleSecondBtn()">
                                        <i class="fas fa-plus"></i> Add Another
                                    </button>
                                </div>
                                <div id="second_btn" class="border p-3 rounded-1 mb-2" style="display:none">
                                    <h5 class="mb-2 badge badge-secondary rounded-0">Button 2</h5>
                                    <div class="row">
                                        <div class="col-lg-3">
                                            <label class="form-label">Title: <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="btn_title_2" placeholder="Click Here"
                                                   onkeyup="btn_title_prv2(this.value)" required>
                                        </div>
                                        <div class="col-lg-9">
                                            <label class="form-label">Landing URL: <span class="text-danger">*</span></label>
                                            <input type="url" class="form-control" name="btn_url_2" id="urlInput2" placeholder="Enter URL" required>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm mt-3 btn-outline-danger" onclick="removeSecondBtn()">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Domain Selection (always visible) -->
                    <div class="card h-auto" id="domainCard">
                        <div class="card-header d-flex align-items-center gap-3">
                            <div class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="checkbox" name="select_all" id="select_all_domain" value="Select All">
                                <label class="form-check-label" for="select_all_domain">Select All</label>
                            </div>
                            <input type="search" id="domainFilter" class="form-control domain-input" placeholder="Search domains…" style="max-width: 260px;">
                        </div>
                        <div class="card-body">
                            <div class="row scrollbar" id="domain-list"></div>
                            <span id="domain-error-span"></span>
                        </div>
                    </div>

                    {{-- 🔥 Notification Timing REMOVED entirely --}}

                </div>

                <!-- RIGHT: Preview -->
                <div class="col-lg-5 col-md-5 col-12">
                    <div id="stickyElement" class="sticky">
                        <div class="card h-auto">
                            <div class="card-body p-3">
                                <div class="custom-radio justify-content-start">
                                    <label class="mb-3 w-auto d-inline-block" for="preview_windows">
                                        <input type="radio" name="preview_type" id="preview_windows" value="preview_windows" checked>
                                        <span>Windows</span>
                                    </label>
                                    <label class="mb-3 w-auto d-inline-block" for="preview_android">
                                        <input type="radio" name="preview_type" id="preview_android" value="preview_android">
                                        <span>Android</span>
                                    </label>
                                </div>

                                <div class="windows_view">
                                    <img src="" id="message_image" class="feat_img img-fluid message_image" alt="" style="display:none;">
                                    <div class="windows_body">
                                        <div class="d-flex align-items-center">
                                            <img src="{{ asset('images/chrome.png') }}" class="me-2" alt="Chrome" width="20">
                                            <span>Google Chrome</span>
                                            <i class="far fa-window-close ms-auto"></i>
                                        </div>
                                        <div class="preview_content d-flex align-items-center mt-2">
                                            <div class="flex-shrink-0">
                                                <img src="{{ asset('images/push/icons/alarm-1.png') }}" id="icon_prv" class="img-fluid" alt="Icon Preview">
                                            </div>
                                            <div class="flex-grow-1 ms-2">
                                                <span class="fs-16 text-white prv_title" id="prv_title">Title Preview</span>
                                                <p class="card-text prv_desc" id="prv_desc">Preview of Description</p>
                                            </div>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-6 btn_prv" style="display:none;" id="btn_prv">
                                                <span id="btn_title1" class="btn_title1 btn btn-dark w-100 btn-sm">Click Here</span>
                                            </div>
                                            <div class="col-6 btn2_prv" style="display:none;" id="btn2_prv">
                                                <span id="btn_title2" class="btn_title2 btn btn-dark w-100 btn-sm">Click Here</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="android_view" style="display:none;">
                                    <div class="android_body mt-3">
                                        <div class="d-flex align-items-center">
                                            <img src="{{ asset('images/chrome.png') }}" class="me-2" alt="Chrome" width="20">
                                            <span>Google Chrome</span>
                        <span class="ms-auto"><i class="far fa-chevron-circle-down fa-lg"></i></span>
                                        </div>
                                        <div class="preview_content d-flex justify-content-between align-items-center mt-2">
                                            <div class="flex-grow-1">
                                                <span class="fs-16 text-black prv_title">Title Preview</span>
                                                <p class="card-text fs-14 prv_desc">Preview of Description</p>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <img src="{{ asset('images/push/icons/alarm-1.png') }}" id="icon_prv_android" class="img-fluid" alt="Icon Preview">
                                            </div>
                                        </div>
                                        <img src="" class="feat_img message_image img-fluid mt-3" alt="" style="display:none;">
                                        <div class="d-flex align-items-center">
                                            <div class="mt-3 me-3 btn_prv" style="display:none;">
                                                <span class="btn_title1 text-primary fs-16">Click Here</span>
                                            </div>
                                            <div class="mt-3 me-3 btn2_prv" style="display:none;">
                                                <span class="btn_title2 text-primary fs-16">Click Here</span>
                                            </div>
                                        </div>
                                    </div>
                                </div> <!-- /android -->
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- /.row -->

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

<!-- Modal: Select Banner Img -->
<div class="modal fade" id="bannerImg" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
     aria-labelledby="bannerImgLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-20" id="bannerImgLabel">Select Banner Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body d-flex flex-wrap" id="banner-container">
                <div id="banner-loader" class="spinner-border m-auto" role="status">
                    <span class="visually-hidden">Loading…</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-danger" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

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
  

    {{-- Dummy data (domains, icons, banners) --}}
    <script>
        const DUMMY_DOMAINS = [
            'example.com','shop.example.com','newsportal.io','myapp.io','blogspace.dev',
            'acme.co','contoso.net','gaminghub.gg','travelmate.app','foodly.io'
        ];
        const ICON_URLS = [
            '{{ asset('images/push/icons/alarm-1.png') }}',
            '{{ asset('images/push/icons/alarm-2.png') }}',
            '{{ asset('images/push/icons/alarm-3.png') }}',
            'https://api.iconify.design/mdi-light:bell.svg?color=%230d6efd',
            'https://api.iconify.design/mdi-light:cart.svg?color=%230d6efd',
            'https://api.iconify.design/mdi-light:rocket.svg?color=%230d6efd',
            'https://api.iconify.design/mdi-light:tag.svg?color=%230d6efd'
        ];
        const BANNER_URLS = [
            'https://images.unsplash.com/photo-1542834369-f10ebf06d3cb?q=80&w=1600&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1491553895911-0055eca6402d?q=80&w=1600&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?q=80&w=1600&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1515168833906-d2a3b82b302a?q=80&w=1600&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1492724441997-5dc865305da7?q=80&w=1600&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1580910051074-cf3f2370d6f9?q=80&w=1600&auto=format&fit=crop'
        ];
    </script>

    <script>
        // --- Banner mode (URL vs Upload) ---
        function setBannerMode(mode) {
            const $urlGroup = $('#banner_url_group');
            const $uploadGroup = $('#banner_upload_group');
            const $urlInput = $('#image_input');
            const $fileInput = $('#banner_image_file');

            if (mode === 'url') {
                $urlGroup.show(); $uploadGroup.hide();
                $urlInput.prop('disabled', false);
                $fileInput.prop('disabled', true).val('');
                const v = ($urlInput.val() || '').trim();
                if (v) changeBanner(v); else resetImage();
            } else {
                $urlGroup.hide(); $uploadGroup.show();
                $urlInput.prop('disabled', true);
                $fileInput.prop('disabled', false);
                if (!$fileInput[0].files.length) resetImage();
            }
        }

        // --- Preview + helpers ---
        function prv_icons(url) {
            var def = "{{ asset('images/push/icons/alarm-1.png') }}";
            $('#icon_prv, #icon_prv_android, #banner_icon').attr('src', url.trim() ? url : def);
        }
        function titleText(input) { $('.prv_title').text(input.value || 'Title Preview'); }
        function descriptionText(text) { $('.prv_desc').text(text || 'Preview of Description'); }
        function changeBanner(src) {
            var def = "{{ asset('images/default.png') }}";
            const v = (src || '').trim();
            $('#banner_image').attr('src', v || def);
            $('.message_image').attr('src', v || '').toggle(!!v);
        }
        function setBannerUrl(url){
            $('#image_input').val(url); changeBanner(url);
            bootstrap.Modal.getInstance(document.getElementById('bannerImg')).hide();
        }
        function setImageUrl(url){
            $('#target').val(url); prv_icons(url);
            bootstrap.Modal.getInstance(document.getElementById('staticBackdrop')).hide();
        }
        function resetIcon() {
            var def = "{{ asset('images/push/icons/alarm-1.png') }}";
            $('#banner_icon,#icon_prv,#icon_prv_android').attr('src', def);
            $('#target').val('');
        }
        function resetImage() {
            var def = "{{ asset('images/default.png') }}";
            $('#banner_image').attr('src', def);
            $('.message_image').attr('src', '').hide();
            $('#image_input').val('');
            $('#banner_image_file').val('');
        }

        // CTA preview bindings (used by inline handlers)
        function btn_title_prv1(v){ $('.btn_title1').text(v || 'Click Here'); $('#btn_prv').toggle(!!v && $('#ctaCheckbox').is(':checked')); }
        function btn_title_prv2(v){ $('.btn_title2').text(v || 'Click Here'); $('#btn2_prv').toggle(!!v && $('#ctaCheckbox').is(':checked') && $('#second_btn').is(':visible')); }
        function toggleSecondBtn(){ $('#second_btn').slideToggle(200, () => btn_title_prv2($('input[name="btn_title_2"]').val() || '')); }
        function removeSecondBtn(){ $('#second_btn').slideUp(200, () => btn_title_prv2('')); $('input[name="btn_title_2"], input[name="btn_url_2"]').val(''); }

        // Dummy “fetch meta”
        function fakeFetchMeta(url){
            try {
                const u = new URL(url);
                const host = u.hostname.replace('www.','');
                const path = u.pathname.replace(/\//g,' ').trim() || 'home';
                return {
                    title: `🔥 ${host.toUpperCase()} — ${path}`,
                    description: `Fresh content from ${host}. Click to explore ${path}.`,
                    image: BANNER_URLS[Math.floor(Math.random()*BANNER_URLS.length)]
                };
            } catch(e) { return null; }
        }

        $(function(){
            // tooltips
            [...document.querySelectorAll('[data-bs-toggle="tooltip"]')].forEach(el => new bootstrap.Tooltip(el));

            // default to URL mode
            setBannerMode('url');
            $('#banner_src_url, #banner_src_upload').on('change', function(){ setBannerMode(this.value); });

            // file upload preview
            $('#banner_image_file').on('change', function(){
                const f = this.files && this.files[0];
                if(!f){ resetImage(); return; }
                const r = new FileReader();
                r.onload = e => { $('#banner_image,.message_image').attr('src', e.target.result); $('.message_image').show(); };
                r.readAsDataURL(f);
            });

            // preview switch
            $('input[name="preview_type"]').on('change', function(){
                const win = $(this).val()==='preview_windows';
                $('.windows_view').toggle(win);
                $('.android_view').toggle(!win);
            }).trigger('change');

            // domains (dummy)
            const $list = $('#domain-list');
            function renderDomains(items){
                $list.empty();
                items.forEach((d,i)=>{
                    $list.append(`
                        <div class="col-lg-6 col-md-6 col-12 domain-item">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="domain_name[]" id="domain-${i}" value="${d}">
                                <label class="form-check-label" for="domain-${i}">${d}</label>
                            </div>
                        </div>
                    `);
                });
            }
            renderDomains(DUMMY_DOMAINS);

            // domain filter
            $('#domainFilter').on('input', function(){
                const term = this.value.trim().toLowerCase();
                $('#domain-list .domain-item').each(function(){
                    const label = $(this).find('label').text().toLowerCase();
                    $(this).toggle(label.includes(term));
                });
                $('#select_all_domain').prop('checked', false);
            });

            // select all (visible)
            $('#select_all_domain').on('click', function(){
                const on = this.checked;
                $("input[name='domain_name[]']").prop('checked', false);
                if (on){
                    $('#domain-list .domain-item:visible').find("input[name='domain_name[]']").prop('checked', true);
                }
            });

            // icons modal (dummy)
            (function(){
                let injected = false;
                $('#staticBackdrop').on('show.bs.modal', function(){
                    if(injected) return;
                    const $ct = $('#icon-container').empty();
                    ICON_URLS.forEach(url=>{
                        $('<div class="m-1">')
                            .append($('<img>').attr({src:url,width:52,height:52,class:'img-thumbnail p-2',alt:'icon'})
                            .css('cursor','pointer').on('click', ()=> setImageUrl(url)))
                            .appendTo($ct);
                    });
                    injected = true;
                });
            })();

            // banners modal (dummy)
            (function(){
                let injected = false;
                $('#bannerImg').on('show.bs.modal', function(){
                    if(injected) return;
                    const $ct = $('#banner-container').empty();
                    BANNER_URLS.forEach(url=>{
                        $('<div class="m-1">')
                            .append($('<img>').attr({src:url,class:'img-thumbnail banner-model-img p-2',alt:'banner'})
                            .css('cursor','pointer').on('click', ()=> setBannerUrl(url)))
                            .appendTo($ct);
                    });
                    injected = true;
                });
            })();

            // GET DATA (dummy meta)
            $('#getData').on('click', function(){
                var url = $.trim($('#target_url').val());
                if(!url){
                    iziToast.error({ title:'Warning!', message:'Please enter a URL first.', position:'topRight' });
                    return;
                }
                const $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Fetching...');
                setTimeout(()=>{
                    const meta = fakeFetchMeta(url);
                    if(meta){
                        $('#title').val(meta.title).trigger('keyup');
                        $('#description').val(meta.description).trigger('keyup');
                        $('#image_input').val(meta.image); changeBanner(meta.image);
                        $('#urlInput1,#urlInput2').val(url);
                        iziToast.success({ title:'Success', message:'Metadata loaded.', position:'topRight' });
                    } else {
                        iziToast.error({ title:'Error', message:'Invalid URL.', position:'topRight' });
                    }
                    $btn.prop('disabled', false).html('<i class="far fa-wand"></i> Get Data');
                }, 500);
            });

            // icon preview from input
            $('#target').on('input', function(){ prv_icons(this.value); });

            // Validation (no scheduling fields now)
            $("#notificationform").validate({
                errorElement: 'div',
                errorClass: 'invalid-feedback',
                ignore: ':hidden',
                rules: {
                    target_url: { required: true, url: true },
                    title: { required: true, maxlength: 100 },
                    description: { required: true, maxlength: 200 },
                    'domain_name[]': { required: true },
                    btn_1_title: {
                        required: function(){ return $('#ctaCheckbox').is(':checked'); }
                    },
                    btn_1_url: {
                        required: function(){ return $('#ctaCheckbox').is(':checked'); },
                        url: true
                    },
                    btn_title_2: {
                        required: function(){ return $('#ctaCheckbox').is(':checked') && $('#second_btn').is(':visible'); }
                    },
                    btn_url_2: {
                        required: function(){ return $('#ctaCheckbox').is(':checked') && $('#second_btn').is(':visible'); },
                        url: true
                    }
                },
                messages: {
                    target_url: 'Please enter a valid URL.',
                    title: { required: 'Title is required.', maxlength: 'Title cannot exceed 100 characters.' },
                    description: { required: 'Notification Message is required.', maxlength: 'Description cannot exceed 200 characters.' },
                    'domain_name[]': 'Please select at least one domain.',
                    btn_1_title: "Please enter a title for Button 1.",
                    btn_1_url: "Please enter a valid URL for Button 1.",
                    btn_title_2: "Please enter a title for Button 2.",
                    btn_url_2: "Please enter a valid URL for Button 2."
                },
                errorPlacement: function(error, element) {
                    const n = element.attr('name');
                    if (['btn_1_title','btn_1_url','btn_title_2','btn_url_2'].includes(n)) {
                        error.insertAfter(element);
                    } else if (n === 'domain_name[]') {
                        error.appendTo('#domain-error-span');
                    } else {
                        error.insertAfter(element);
                    }
                },
                highlight: function(el){ $(el).addClass('is-invalid'); },
                unhighlight: function(el){ $(el).removeClass('is-invalid'); },
                submitHandler: function(form){
                    // Dummy submit
                    $('#sendNotification').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Sending…');
                    setTimeout(()=>{
                        iziToast.success({
                            title: 'Sent!',
                            message: 'This was a dummy submission. No request was sent.',
                            position: 'topRight'
                        });
                        $('#sendNotification').prop('disabled', false).html('<i class="far fa-check-square pe-2"></i>Send Now');
                    }, 800);
                    return false;
                }
            });
        });
    </script>

@endpush
