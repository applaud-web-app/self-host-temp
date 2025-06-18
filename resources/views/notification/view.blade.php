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
                                            <input type="text" class="form-control" name="campaign_name"
                                                id="filter_campaign_name" placeholder="Search Campaign Name...">
                                            <div class="invalid-feedback"></div>
                                            <i class="far fa-search text-primary position-absolute top-50 translate-middle-y"
                                                style="right: 10px;"></i>
                                        </div>
                                    </div>

                                    <!-- Status Filter (takes 2 columns on xl, 6 on md) -->
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

                                    <!-- Domain Filter (takes 3 columns on xl, 6 on md) -->
                                    <div class="col-xl-3 col-md-6">
                                        <select class="form-select filter_site_web form-control" id="filter_domain"
                                            name="site_web"></select>
                                        <div class="invalid-feedback"></div>
                                    </div>

                                    <!-- Last Send Date Range Filter (takes 2 columns on xl, 6 on md) -->
                                    <div class="col-xl-2 col-md-6">
                                        <input type="text" class="form-control hasDatepicker" id="daterange"
                                            name="last_send" readonly placeholder="Select date range">
                                        <div class="invalid-feedback"></div>
                                    </div>

                                    <!-- Reset Button (takes 2 columns on xl, 6 on md) -->
                                    <div class="col-xl-2 col-md-6 text-end">
                                        <a href="" class="btn btn-danger light w-100"
                                            title="Click here to remove filter">
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
                            <input type="radio" name="campaign_type" id="campaign_type_instant" value="instant">
                            <span>Instant</span>
                        </label>
                        <label class="mb-2 mb-lg-3 w-auto d-inline-block" for="campaign_type_schedule">
                            <input type="radio" name="campaign_type" id="campaign_type_schedule" value="schedule">
                            <span>Schedule</span>
                        </label>
                        <label class="mb-2 mb-lg-3 w-auto d-inline-block" for="campaign_type_segment">
                            <input type="radio" name="campaign_type" id="campaign_type_segment" value="particular">
                            <span>Segment</span>
                        </label>
                        <label class="mb-2 mb-lg-3 w-auto d-inline-block" for="campaign_type_api">
                            <input type="radio" name="campaign_type" id="campaign_type_api" value="api">
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
                                    <tbody>
                                      
                                    </tbody>
                                </table>
                            </div>
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
                                <!-- Analytics Section (only shown when there's data) -->
                                <div id="analyticsSection" class="col-lg-6 mb-4 mb-lg-0">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0"><i class="fas fa-chart-pie text-primary me-2"></i>Performance
                                            Analytics</h6>
                                        <div class="badge bg-primary rounded-pill">Live</div>
                                    </div>
                                    <div id="chart" class="border rounded p-3 bg-light"></div>
                                </div>

                                <!-- Notification Preview -->
                                <div class="col-lg-6">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <h6 class="mb-0"><i class="fas fa-bell text-primary me-2"></i>Push Preview</h6>
                                         <div class="d-flex align-items-center mb-3">
                                            <img src="{{ asset('images/chrome.png') }}" class="me-1" alt="Browser Icon">
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
                                                <div class="text-muted small mb-2 line-clamp-2" id="prv_desc"
                                                    style="-webkit-line-clamp:2;display:-webkit-box;-webkit-box-orient:vertical;overflow:hidden">
                                                </div>
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
                                </div>
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

    </section>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/bootstrap-daterangepicker/daterangepicker.js') }}"></script>
    <script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(function() {
            /* ------------------------------------------------- Select2 (domains) */
             const $domain = $('#filter_domain').select2({
            placeholder: 'Search for Domain…',
            allowClear: true,
            minimumInputLength: 0, // show list even when empty
            ajax: {
                url: "{{ route('domain.domain-list') }}",
                dataType: 'json',
                delay: 250,
                cache: true,
                data: params => ({ q: params.term || '' }),
                processResults: res => ({
                    results: (res.status ? res.data : []).map(item => ({
                        id:   item.text,       // matches your controller
                        text: item.text
                    }))
                })
            },
            templateResult: domain => {
                if (domain.loading) return domain.text;
                return $(
                    `<span><i class="fal fa-globe me-2"></i>${domain.text}</span>`
                );
            },
            escapeMarkup: markup => markup
        });

        // On open, if no term typed, trigger an empty search to load all domains
        $domain.on('select2:open', () => {
            const search = $domain.data('select2').dropdown.$search;
            if (!search.val()) {
                $domain.select2('trigger', 'query', { term: '' });
            }
        });

            

            /* ------------------------------------------------- DataTable */
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
                    url: '{{ route('notification.view') }}',
                    data: d => {
                        d.search_term = $('#filter_campaign_name').val();
                        d.status = $('#filter_status').val();
                        d.site_web = $('#filter_domain').val();
                        d.last_send = $('#daterange').val();
                        d.campaign_type = $('input[name="campaign_type"]:checked').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'campaign_name',
                        name: 'campaign_name'
                    },
                    {
                        data: 'domain',
                        name: 'domain',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'sent_time',
                        name: 'sent_time'
                    },
                    {
                        data: 'clicks',
                        name: 'clicks'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                order: [
                    [1, 'desc']
                ],
                lengthMenu: [10, 25, 50]
            });

            // redraw when any filter changes
            $('#filterForm').on('change', 'input, select', () => table.draw())
                .on('keyup', 'input[name="campaign_name"]', () => table.draw());

            $('input[name="campaign_type"]').on('change', () => table.draw());

            /* ------------------------------------------------- Date-range picker */
            $('#daterange').daterangepicker({
                    autoUpdateInput: false, // show "all dates" until user picks
                    locale: {
                        format: 'MM/DD/YYYY',
                        cancelLabel: 'Clear'
                    },
                    maxDate: new Date(),
                    opens: 'left'
                })
                .on('apply.daterangepicker', function(ev, picker) {
                    $(this).val(picker.startDate.format('MM/DD/YYYY') +
                        ' - ' +
                        picker.endDate.format('MM/DD/YYYY'));
                    table.draw();
                })
                .on('cancel.daterangepicker', function() {
                    $(this).val('');
                    table.draw();
                });
        });
    </script>
    <script>
        $(function() {
            /* ---------- Chart instances ------------------ */
            let deliveryChart, engagementChart;
            let totalSent = 0;

            function safeRenderChart(sent, received, clicked) {
                totalSent = sent;
                const el = document.querySelector('#chart');
                if (deliveryChart) deliveryChart.destroy();
                if (engagementChart) engagementChart.destroy();

                const analyticsSection = $('#analyticsSection');
                
                if (sent === 0) {
                    analyticsSection.hide();
                    $('#modalContent .col-lg-6').removeClass('col-lg-6').addClass('col-12');
                    return;
                } else {
                    analyticsSection.show();
                    $('#modalContent .col-12').removeClass('col-12').addClass('col-lg-6');
                }

                // Calculate percentages
                const receivedPercentage = (received / sent) * 100;
                const clickedPercentage = (clicked / sent) * 100;
                const notReceivedPercentage = 100 - receivedPercentage;
                const notClickedPercentage = 100 - clickedPercentage;

                // Create tabs container
                const chartContainer = $(el);
                chartContainer.empty().html(`
                    <div class="text-center mb-3">
                        <span class="badge bg-primary">Total Sent: ${sent.toLocaleString()}</span>
                    </div>
                    <ul class="nav nav-tabs mb-3" id="chartTabs" role="tablist">
                        <li class="nav-item w-50" role="presentation">
                            <button class="nav-link w-100 active" id="delivery-tab" data-bs-toggle="tab" 
                                    data-bs-target="#delivery-chart" type="button" role="tab">
                                Delivery
                            </button>
                        </li>
                        <li class="nav-item w-50" role="presentation">
                            <button class="nav-link w-100" id="engagement-tab" data-bs-toggle="tab" 
                                    data-bs-target="#engagement-chart" type="button" role="tab">
                                Engagement
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="delivery-chart" role="tabpanel"></div>
                        <div class="tab-pane fade" id="engagement-chart" role="tabpanel"></div>
                    </div>
                `);

                // Render Delivery Chart
                renderDeliveryChart(sent, received);
                
                // Render Engagement Chart
                renderEngagementChart(received, clicked);

                // Initialize Bootstrap tabs and handle tab change events
                const tabEls = document.querySelectorAll('#chartTabs button[data-bs-toggle="tab"]');
                tabEls.forEach(tabEl => {
                    tabEl.addEventListener('shown.bs.tab', event => {
                        if (event.target.id === 'delivery-tab') {
                            renderDeliveryChart(totalSent, received);
                        } else if (event.target.id === 'engagement-tab') {
                            renderEngagementChart(received, clicked);
                        }
                    });
                });

                // Show first tab by default
                new bootstrap.Tab(document.querySelector('#delivery-tab')).show();
            }

            function renderDeliveryChart(sent, received) {
                const receivedPercentage = (received / sent) * 100;
                const notReceivedPercentage = 100 - receivedPercentage;
                
                if (deliveryChart) deliveryChart.destroy();
                
                deliveryChart = new ApexCharts(document.querySelector('#delivery-chart'), {
                    chart: { 
                        type: 'pie', 
                        height: 250,
                        animations: {
                            enabled: true,
                            easing: 'easeinout',
                            speed: 800
                        }
                    },
                    series: [receivedPercentage, notReceivedPercentage],
                    labels: [
                        `Received (${received.toLocaleString()})`, 
                        `Not Received (${(sent - received).toLocaleString()})`
                    ],
                    legend: { 
                        position: 'bottom',
                        markers: { radius: 3 }
                    },
                    colors: ['#1cc88a', '#e74a3b'],
                    dataLabels: {
                        enabled: true,
                        formatter: function(val) {
                            return Math.round(val) + '%';
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: function(value, { seriesIndex }) {
                                const counts = [received, sent - received];
                                return `${counts[seriesIndex].toLocaleString()} users (${Math.round(value)}%)`;
                            }
                        }
                    }
                });
                deliveryChart.render();
            }

            function renderEngagementChart(received, clicked) {
                const clickedPercentage = totalSent ? (clicked / totalSent) * 100 : 0;
                const notClickedPercentage = 100 - clickedPercentage;
                
                if (engagementChart) engagementChart.destroy();
                
                engagementChart = new ApexCharts(document.querySelector('#engagement-chart'), {
                    chart: { 
                        type: 'pie', 
                        height: 250,
                        animations: {
                            enabled: true,
                            easing: 'easeinout',
                            speed: 800
                        }
                    },
                    series: [clickedPercentage, notClickedPercentage],
                    labels: [
                        `Clicked (${clicked.toLocaleString()})`, 
                        `Not Clicked (${(received - clicked).toLocaleString()})`
                    ],
                    legend: { 
                        position: 'bottom',
                        markers: { radius: 3 }
                    },
                    colors: ['#36b9cc', '#f6c23e'],
                    dataLabels: {
                        enabled: true,
                        formatter: function(val) {
                            return Math.round(val) + '%';
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: function(value, { seriesIndex }) {
                                const counts = [clicked, received - clicked];
                                return `${counts[seriesIndex].toLocaleString()} users (${Math.round(value)}%)`;
                            }
                        }
                    }
                });
                engagementChart.render();
            }

            /* ---------- loader + Ajax handler ----------------------------- */
            $('body').on('click', '.report-btn', function() {
                const url = $(this).data('url');

                $('#modalSpinner').removeClass('d-none');
                $('#modalContent').addClass('d-none');
                $('#reportModal').modal('show');

                $.getJSON(url)
                    .done(res => {
                        if (!res.status) throw new Error();

                        const d = res.data;

                        /* fill preview */
                        $('#campaign_name').text(d.title).attr('title', d.title);
                        $('#prv_title').text(d.title).attr('title', d.title);
                        $('#prv_desc').text(d.description).attr('title', d.description);
                        $('#message_image').attr('src', d.banner_image);
                        $('#icon_prv').attr('src', d.banner_icon);
                        $('#prv_link').text(new URL(d.link).hostname).attr('href', d.link);

                        /* buttons */
                        $('#btn_prv, #btn2_prv').addClass('d-none');
                        if (d.btns.length) {
                            $('#btn_prv').removeClass('d-none')
                                .find('#btn_title1').text(d.btns[0].title)
                                .parent().attr('href', d.btns[0].url);

                            if (d.btns[1]) {
                                $('#btn2_prv').removeClass('d-none')
                                    .find('#btn_title2').text(d.btns[1].title)
                                    .parent().attr('href', d.btns[1].url);
                            }
                        }

                        /* pie chart */
                        safeRenderChart(
                            d.analytics.sent || d.analytics.delivered,
                            d.analytics.received,
                            d.analytics.clicked
                        );

                        /* reveal */
                        $('#modalSpinner').addClass('d-none');
                        $('#modalContent').removeClass('d-none');
                    })
                    .fail(() => {
                        $('#modalSpinner').addClass('d-none');
                        Swal.fire('Error', 'Failed to load report', 'error');
                    });
            });
        });
    </script>
   <script>
        $(function() {
            // assume your DataTable instance is in a `table` variable
            const table = $('#datatable').DataTable(); // or however you initialize

            $('body').on('click', '.cancel-btn', function(e) {
            e.preventDefault();

            const $btn = $(this);
            const url  = $btn.data('url');

            // disable to prevent double-clicks
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
                // perform AJAX GET
                $.ajax({
                    url: url,
                    type: 'GET',
                })
                .done((res) => {
                    if (res.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Cancelled',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    table.draw(false); // redraw, staying on current page
                    } else {
                    Swal.fire('Error', res.message || 'Could not cancel', 'error');
                    }
                })
                .fail((xhr) => {
                    const msg = xhr.responseJSON?.message 
                            || Object.values(xhr.responseJSON?.errors || {}).flat().join('\n')
                            || 'Request failed';
                    Swal.fire('Error', msg, 'error');
                })
                .always(() => {
                    // re-enable regardless
                    $btn.prop('disabled', false);
                });
                } else {
                // user canceled the confirmation
                $btn.prop('disabled', false);
                }
            });
            });
        });
    </script>
@endpush