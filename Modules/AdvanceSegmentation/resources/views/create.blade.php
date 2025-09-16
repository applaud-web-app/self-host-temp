@extends('layouts.master')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
    <style>
        @keyframes run { 0%{transform:translateX(0)} 50%{transform:translateX(8px)} 100%{transform:translateX(0)} }
        .run-anim{animation:run .7s linear infinite;display:inline-block}
        .run-delay{animation-delay:.35s}
    </style>
@endpush

@section('content')
<section class="content-body" id="create_segmentation_page">
    <div class="container-fluid">
        <div class="d-flex flex-wrap align-items-center text-head mb-3">
            <h2 class="mb-0">Add Advance Segment</h2>
            <a href="{{ route('advance-segmentation.index') }}" class="btn btn-secondary ms-auto">
                <i class="fas fa-arrow-left me-1"></i>Back to List
            </a>
        </div>

        <div class="row">
            <!-- FORM COLUMN -->
            <div class="col-lg-8 order-2 order-lg-1">
                <form id="segmentationForm" method="POST" action="{{ route('advance-segmentation.store') }}">
                    @csrf
                    <input type="hidden" name="segment_type" id="segment_type">

                    <!-- SEGMENT BASICS -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Segment Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="segment_name" placeholder="Enter segment name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Select Domain <span class="text-danger">*</span></label>
                                <select class="form-select" id="domain-select" name="domain_name">
                                    <option value="">Choose domain…</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- CONDITION MENU -->
                    <div class="card mb-3" id="condition-screen">
                        <div class="card-body">
                            <h4 class="mb-3">Subscribers who match the following condition</h4>
                            <div class="list-group">
                                <!-- 1) TIME-BASED (first) -->
                                <div class="list-group-item d-flex justify-content-between align-items-center p-3">
                                    <div>
                                        <i class="fal fa-clock pe-1"></i>
                                        <strong>Time-based Targeting</strong>
                                        <p class="mb-0 small">Filter by start and end date & time.</p>
                                    </div>
                                    <button type="button" class="btn btn-outline-secondary show-card" data-target="time-card">+</button>
                                </div>
                                <!-- 2) URL-BASED (second) -->
                                <div class="list-group-item d-flex justify-content-between align-items-center p-3">
                                    <div>
                                        <i class="fal fa-link pe-1"></i>
                                        <strong>URL-based Targeting</strong>
                                        <p class="mb-0 small">Match one or more page URLs (max 10).</p>
                                    </div>
                                    <button type="button" class="btn btn-outline-secondary show-card" data-target="url-card">+</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TIME CARD -->
                    <div class="card mb-3 card-targeting" id="time-card" style="display:none;">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Time-based Targeting</h5>
                            <button type="button" class="btn btn-link text-danger delete-card"><i class="fas fa-trash-alt"></i></button>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Start Date & Time <span class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control" name="start_datetime" id="start_datetime">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">End Date & Time <span class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control" name="end_datetime" id="end_datetime">
                                </div>
                            </div>
                            <p class="small text-muted mt-2">
                                End must be after start, and cannot be in the future.
                            </p>
                        </div>
                    </div>

                    <!-- URL CARD -->
                    <div class="card mb-3 card-targeting" id="url-card" style="display:none;">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">URL-based Targeting</h5>
                            <button type="button" class="btn btn-link text-danger delete-card"><i class="fas fa-trash-alt"></i></button>
                        </div>
                        <div class="card-body">
                            <label class="form-label">URLs <span class="text-danger">*</span></label>
                            <select class="form-select" id="url-select" name="urls[]" multiple></select>
                            <p class="small text-muted mt-2">
                                Click to load URL options for the selected domain (loaded once). You can also type to add custom URLs. Max 10 selected.
                            </p>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Create Segment</button>
                </form>
            </div>

            <!-- SIDEBAR COLUMN -->
            <div class="col-lg-4 order-1 order-lg-2">
                <div class="card h-auto mb-3 text-center">
                    <div class="card-header bg-primary ">
                        <h4 class="mb-0 text-white">Total Audience</h4>
                    </div>
                    <div class="card-body">
                        <p class="display-5 fw-bold mb-3" id="count">0</p>
                        <button id="checkInfo" class="btn btn-outline-primary btn-sm w-100">
                            <i class="fas fa-redo-alt"></i> Refresh Data
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>
<script>
$(function () {
    // 1) Generic Select2 (except URL)
    function initSelect2($ctx) {
        $ctx.find('select').not('#url-select').select2({
            placeholder: 'Choose…', allowClear: true, width: '100%'
        });
    }
    initSelect2($(document));

    // 2) Domain select (AJAX)
    $('#domain-select').select2({
        placeholder: 'Search for Domain…',
        allowClear: true,
        minimumInputLength: 0,
        width: '100%',
        ajax: {
            url: "{{ route('domain.domain-list') }}",
            dataType: 'json', delay: 200, cache: true,
            data: params => ({ q: params.term || '' }),
            processResults: resp => ({
                results: (resp.status ? resp.data : []).map(d => ({ id: d.text, text: d.text }))
            })
        },
        templateResult: d => d.loading ? d.text : $(`<span><i class="fal fa-globe me-2"></i>${d.text}</span>`),
        escapeMarkup: m => m
    });

    // 3) URL select (prefetch on domain change, max=10, taggable)
    let cachedDomain = null;

    $('#url-select').select2({
        tags: true,
        tokenSeparators: [',', ' ', '\n'],
        width: '100%',
        placeholder: 'Add URLs…',
        createTag: function (params) {
            const term = (params.term || '').trim();
            if (!term) return null;
            return { id: term, text: term, newTag: true };
        }
    });

    $('#url-select').on('change', function () {
        const selected = $(this).val() || [];
        if (selected.length > 10) {
            selected.splice(10);
            $(this).val(selected).trigger('change.select2');
        }
    });

    $('#domain-select').on('change', function () {
        const domain = $(this).val();
        const $sel = $('#url-select');

        if (!domain) {
            cachedDomain = null;
            $sel.val(null).empty().trigger('change');
            return;
        }
        if (domain === cachedDomain) return;

        cachedDomain = domain;
        $sel.prop('disabled', true).val(null).empty().trigger('change');

        $.ajax({
            url: "{{ route('advance-segmentation.url-list') }}",
            type: 'POST',
            data: { domain: domain, _token: '{{ csrf_token() }}' },
            dataType: 'json',
            success: function (res) {
                if (res && res.status && Array.isArray(res.data)) {
                    const existing = new Set();
                    $sel.find('option').each(function () { existing.add($(this).val()); });
                    res.data.forEach(item => {
                        if (!existing.has(item.id)) {
                            const opt = new Option(item.text, item.id, false, false);
                            $sel.append(opt);
                        }
                    });
                }
            },
            complete: function () {
                $sel.prop('disabled', false);
            }
        });
    });

    // 4) Card toggling
    $('.show-card').on('click', function () {
        const target = $(this).data('target'); // time-card | url-card
        $('#condition-screen, .card-targeting').hide();
        $('#' + target).show();
        $('#segment_type').val(target.replace('-card', ''));
    });
    $('.delete-card').on('click', function () {
        $('#condition-screen').show();
        $(this).closest('.card-targeting').hide();
        $('#segment_type').val('');
        $('#start_datetime, #end_datetime').val('');
        $('#url-select').val(null).trigger('change');
    });

    // 5) Refresh audience
    $('#checkInfo').on('click', function () {
        const $btn = $(this), $form = $('#segmentationForm'), $count = $('#count');
        if (!$form.valid()) return;

        const prev = $count.text();
        $count.html('<i class="fas fa-running run-anim"></i>');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Loading…');

        $.ajax({
            url: "{{ route('advance-segmentation.refresh-data') }}",
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: res => $count.text((res && res.count !== undefined) ? res.count : prev),
            error: () => $count.text(prev),
            complete: () => $btn.prop('disabled', false).html('<i class="fas fa-redo-alt"></i> Refresh Data')
        });
    });

    // 6) Validation — start ≤ now, end > start, end ≤ now
    function parseLocal(s){ return s ? new Date(s.replace('T',' ') + ':00') : null; }

    $.validator.addMethod('startNotFuture', function(v){
        if(!v) return true;
        const start = parseLocal(v), now = new Date();
        return start.getTime() <= now.getTime();
    }, 'Start cannot be in the future.');

    $.validator.addMethod('endAfterStart', function(){
        const s = parseLocal($('#start_datetime').val());
        const e = parseLocal($('#end_datetime').val());
        if(!s || !e) return true;
        return e.getTime() > s.getTime();
    }, 'End must be after Start.');

    $.validator.addMethod('notFuture', function(v){
        if(!v) return true;
        const dt = parseLocal(v), now = new Date();
        return dt.getTime() <= now.getTime();
    }, 'Date/time cannot be in the future.');

    $('#segmentationForm').validate({
        ignore: [],
        errorClass: 'error',
        validClass: 'is-valid',
        errorPlacement(err, el){
            if (el.hasClass('select2-hidden-accessible')) err.insertAfter(el.next('.select2'));
            else err.insertAfter(el);
        },
        rules: {
            segment_name: { required: true, maxlength: 255 },
            domain_name:  { required: true },
            segment_type: { required: true },

            start_datetime: {
                required: () => $('#segment_type').val() === 'time',
                startNotFuture: true
            },
            end_datetime: {
                required: () => $('#segment_type').val() === 'time',
                endAfterStart: true,
                notFuture: true
            },
            'urls[]': {
                required: () => $('#segment_type').val() === 'url'
            }
        },
        messages: {
            segment_type: 'Please choose Time-based or URL-based targeting first.'
        },
        submitHandler(form){
            if ($('#segment_type').val() === 'url') {
                const sel = $('#url-select').val() || [];
                if (sel.length === 0 || sel.length > 10) return false;
            }
            const $btn = $(form).find('button[type="submit"]');
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Processing…');
            form.submit();
        }
    });
});
</script>
@endpush