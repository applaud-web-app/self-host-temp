<?php /* Segmentation – create page with Add More button above geo rows */ ?>
@extends('layouts.master')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
    <style>
      /* simple left-right jog */
      @keyframes run {
          0%   { transform: translateX(0);   }
          50%  { transform: translateX(8px); }
          100% { transform: translateX(0);   }
      }
      .run-anim   { animation: run .7s linear infinite; display:inline-block }
      .run-delay  { animation-delay: .35s; }     /* second runner starts half-beat later */
    </style>
@endpush

@section('content')
<section class="content-body" id="create_segmentation_page">
    <div class="container-fluid">
        <div class="d-flex flex-wrap align-items-center justify-content-between text-head mb-3">
            <h2 class="mb-0">Add New Segment</h2>
            <div>
                @php
                    $hasAdvanceSegment = App\Models\Addon::where('preferred_name', 'AdvanceSegmentation')->where('status', 'installed')->exists();
                @endphp

                @if ($hasAdvanceSegment)
                    <a href="{{ route('advance-segmentation.index') }}" class="btn btn-primary ms-auto">
                        <i class="fas fa-bullseye-arrow"></i> Advance Segment
                    </a>
                @endif
                <a href="{{ route('segmentation.view') }}" class="btn btn-secondary ms-auto">
                    <i class="fas fa-arrow-left me-1"></i> Back to List
                </a>
            </div>
        </div>
        <div class="row">
            <!-- FORM COLUMN -->
    <div class="col-lg-8 order-2 order-lg-1">
                <form id="segmentationForm" method="POST" action="{{ route('segmentation.store') }}">
                    @csrf
                    <input type="hidden" name="segment_type" id="segment_type">

                    <!-- SEGMENT BASICS -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Segment Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="segment_name"
                                    placeholder="Enter segment name" required>
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
                                <div class="list-group-item d-flex justify-content-between align-items-center p-3">
                                    <div>
                                        <i class="fal fa-desktop pe-1"></i> <strong>Device-based Targeting</strong>
                                        <p class="mb-0 small">Target subscribers coming in from Mobile, Desktop or
                                            Tablet.</p>
                                    </div>
                                    <button type="button" class="btn btn-outline-secondary show-card"
                                        data-target="device-card">+</button>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center p-3">
                                    <div>
                                        <i class="fal fa-map-marker-alt pe-1"></i> <strong>Geo-Based Targeting</strong>
                                        <p class="mb-0 small">Location from which the user subscribed.</p>
                                    </div>
                                    <button type="button" class="btn btn-outline-secondary show-card"
                                        data-target="geo-card">+</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DEVICE CARD -->
                    <div class="card mb-3 card-targeting" id="device-card" style="display:none;">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Device-based Targeting</h5>
                            <button type="button" class="btn btn-link text-danger delete-card"><i
                                    class="fas fa-trash-alt"></i></button>
                        </div>
                        <div class="card-body">
                            <select class="form-select" id="devicetype" name="devicetype[]" multiple>
                                <option>Desktop</option>
                                <option>Tablet</option>
                                <option>Mobile</option>
                                <option>Other</option>
                            </select>
                        </div>
                    </div>

                    <!-- GEO CARD -->
                    <div class="card mb-3 card-targeting" id="geo-card" style="display:none;">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Geo-based Targeting</h5>
                            <button type="button" class="btn btn-link text-danger delete-card"><i
                                    class="fas fa-trash-alt"></i></button>
                        </div>
                        <div class="card-body">

                            <div id="geo-container">



                                <!-- Hidden template -->
                                <template id="geo-template">
                                    <div class="row g-3 geo-row">
                                        <div class="col-lg-3 mb-3">
                                            <select class="form-select" name="geo_type[]">
                                                <option value="equals">Only</option>
                                                <option value="not_equals">Without</option>
                                            </select>
                                        </div>
                                        <div class="col-lg-4 mb-3">
                                            <select class="form-select" name="country[]">
                                                <option value="">Country…</option>
                                                @foreach ($countriesStates as $country => $states)
                                                    <option value="{{ $country }}">{{ $country }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-lg-4 mb-3">
                                            <select class="form-select" name="state[]">
                                                <option value="">State (optional)…</option>
                                            </select>
                                        </div>
                                        <div class="col-lg-1 mb-3">
                                            <button type="button"
                                                class="btn btn-danger remove-geo-row">&times;</button>
                                        </div>
                                    </div>
                                </template>

                                <!-- Initial geo-row -->
                                <div class="row g-3 geo-row">
                                    <div class="col-lg-3 mb-3">
                                        <select class="form-select" name="geo_type[]">
                                            <option value="equals">Only</option>
                                            <option value="not_equals">Without</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-4 mb-3">
                                        <select class="form-select" name="country[]">
                                            <option value="">Country…</option>
                                            @foreach ($countriesStates as $country => $states)
                                                <option value="{{ $country }}">{{ $country }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-4 mb-3">
                                        <select class="form-select" name="state[]">
                                            <option value="">State (optional)…</option>
                                            <option>Maharashtra</option>
                                            <option>California</option>
                                            <option>Ontario</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-1 mb-3">
                                        <button type="button" class="btn btn-danger remove-geo-row">&times;</button>
                                    </div>
                                </div>

                            </div>
                            <!-- Add More button ABOVE rows -->
                            <button type="button" class="btn btn-secondary btn-sm " id="add-geo-row">
                                + Add More
                            </button>


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
                        <button id="checkInfo" class="btn btn-outline-primary btn-sm w-100"><i
                                class="fas fa-redo-alt"></i> Refresh Data</button>
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
    $(function() {

        /* ------------------------------------------------------------
         * 0.  Cached map country → [states]
         * ------------------------------------------------------------ */
        const countryStates = @json($countriesStates);

        /* ------------------------------------------------------------
         * 1.  Generic Select2 bootstrap
         * ------------------------------------------------------------ */
        function initSelect2($ctx) {
            $ctx.find('select').select2({
                placeholder: 'Choose…',
                allowClear: true,
                width: '100%'
            });
        }
        initSelect2($(document));

        /* ------------------------------------------------------------
         * 2.  Add helper classes (if missing) + dynamic state builder
         * ------------------------------------------------------------ */
        $('#geo-container')
            .find('select[name="country[]"]').addClass('country-select').end()
            .find('select[name="state[]"]').addClass('state-select');

        function refreshStates($row) {
            const country = $row.find('.country-select').val();
            const $stateSel = $row.find('.state-select');

            let html = '<option value="">State (optional)…</option>';
            if (country && Array.isArray(countryStates[country])) {
                countryStates[country].forEach(st => {
                    html += `<option value="${st}">${st}</option>`;
                });
                $stateSel.prop('disabled', false);
            } else {
                $stateSel.prop('disabled', true);
            }
            $stateSel.html(html).val(null).trigger('change');
        }
        refreshStates($('.geo-row').first());
        $(document).on('change', '.country-select', function() {
            refreshStates($(this).closest('.geo-row'));
        });

        /* ------------------------------------------------------------
         * 3.  “+ Add More” (max 5)
         * ------------------------------------------------------------ */
        $('#add-geo-row').on('click', function() {
            if ($('#geo-container .geo-row').length >= 5) {
                return alert('Maximum 5 conditions allowed.');
            }
            const tpl = $('#geo-template')[0].content.cloneNode(true);
            $('#geo-container').append(tpl);

            const $newRow = $('#geo-container .geo-row').last()
                .find('select[name="country[]"]').addClass('country-select').end()
                .find('select[name="state[]"]').addClass('state-select').end();

            initSelect2($newRow);
            refreshStates($newRow);
        });

        /* remove row (leave at least one) */
        $(document).on('click', '.remove-geo-row', function() {
            const $rows = $('#geo-container .geo-row');
            if ($rows.length > 1) {
                $(this).closest('.geo-row').remove();
            } else {
                $rows.find('select').val(null).trigger('change');
            }
        });

        /* ------------------------------------------------------------
         * 4.  Card toggling (sets #segment_type)
         * ------------------------------------------------------------ */
        $('.show-card').on('click', function() {
            const target = $(this).data('target'); // device-card | geo-card
            $('#condition-screen, .card-targeting').hide();
            $('#' + target).show();
            $('#segment_type').val(target.replace('-card', ''));
        });
        $('.delete-card').on('click', function() {
            $('#condition-screen').show();
            $(this).closest('.card-targeting').hide();
            $('#segment_type').val('');
        });

        /* ------------------------------------------------------------
         * 5.  Live Select2 domain list
         * ------------------------------------------------------------ */
        $('#domain-select').select2({
            placeholder: 'Search for Domain…',
            allowClear: true,
            minimumInputLength: 0,  // show list even when empty
            width: '100%',
            ajax: {
                url: "{{ route('domain.domain-list') }}",
                dataType: 'json',
                delay: 250,
                cache: true,
                data: params => ({ q: params.term || '' }),
                processResults: resp => ({
                    results: (resp.status ? resp.data : []).map(d => ({
                        id:   d.text,         // matches your controller
                        text: d.text
                    }))
                })
            },
            templateResult: domain => {
                if (domain.loading) return domain.text;
                return $(
                `<span><i class="fal fa-globe me-2"></i>${domain.text}</span>`
                );
            },
            escapeMarkup: m => m
        });

        /* ------------------------------------------------------------
         * 6.  “Total Audience” demo refresh
         * ------------------------------------------------------------ */
        $('#checkInfo').on('click', function () {

          const $btn   = $(this);
          const $form  = $('#segmentationForm');
          const $count = $('#count');

          /* 1) client-side validation --------------------------------------- */
          if (!$form.valid()) { return; }
          if ($('#segment_type').val() === 'geo' && !geoIsConsistent()) { return; }

          /* 2) visual feedback ---------------------------------------------- */
          const prevCount = $count.text();          // remember number in case of error
          $count.html(
              '<i class="fas fa-running run-anim"></i>'
          );

          $btn.prop('disabled', true).html(
              '<span class="spinner-border spinner-border-sm me-2"></span> Loading…'
          );

          /* 3) AJAX ---------------------------------------------------------- */
          $.ajax({
              url      : "{{ route('segmentation.refresh-data') }}",
              type     : 'POST',
              data     : $form.serialize(),
              dataType : 'json',
              success  : function (res) {
                  if (res && res.count !== undefined) {
                      $count.text(res.count);
                  } else {
                      $count.text(prevCount);
                      iziToast.warning({ message:'Unexpected response.', position:'topRight' });
                  }
              },
              error    : function () {
                  $count.text(prevCount);
                  iziToast.error({ message:'Unable to fetch data.', position:'topRight' });
              },
              complete : function () {
                  $btn.prop('disabled', false)
                      .html('<i class="fas fa-redo-alt"></i> Refresh Data');
              }
          });
        });

        /* ------------------------------------------------------------
         * 7.  Additional Geo consistency check
         * ------------------------------------------------------------ */
        function geoIsConsistent() {
            const seen = {}; // key = country|state
            const ops = {}; // country => { equals, not_equals }
            let ok = true;

            $('#geo-container .geo-row').each(function() {
                const c = $(this).find('select[name="country[]"]').val();
                const s = $(this).find('select[name="state[]"]').val() || '';
                const op = $(this).find('select[name="geo_type[]"]').val();

                if (!c) {
                    ok = false;
                    return false;
                }

                const key = c + '|' + s;
                if (seen[key]) {
                    ok = false;
                    return false;
                }
                seen[key] = true;

                ops[c] = ops[c] || {
                    equals: false,
                    not_equals: false
                };
                ops[c][op] = true;
                if (ops[c].equals && ops[c].not_equals) {
                    ok = false;
                    return false;
                }
            });
            return ok;
        }

        /* ------------------------------------------------------------
         * 8.  jQuery-Validate
         * ------------------------------------------------------------ */
        $('#segmentationForm').validate({
            ignore: [],
            errorClass: 'error',
            validClass: 'is-valid',
            errorPlacement(error, element) {
                if (element.hasClass('select2-hidden-accessible')) {
                    error.insertAfter(element.next('.select2'));
                } else {
                    error.insertAfter(element);
                }
            },
            rules: {
                segment_name: {
                    required: true,
                    maxlength: 255
                },
                domain_name: {
                    required: true
                },
                segment_type: {
                    required: true
                }, // ← NEW

                'devicetype[]': {
                    required: () => $('#segment_type').val() === 'device'
                },
                'geo_type[]': {
                    required: () => $('#segment_type').val() === 'geo'
                },
                'country[]': {
                    required: () => $('#segment_type').val() === 'geo'
                }
            },
            messages: {
                segment_type: 'Please choose Device-based or Geo-based targeting first.'
            },
            submitHandler(form) {
                /* extra Geo consistency check */
                if ($('#segment_type').val() === 'geo' && !geoIsConsistent()) {
                    iziToast.error({
                        title: 'Error',
                        message: 'Geo rules contain duplicates or conflicting "Only" / "Without" pairs.',
                        position: 'topRight'
                    });
                    return false;
                }
                const $btn = $(form).find('button[type="submit"]');
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Processing…');
                form.submit();
            }
        });
    });
</script>
@endpush