@extends('layouts.master')

@push('styles')
    <!-- Bootstrap Date Range Picker CSS -->
    <link rel="stylesheet" href="{{ asset('/vendor/bootstrap-daterangepicker/daterangepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/sweetalert2/dist/sweetalert2.min.css') }}">
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
        .is-invalid + .invalid-feedback {
            display: block;
        }
        .filter-group .form-control {
            max-width: 200px;
        }
        .table-responsive {
            margin-top: 1rem;
        }
    </style>
@endpush

@section('content')
<section class="content-body">
    <div class="container-fluid">
        <!-- Header Section -->
        <div class="d-flex flex-wrap align-items-center text-head mb-3">
            <h2 class="mb-3 me-auto">Campaign Reports</h2>
            <div class="mb-3">
                <a href="{{ route('notification.create') }}" class="btn btn-primary">
                    <i class="far fa-plus-circle me-2"></i> Add New
                </a>
            </div>
        </div>

        <!-- Static Filter Form -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card h-auto">
                    <div class="card-body">
                        <form id="filterForm">
                            <div class="row g-2 align-items-end">
                                <!-- Campaign Name Filter (takes 3 columns on xl, 6 on md) -->
                                <div class="col-xl-3 col-md-6">
                                    <div class="position-relative">
                                        <input
                                            type="text"
                                            class="form-control"
                                            name="campaign_name"
                                            id="filter_campaign_name"
                                            placeholder="Search Campaign Name..."
                                        >
                                        <div class="invalid-feedback"></div>
                                        <i
                                            class="far fa-search text-primary position-absolute top-50 translate-middle-y"
                                            style="right: 10px;"
                                        ></i>
                                    </div>
                                </div>

                                <!-- Status Filter (takes 2 columns on xl, 6 on md) -->
                                <div class="col-xl-2 col-md-6">
                                    <select
                                        class="form-control form-select"
                                        id="filter_status"
                                        name="status"
                                    >
                                        <option value="">Select Status</option>
                                        <option value="sent">Sent</option>
                                        <option value="ongoing">Ongoing</option>
                                        <option value="pending">Pending</option>
                                        <option value="cancelled">Cancel</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <!-- Domain Filter (takes 3 columns on xl, 6 on md) -->
                                <div class="col-xl-3 col-md-6">
                                 <select class="form-select form-control filter_site_web" name="site_web">
                                    <option value="">Search for Domain…</option>
                                    @foreach($domains as $d)
                                        <option value="{{ $d }}">{{ $d }}</option>
                                    @endforeach
                                    </select>

                                    <div class="invalid-feedback"></div>
                                </div>

                                <!-- Last Send Date Range Filter (takes 2 columns on xl, 6 on md) -->
                                <div class="col-xl-2 col-md-6">
                                    <input
                                        type="text"
                                        class="form-control hasDatepicker"
                                        id="daterange"
                                        name="last_send"
                                        readonly
                                        placeholder="Select date range"
                                    >
                                    <div class="invalid-feedback"></div>
                                </div>

                                <!-- Reset Button (takes 2 columns on xl, 6 on md) -->
                                <div class="col-xl-2 col-md-6 text-end">
                                    <a
                                        href=""
                                        class="btn btn-danger light w-100"
                                        title="Click here to remove filter"
                                    >
                                        <i class="fas fa-undo me-1"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Campaign Type Radio Buttons -->
            <div class="col-lg-12 mb-3">
                <div class="custom-radio justify-content-start">
                    <label class="mb-2 mb-lg-3 w-auto d-inline-block" for="campaign_type_all">
                        <input type="radio" name="campaign_type" id="campaign_type_all" value="all" checked>
                        <span>All</span>
                    </label>
                    <label class="mb-2 mb-lg-3 w-auto d-inline-block" for="campaign_type_instant">
                        <input type="radio" name="campaign_type" id="campaign_type_instant" value="Instant">
                        <span>Instant</span>
                    </label>
                    <label class="mb-2 mb-lg-3 w-auto d-inline-block" for="campaign_type_schedule">
                        <input type="radio" name="campaign_type" id="campaign_type_schedule" value="schedule">
                        <span>Schedule</span>
                    </label>
                    <label class="mb-2 mb-lg-3 w-auto d-inline-block" for="campaign_type_recurring">
                        <input type="radio" name="campaign_type" id="campaign_type_recurring" value="recurring">
                        <span>Recurring</span>
                    </label>
                    <label class="mb-2 mb-lg-3 w-auto d-inline-block" for="campaign_type_rss">
                        <input type="radio" name="campaign_type" id="campaign_type_rss" value="rss">
                        <span>RSS</span>
                    </label>
                    <label class="mb-2 mb-lg-3 w-auto d-inline-block" for="campaign_type_api">
                        <input type="radio" name="campaign_type" id="campaign_type_api" value="API">
                        <span>API</span>
                    </label>
                </div>
            </div>

            <!-- Static DataTable -->
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body p-3" id="tableData">
                        <div class="table-responsive">
                            <table class="table display" id="datatable">
                            
        <thead >
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
        <tbody>
            @php
                use Carbon\Carbon;

                $statuses = [
                    ['label' => 'Sent',      'class' => 'badge-success'],
                    ['label' => 'Ongoing',   'class' => 'badge-primary'],
                    ['label' => 'Scheduled', 'class' => 'badge-warning'],
                    ['label' => 'Cancelled', 'class' => 'badge-danger'],
                    ['label' => 'Draft',     'class' => 'badge-secondary'],
                ];
            @endphp

            @for ($i = 1; $i <= 20; $i++)
                @php
                    $status       = $statuses[($i - 1) % count($statuses)];
                    $statusLabel  = $status['label'];
                    $statusClass  = $status['class'];

                    // For demonstration, use June $i, 2025 at 10:00 AM
                    $sentTime     = Carbon::create(2025, 6, $i, 10, 0)->format('Y-m-d h:i A');

                    $clicks       = ($statusLabel === 'Sent' || $statusLabel === 'Ongoing') 
                                    ? $i * 100 
                                    : null;

                    $reachCount   = $i * 1000;
                    $sentCount    = $i * 900;
                    $clickedCount = $i * ($i * 5);
                @endphp

                <tr>
                    <td>{{ $i }}</td>

                    <td>
                        <a href="javascript:void(0)"
                           class="open-modal text-decoration-none"
                           data-title="Campaign {{ $i }}"
                           data-description="This is the description for Campaign {{ $i }}."
                           data-image="{{ asset('images/default.png') }}"
                           data-link="https://example.com/campaign-{{ $i }}"
                           data-reach="{{ $reachCount }}"
                           data-sent="{{ $sentCount }}"
                           data-clicked="{{ $clickedCount }}"
                           data-bs-toggle="modal"
                           data-bs-target="#campaign">
                            Campaign {{ $i }}
                        </a>
                    </td>

                    <td>domain{{ $i }}.com</td>

                    <td>
                        <span class="badge light {{ $statusClass }}">
                            {{ $statusLabel }}
                        </span>
                    </td>

                    <td>{{ $sentTime }}</td>

                    <td>{{ $clicks !== null ? $clicks : '—' }}</td>

                    <td>
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary open-modal"
                            data-title="Campaign {{ $i }}"
                            data-description="This is the description for Campaign {{ $i }}."
                            data-image="{{ asset('images/default.png') }}"
                            data-link="https://example.com/campaign-{{ $i }}"
                            data-reach="{{ $reachCount }}"
                            data-sent="{{ $sentCount }}"
                            data-clicked="{{ $clickedCount }}"
                            data-bs-toggle="modal"
                            data-bs-target="#campaign">
                            <i class="fas fa-eye me-1"></i> View
                        </button>

                        <button type="button" class="btn btn-sm btn-outline-primary">
                             <i class="fas fa-clone me-1"></i> Clone
                        </button>
                    </td>
                </tr>
            @endfor
        </tbody>
    </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Campaign Modal (Static Preview) -->
    <div class="modal fade" id="campaign" tabindex="-1" aria-labelledby="campaignModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-bullhorn"></i> <span id="campaign_name"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- Analytics Section -->
                        <div class="col-lg-6">
                            <h4 class="mb-0"><i class="fas fa-chart-bar"></i> Analytics</h4>
                            <hr>
                            <div id="chart" class="border p-3">
                                <!-- Static placeholder chart area -->
                                <p class="text-center text-muted">[Chart will render here]</p>
                            </div>
                        </div>
                        <!-- Preview Section -->
                        <div class="col-lg-6">
                            <h4 class="mb-0"><i class="fas fa-bell"></i> Preview</h4>
                            <hr>
                            <div class="windows_view border p-3">
                                <img src="" id="message_image" class="feat_img img-fluid mb-3" alt="Campaign Image" style="height: 260px; width: auto;">
                                <div class="windows_body">
                                    <div class="d-flex align-items-center mb-3">
                                        <img src="{{ asset('images/chrome.png') }}" class="me-2" alt="Browser Icon">
                                        <span>Google Chrome</span>
                                        <i class="far fa-window-close ms-auto"></i>
                                    </div>
                                    <div class="preview_content d-flex align-items-start mb-3">
                                        <div class="flex-shrink-0 me-3">
                                            <img src="{{ asset('images/push/icons/alarm-1.png') }}" id="icon_prv" class="img-fluid" alt="Icon Preview" style="height: 50px; width: 50px;">
                                        </div>
                                        <div class="flex-grow-1">
                                            <span class="fw-bold fs-13" id="prv_title"></span>
                                            <p class="card-text mb-2" id="prv_desc"></p>
                                            <span class="fw-light text-primary" id="prv_link"></span>
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6" style="display:none;" id="btn_prv">
                                            <span id="btn_title1" class="btn btn-dark w-100 btn-sm">Click Here</span>
                                        </div>
                                        <div class="col-6" style="display:none;" id="btn2_prv">
                                            <span id="btn_title2" class="btn btn-dark w-100 btn-sm">Click Here</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!-- End of Row -->
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
    <script src="{{ asset('vendor/sweetalert2/dist/sweetalert2.min.js') }}"></script>
  

    {{-- <script>
        $(document).ready(function () {
            // Initialize DataTable on the static table
            $('#datatable').DataTable({
              pageLength: 10,
            lengthChange: true,
            info: true,
            searching: false,
                
            language: {
                paginate: {
                    previous: '<i class="fas fa-chevron-left"></i>',
                    next: '<i class="fas fa-chevron-right"></i>'
                },
                zeroRecords: "No users found",
                emptyTable: "No data available in table"
            },
            dom: '<"top"f>rt<"bottom"p><"clear">' 
            });

            // Initialize Select2 on static domain select
            $('.filter_site_web').select2({
                placeholder: "Select a Domain...",
                allowClear: true
            });

            // Initialize DateRangePicker (static, no filtering logic)
            $('#daterange').daterangepicker({
                showWeekNumbers: true,
                showISOWeekNumbers: true,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                },
                maxSpan: { days: 31 },
                alwaysShowCalendars: true,
                maxDate: new Date(),
                autoApply: true,
                locale: {
                    format: "MM/DD/YYYY",
                    applyLabel: "Apply",
                    cancelLabel: "Cancel",
                    customRangeLabel: "Custom Range",
                    daysOfWeek: ["Su","Mo","Tu","We","Th","Fr","Sa"],
                    monthNames: ["January","February","March","April","May","June","July","August","September","October","November","December"],
                    firstDay: 0
                }
            }, function(start, end) {
                $('#daterange').val(start.format('MM/DD/YYYY') + ' - ' + end.format('MM/DD/YYYY'));
            });

            // Clear date range on cancel
            $('#daterange').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });

            // Placeholder: Open modal and populate static content
            $(document).on('click', '.open-modal', function(){
                var title = $(this).data('title');
                var description = $(this).data('description');
                var image = $(this).data('image');
                var link = $(this).data('link');
                var reach = parseInt($(this).data('reach'), 10);
                var sent = parseInt($(this).data('sent'), 10);
                var clicked = parseInt($(this).data('clicked'), 10);

                // Populate modal fields
                $('#campaign_name').text(title);
                $('#prv_title').text(title);
                $('#prv_desc').text(description || '');
                $('#prv_link').text(link || '');
                $('#message_image').attr('src', image || '');

                // Show/hide buttons based on static data
                if(clicked > 0) {
                    $('#btn_prv').show();
                    $('#btn_title1').text('View Clicks');
                } else {
                    $('#btn_prv').hide();
                }
                if(sent > 0) {
                    $('#btn2_prv').show();
                    $('#btn_title2').text('View Sent Details');
                } else {
                    $('#btn2_prv').hide();
                }

                // Render static chart (example values)
                chartload(reach, sent, clicked);
            });

            // Static chart rendering function
            function chartload(reach, sent, clicked) {
                var options = {
                    series: [
                        { name: 'REACH', data: [reach] },
                        { name: 'SENT',  data: [sent] },
                        { name: 'CLICKED', data: [clicked] }
                    ],
                    chart: { type: 'bar', height: 350, toolbar: { show: false } },
                    plotOptions: {
                        bar: { horizontal: false, columnWidth: '40%', endingShape: 'rounded' }
                    },
                    dataLabels: { enabled: false },
                    xaxis: { categories: ['Notifications'] },
                    stroke: { show: true, width: 2, colors: ['transparent'] },
                    fill: { opacity: 1 },
                    tooltip: {
                        y: { formatter: function(val) { return val; } }
                    }
                };

                if(window.currentChart) {
                    window.currentChart.destroy();
                }
                window.currentChart = new ApexCharts(document.querySelector("#chart"), options);
                window.currentChart.render();
            }

            // Reset modal content when hidden
            $('#campaign').on('hidden.bs.modal', function () {
                if(window.currentChart) {
                    window.currentChart.destroy();
                }
                $('#campaign_name').text('');
                $('#prv_title').text('');
                $('#prv_desc').text('');
                $('#prv_link').text('');
                $('#message_image').attr('src', '');
                $('#btn_prv, #btn2_prv').hide();
            });

        });
    </script> --}}

