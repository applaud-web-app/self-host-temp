@extends('layouts.master')

@push('styles')
<link rel="stylesheet" href="{{ asset('/vendor/bootstrap-daterangepicker/daterangepicker.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
@endpush

@section('content')
<section class="content-body">
    <div class="container-fluid position-relative">
        <div class="d-flex flex-wrap align-items-center justify-content-between text-head">
           <h2 class="mb-3 me-auto applaud">Self-Hosted Admin Dashboard</h2>
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
                                <tr><td>1</td><td>Summer Sale</td><td><span class="badge light bg-success">Sent</span></td><td>10:30 AM, 05/28/2025</td><td>150</td></tr>
                                <tr><td>2</td><td>Newsletter</td><td><span class="badge light bg-secondary">Ongoing</span></td><td>11:00 AM, 05/30/2025</td><td>75</td></tr>
                                <tr><td>3</td><td>Product Launch</td><td><span class="badge light bg-warning">Pending</span></td><td>--</td><td>0</td></tr>
                                <tr><td>4</td><td>Event Invite</td><td><span class="badge light bg-danger">Failed</span></td><td>09:00 AM, 05/27/2025</td><td>20</td></tr>
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
<script src="https://cdn.jsdelivr.net/npm/echarts@5.5.1/dist/echarts.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var subscriberChart = echarts.init(document.getElementById('subscriberChart'));
        var notificationsChart = echarts.init(document.getElementById('notificationsChart'));

        var subscriberOption = {
            tooltip: { trigger: 'axis' },
            xAxis: { type: 'category', data: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] },
            yAxis: { type: 'value' },
            series: [{
                name: 'Subscribers',
                data: [120, 200, 150, 80, 70, 110, 130],
                type: 'line',
                smooth: true,
                areaStyle: { color: 'rgba(0,123,255,0.2)' },
                lineStyle: { color: 'rgba(0,123,255,1)', width: 3 },
                itemStyle: { color: 'rgba(0,123,255,1)' },
                symbolSize: 8
            }]
        };

        var notificationsOption = {
            tooltip: { trigger: 'axis' },
            xAxis: { type: 'category', data: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] },
            yAxis: { type: 'value' },
            series: [{
                name: 'Notifications Sent',
                data: [300, 450, 400, 350, 300, 500, 550],
                type: 'bar',
                itemStyle: { color: 'rgba(40,167,69,1)' },
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
@endpush
