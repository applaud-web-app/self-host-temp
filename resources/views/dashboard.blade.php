@extends('layouts.master')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
@endpush

@section('content')
    <section class="content-body">
        <div class="container-fluid position-relative">
            <div class="d-flex flex-wrap align-items-center justify-content-between text-head">
                <div class="me-3 d-flex align-items-center mb-2">
                    <h2 class="mb-0 me-auto applaud">Self-Hosted Admin Dashboard</h2>
                    <button id="refresh-button" class="btn btn-sm btn-primary ms-2 align-items-center refresh-button"><i
                            class="me-2 far fa-sync-alt"></i> Refresh</button>
                </div>
                <div class="form-group mb-3" style="min-width: 350px" id="hiddenSelect">
                    <select class="default-select form-control form-select wide" id="domain-select">
                        <option value="">Search for Domain...</option>
                    </select>
                </div>
            </div>

            <!-- 8 Summary Cards -->
            <div class="row">
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="widget-stat card">
                        <div class="card-body p-4">
                            <div class="media ai-icon">
                                <span class="me-3 bgl-primary text-primary"><i class="fal fa-users"></i></span>
                                <div class="media-body">
                                    <p class="mb-0">Total Subscribers</p>
                                    <h4 class="mb-0">1,200</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="widget-stat card">
                        <div class="card-body p-4">
                            <div class="media ai-icon">
                                <span class="me-3 bgl-secondary text-secondary"><i class="fal fa-user-plus"></i></span>
                                <div class="media-body">
                                    <p class="mb-0">This Month Subs</p>
                                    <h4 class="mb-0">300</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="widget-stat card">
                        <div class="card-body p-4">
                            <div class="media ai-icon">
                                <span class="me-3 bgl-success text-success"><i class="fal fa-calendar-day"></i></span>
                                <div class="media-body">
                                    <p class="mb-0">Today Subs</p>
                                    <h4 class="mb-0">25</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="widget-stat card">
                        <div class="card-body p-4">
                            <div class="media ai-icon">
                                <span class="me-3 bgl-warning text-warning"><i class="fal fa-bullhorn"></i></span>
                                <div class="media-body">
                                    <p class="mb-0">Active Campaigns</p>
                                    <h4 class="mb-0">5</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="widget-stat card">
                        <div class="card-body p-4">
                            <div class="media ai-icon">
                                <span class="me-3 bgl-info text-info"><i class="fal fa-bell"></i></span>
                                <div class="media-body">
                                    <p class="mb-0">Total Notifications</p>
                                    <h4 class="mb-0">850</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="widget-stat card">
                        <div class="card-body p-4">
                            <div class="media ai-icon">
                                <span class="me-3 bgl-danger text-danger"><i class="fal fa-globe"></i></span>
                                <div class="media-body">
                                    <p class="mb-0">Total Domains</p>
                                    <h4 class="mb-0">12</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="widget-stat card">
                        <div class="card-body p-4">
                            <div class="media ai-icon">
                                <span class="me-3 bgl-light text-dark"><i class="fal fa-percentage"></i></span>
                                <div class="media-body">
                                    <p class="mb-0">Campaign Success Rate</p>
                                    <h4 class="mb-0">75%</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="widget-stat card">
                        <div class="card-body p-4">
                            <div class="media ai-icon">
                                <span class="me-3 bgl-light text-dark"><i class="fal fa-envelope-open"></i></span>
                                <div class="media-body">
                                    <p class="mb-0">Avg Open Rate</p>
                                    <h4 class="mb-0">48%</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Keep the Charts and Table as before -->
            <div class="row">
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="fs-20 mb-0">Daily Subscribers</h4>
                        </div>
                        <div class="card-body p-0">
                            <div id="subscriberChart" style="height: 300px; width: 100%;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="fs-20 mb-0">Notifications Sent</h4>
                        </div>
                        <div class="card-body p-0">
                            <div id="notificationsChart" style="height: 300px; width: 100%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Campaigns Table -->
            <div class="col-xl-12">
                <div class="card ">
                    <div class="card-header ">
                        <h4 class="card-title">Recent Campaigns</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table display">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Campaign Name</th>
                                        <th>Status</th>
                                        <th>Sent Time</th>
                                        <th>Clicks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>Summer Sale</td>
                                        <td><span class="badge light bg-success">Sent</span></td>
                                        <td>10:30 AM, 05/28/2025</td>
                                        <td>150</td>
                                    </tr>
                                    <tr>
                                        <td>2</td>
                                        <td>Newsletter</td>
                                        <td><span class="badge light bg-secondary">Ongoing</span></td>
                                        <td>11:00 AM, 05/30/2025</td>
                                        <td>75</td>
                                    </tr>
                                    <tr>
                                        <td>3</td>
                                        <td>Product Launch</td>
                                        <td><span class="badge light bg-warning">Pending</span></td>
                                        <td>--</td>
                                        <td>0</td>
                                    </tr>
                                    <tr>
                                        <td>4</td>
                                        <td>Event Invite</td>
                                        <td><span class="badge light bg-danger">Failed</span></td>
                                        <td>09:00 AM, 05/27/2025</td>
                                        <td>20</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/echarts@5.5.1/dist/echarts.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var subscriberChart = echarts.init(document.getElementById('subscriberChart'));
            var notificationsChart = echarts.init(document.getElementById('notificationsChart'));

            var subscriberOption = {
                tooltip: {
                    trigger: 'axis'
                },
                xAxis: {
                    type: 'category',
                    data: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
                },
                yAxis: {
                    type: 'value'
                },
                series: [{
                    name: 'Subscribers',
                    data: [120, 200, 150, 80, 70, 110, 130],
                    type: 'line',
                    smooth: true,
                    areaStyle: {
                        color: 'rgba(0,123,255,0.2)'
                    },
                    lineStyle: {
                        color: 'rgba(0,123,255,1)',
                        width: 3
                    },
                    itemStyle: {
                        color: 'rgba(0,123,255,1)'
                    },
                    symbolSize: 8
                }]
            };

            var notificationsOption = {
                tooltip: {
                    trigger: 'axis'
                },
                xAxis: {
                    type: 'category',
                    data: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
                },
                yAxis: {
                    type: 'value'
                },
                series: [{
                    name: 'Notifications Sent',
                    data: [300, 450, 400, 350, 300, 500, 550],
                    type: 'bar',
                    itemStyle: {
                        color: 'rgba(40,167,69,1)'
                    },
                    barWidth: '50%'
                }]
            };

            subscriberChart.setOption(subscriberOption);
            notificationsChart.setOption(notificationsOption);

            window.addEventListener('resize', function() {
                subscriberChart.resize();
                notificationsChart.resize();
            });
        });
    </script>
    <script>
        $(function() {
            const placeholder = "Search for Domain...";
            $('#domain-select').select2({
                placeholder: "Search for Domain…",
                allowClear: true,
                minimumInputLength: 0,        // ← allow AJAX to fire even when empty
                ajax: {
                    url: "{{ route('domain.domain-list') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        console.log(params);
                        // when you open, params.term will be undefined; default to empty string
                        return { q: params.term || '' };
                    },
                    processResults: function(response) {
                        return {
                            results: response.data.map(function(item) {
                                return {
                                    id: item.text,
                                    text: item.text 
                                };
                            })
                        };
                    },
                    cache: true
                },
                // optional: show globe icon
                templateResult: function(domain) {
                    if (domain.loading) return domain.text;
                    return $(
                      '<span><i class="fal fa-globe me-2"></i>' +
                      domain.text +
                      '</span>'
                    );
                },
                escapeMarkup: function(markup) { return markup; }
            });

            // Trigger the initial load on open (some Select2 builds don’t auto-fetch on open)
            $('#domain-select').on('select2:open', function() {
                // if no search term, manually trigger the query
                const select = $(this);
                if (!select.data('select2').dropdown.$search.val()) {
                    select.select2('trigger', 'query', { term: '' });
                }
            });
        });

        // $('#domain-select').on('select2:select', function(e) {
        //     $('#preloader-cart').toggleClass('d-none d-flex');
        //     $('#dashboardBody').removeClass('d-none');
        //     $('#onLoading').addClass('d-none');

        //     var selectedDomainId = e.params.data.id;

        //     $('#currentDomain').val(e.params.data.text);

        //     const domainName = $('#currentDomain').val();
        //     $.ajax({
        //         url: '',
        //         type: 'GET',
        //         data: {
        //             domain_name: domainName
        //         },
        //         success: function(response) {
        //             if (response.status) {
        //                 $('#preloader-cart').toggleClass('d-none d-flex');
        //                 $('#total_sub').text(response.data['total_subscribers'] || 0);
        //                 $('#monthly_sub').text(response.data['monthly_subscribers'] || 0);
        //                 $('#today_sub').text(response.data['today_subscribers'] || 0);

        //                 if (response.data['import_data']['total'] == 0 || response.data['import_data'][
        //                         'inactive'
        //                     ] == response.data['import_data']['total']) {
        //                     $('#imported_stats').addClass('d-none');
        //                 } else {
        //                     $('#imported_stats').removeClass('d-none');
        //                 }
        //                 $('#imported_sub').text(response.data['import_data']['total'] || 0);
        //                 $('#imported_active_sub').text(response.data['import_data']['active'] || 0);
        //                 $('#imported_inactive_sub').text(response.data['import_data']['inactive'] || 0);

        //                 const notification = response.data['recent_campaigns'];
        //                 if (notification && notification.length > 0) {
        //                     updateNotificationTable(notification);
        //                 } else {
        //                     $('#dataBox').html(
        //                         '<tr><td colspan="5">No notifications available.</td></tr>'
        //                     );
        //                 }

        //                 updateChart(
        //                     response.data['graph_data']['dates'],
        //                     response.data['graph_data']['subscribers']
        //                 );
        //             } else {
        //                 $('#preloader-cart').toggleClass('d-none d-flex');
                     
        //             }
        //         },
        //         error: function() {
        //             $('#preloader-cart').toggleClass('d-none d-flex');
        //         }
        //     });
        // });

        // // Refresh button logic
        $('.refresh-button').on('click', function() {
            var selectedDomainId = $('#domain-select').val();

            if (!selectedDomainId) {
                iziToast.warning({
                    title: 'Warning',
                    message: 'Please select a domain first.',
                    position: 'topRight'
                });
                return;
            }

            $('#preloader-cart').toggleClass('d-none d-flex');
            const domainName = $('#currentDomain').val();
            $.ajax({
                url: '',
                type: 'GET',
                data: {
                    domain_name: domainName,
                    refresh: 1
                },
                success: function(response) {
                    $('#preloader-cart').toggleClass('d-none d-flex');
                    if (response.status) {
                        $('#total_sub').text(response.data['total_subscribers'] || 0);
                        $('#monthly_sub').text(response.data['monthly_subscribers'] || 0);
                        $('#today_sub').text(response.data['today_subscribers'] || 0);


                        $('#imported_sub').text(response.data['import_data']['total'] || 0);
                        $('#imported_active_sub').text(response.data['import_data']['active'] || 0);
                        $('#imported_inactive_sub').text(response.data['import_data']['inactive'] || 0);

                        const notification = response.data['recent_campaigns'];
                        if (notification && notification.length > 0) {
                            updateNotificationTable(notification);
                        } else {
                            $('#dataBox').html(
                                '<tr><td colspan="5" class="text-center text-danger">No Data Available</td></tr>'
                            );
                        }

                        updateChart(
                            response.data['graph_data']['dates'],
                            response.data['graph_data']['subscribers']
                        );

                        iziToast.success({
                            title: 'Success',
                            message: 'Data refreshed successfully.',
                            position: 'topRight'
                        });
                    } else {
                        iziToast.error({
                            title: 'Error',
                            message: 'Failed to refresh data.',
                            position: 'topRight'
                        });
                    }
                },
                error: function() {
                    $('#preloader-cart').toggleClass('d-none d-flex');
                    iziToast.error({
                        title: 'Error',
                        message: 'An error occurred while refreshing data.',
                        position: 'topRight'
                    });
                }
            });
        });
    </script>
@endpush