<script>
$(function(){
  const table = $('#datatable').DataTable({
     searching: false,
    paging: true,
    select: false,
    language: {
        paginate: {
            previous: '<i class="fas fa-angle-double-left"></i>',
            next: '<i class="fas fa-angle-double-right"></i>'
        }
    },
    lengthChange: false,
    processing: true,
    serverSide: true,
    ajax: {
      url: '{{ route("notification.view") }}',
      data: d => {
        d.campaign_name = $('#filter_campaign_name').val();
        d.status        = $('#filter_status').val();
        d.site_web      = $('.filter_site_web').val();
        d.last_send     = $('#daterange').val();
        d.campaign_type = $('input[name="campaign_type"]:checked').val();
      }
    },
    columns: [
      { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable:false, searchable:false },
      { data: 'campaign_name', name: 'campaign_name' },
      { data: 'domain', name: 'domain', orderable:false, searchable:false },
      { data: 'status', name: 'status' },
      { data: 'sent_time', name: 'sent_time' },
      { data: 'clicks', name: 'clicks' },
      { data: 'action', name: 'action', orderable:false, searchable:false },
    ],
    order: [[1,'desc']],
    lengthMenu: [10,25,50]
  });

  // redraw on any filter change
  $('#filterForm')
    .on('change','input, select', () => table.draw())
    .on('keyup','input[name="campaign_name"]', () => table.draw());

  // date-range picker
  $('#daterange').daterangepicker({
    autoApply: true,
    maxDate: new Date(),
    locale: { format: 'MM/DD/YYYY' }
  }, function(start,end){
    $('#daterange').val(start.format('MM/DD/YYYY')+' - '+end.format('MM/DD/YYYY'));
    table.draw();
  }).on('cancel.daterangepicker', function(){
    $(this).val('');
    table.draw();
  });

  // campaign-type radios
  $('input[name="campaign_type"]').on('change', () => table.draw());
});
</script>

@endpush
