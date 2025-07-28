@extends('layouts.master')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
    <style>
        .refresh-icon {
            cursor: pointer;
            position: absolute;
            top: 10px;
            right: 10px;
            color: #f93a0b;
        }
    </style>
@endpush

@section('content')
    <section class="content-body" id="dashboard_page">
        <div class="container-fluid ">
            <div class="d-flex flex-wrap align-items-center justify-content-between text-head">
                <div class="me-3 d-flex align-items-center mb-2">
                    <h2 class="mb-0 me-auto applaud">Admin Dashboard</h2>
                    <button id="refresh-all" class="btn btn-sm btn-primary ms-2 align-items-center refresh-button"><i
                            class="me-2 far fa-sync-alt"></i> Refresh</button>
                </div>
                <div class="form-group mb-3" style="min-width: 320px" id="hiddenSelect">
                    <select class="default-select form-control form-select wide" id="domain-select">
                        <option value="">Search for Domain...</option>
                    </select>
                </div>
            </div>


            <div class="row g-lg-3 g-1 ">  
                @php
                    $cards = [
                        'total' => ['label' => 'Total Subscribers', 'icon' => 'fa-users', 'badge' => 'primary'],
                        'monthly' => ['label' => 'This Month', 'icon' => 'fa-user-plus', 'badge' => 'secondary'],
                        'today' => ['label' => 'Today', 'icon' => 'fa-calendar-day', 'badge' => 'success'],
                        'active' => ['label' => 'Total Active', 'icon' => 'fa-check-circle', 'badge' => 'info'],
                    ];
                @endphp
                @foreach ($cards as $key => $c)
                    <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                        <div class="widget-stat card ">
                            <div class="card-body position-relative">
                                <div class="media ai-icon">
                                    <span class="me-lg-3 me-0 bgl-{{ $c['badge'] }} text-{{ $c['badge'] }}">
                                        <i class="fal {{ $c['icon'] }}"></i>
                                    </span>
                                    <div class="media-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <p class="mb-0">{{ $c['label'] }}</p>
                                            <i class="fas fa-sync-alt card-refresh refresh-icon"
                                                data-metric="{{ $key }}" style="cursor:pointer"
                                                title="Refresh"></i>
                                        </div>
                                        <h4 id="card-{{ $key }}" class="mb-0">0</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- FOR NOTIFICATION STATS --}}

           <div class="row g-lg-3 g-1 ">  
                @php
                    $notifCards = [
                        'total' => ['label' => 'Total Notifications', 'icon' => 'fa-bell', 'badge' => 'info'],
                        'segment' => ['label' => 'Segment Notifications','icon' => 'fa-users-cog','badge' => 'warning'],
                        'broadcast' => ['label' => 'Broadcast Notifications','icon' => 'fa-globe','badge' => 'primary'],
                        'plugin' => ['label' => 'Plugin Notifications', 'icon' => 'fa-plug', 'badge' => 'success'],
                    ];
                @endphp
                @foreach ($notifCards as $key => $c)
                    <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                        <div class="widget-stat card ">
                            <div class="card-body position-relative">
                                <div class="media ai-icon">
                                    <span class="me-lg-3 me-0 bgl-{{ $c['badge'] }} text-{{ $c['badge'] }}">
                                        <i class="fal {{ $c['icon'] }}"></i>
                                    </span>
                                    <div class="media-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <p class="mb-0">{{ $c['label'] }}</p>
                                            <i class="fas fa-sync-alt notif-refresh refresh-icon"
                                            data-metric="{{ $key }}"
                                            title="Refresh"></i>
                                        </div>
                                        <h4 id="notif-{{ $key }}" class="mb-0">0</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Weekly charts --}}
            <div class="row">
                <div class="col-xl-6 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between">
                            <h4 class="mb-0">Weekly Subscribers</h4>
                            <i class="fas fa-sync-alt chart-refresh text-primary" data-metric="subscribers"
                                style="cursor:pointer"></i>
                        </div>
                        <div class="card-body">
                            <div id="weeklySubscribersChart" style="height:300px; width:100%;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between">
                            <h4 class="mb-0">Weekly Notifications</h4>
                            <i class="fas fa-sync-alt chart-refresh text-primary" data-metric="notifications"
                                style="cursor:pointer"></i>
                        </div>
                        <div class="card-body">
                            <div id="weeklyNotificationsChart" style="height:300px; width:100%;"></div>
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
    $(function() {
        const statsUrl = "{{ route('dashboard.domain-stats') }}";
        const notifUrl = "{{ route('dashboard.notification-stats') }}";
        const weeklyUrl = "{{ route('dashboard.weekly-stats') }}";
        const LS_KEY    = 'dashboard.selectedDomain';
        const SERVER_INITIAL_DOMAIN = @json($initialDomain);

        // ——— Rate limiter state —————————————————————————————————————
        let refreshCount = 0;
        let cooling      = false;
        const COOLDOWN_MS = 60_000;

        function startCooling() {
            cooling = true;
            iziToast.error({
                title: 'Cooldown active',
                message: 'Too many refreshes! Please wait 1 minute before trying again.',
                position: 'topRight'
            });
            setTimeout(() => {
                cooling = false;
                refreshCount = 0;
                iziToast.success({
                    title: 'You can refresh again',
                    message: 'Feel free to update stats now.',
                    position: 'topRight'
                });
            }, COOLDOWN_MS);
        }

        function checkRateLimit() {
            if (cooling) {
                iziToast.warning({
                    title: 'On cooldown',
                    message: 'Please wait a minute before refreshing again.',
                    position: 'topRight'
                });
                return false;
            }
            refreshCount++;
            if (refreshCount > 7) {
                startCooling();
                return false;
            }
            return true;
        }

        // ——— State & Helpers —————————————————————————————————————
        let currentDomain = localStorage.getItem(LS_KEY) || SERVER_INITIAL_DOMAIN;

        function setDomain(dom) {
            currentDomain = dom;
            localStorage.setItem(LS_KEY, dom);
        }

        function animateValue(el, start, end, maxDuration = 3000) {
            start = Number(el.textContent.replace(/,/g, '')) || start;

            // If the value is the same, don't animate
            if (start === end) {
                el.textContent = new Intl.NumberFormat().format(end);
                return;
            }

            // Calculate the range (difference between start and end)
            const range = Math.abs(end - start);

            // Calculate the step size dynamically based on the range
            let step;
            let steps;

            if (range < 1000) {
                // For values below 1000, use smaller steps
                steps = 50;  // Target around 50 steps
                step = Math.ceil(range / steps);
            } else if (range >= 1000 && range < 10000) {
                // For values between 1000 and 10000, make the steps a little larger
                steps = 100;  // Target around 100 steps
                step = Math.ceil(range / steps);
            } else if (range >= 10000 && range < 100000) {
                // For values between 10k and 100k, use a larger step size
                steps = 200;  // Target around 200 steps
                step = Math.ceil(range / steps);
            } else {
                // For values above 100,000, increase the step size even more
                steps = 500;  // Target around 500 steps
                step = Math.ceil(range / steps);
            }

            // Calculate the duration: faster for larger ranges, but with a cap
            const adjustedDuration = Math.min(range / step * 10, maxDuration); // Adjust duration dynamically

            let cur = start;
            const inc = end > start ? step : -step;

            const timer = setInterval(() => {
                cur += inc;
                if (Math.abs(cur - end) < Math.abs(inc)) {
                    cur = end;  // Ensure we don't overshoot the target value
                    clearInterval(timer);
                }

                el.textContent = new Intl.NumberFormat().format(cur);
            }, adjustedDuration / range); // Adjust the interval speed based on range
        }

        const charts = {};
        function renderChart(domId, labels, data, name) {
            let chart = charts[domId] || echarts.init(document.getElementById(domId));
            chart.setOption({
                tooltip: { trigger: 'axis' },
                xAxis: { type: 'category', data: labels },
                yAxis: { type: 'value' },
                series: [{ name, type: 'bar', data }]
            });
            window.addEventListener('resize', () => chart.resize());
            charts[domId] = chart;
        }

        // ——— Bootstrap empty charts —————————————————————————————————
        const defaultLabels = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
        const emptySeries   = Array(7).fill(0);
        renderChart('weeklySubscribersChart', defaultLabels, emptySeries, 'Subscribers');
        renderChart('weeklyNotificationsChart', defaultLabels, emptySeries, 'Notifications');

        // ——— Fetchers ———————————————————————————————————————————
        function fetchDomainStats(refresh = false) {
            if (!currentDomain) return $.Deferred().reject().promise();
            return $.getJSON(statsUrl, { domain_name: currentDomain, refresh: refresh?1:0 });
        }
        function fetchNotificationStats(refresh = false) {
            if (!currentDomain) return $.Deferred().reject().promise();
            return $.getJSON(notifUrl, { domain_name: currentDomain, refresh: refresh?1:0 });
        }
        function fetchWeeklyStats(refresh = false, metric = null) {
            if (!currentDomain) return $.Deferred().reject().promise();
            const params = { domain_name: currentDomain, refresh: refresh?1:0 };
            if (metric) params.metric = metric;
            return $.getJSON(weeklyUrl, params);
        }

        // ——— Select2 setup ——————————————————————————————————————
        $('#domain-select').select2({
            placeholder: 'Search for Domain…',
            allowClear: true,
            ajax: {
                url: "{{ route('domain.domain-list') }}",
                dataType: 'json', delay: 250,
                data: p => ({ q: p.term || '' }),
                processResults: r => ({ results: r.data.map(i => ({ id: i.text, text: i.text })) }),
                cache: true
            },
            templateResult: d => d.loading ? d.text : $(`<span><i class="fal fa-globe me-1"></i>${d.text}</span>`),
            escapeMarkup: m => m
        });
        if (currentDomain) {
            const opt = new Option(currentDomain, currentDomain, true, true);
            $('#domain-select').append(opt).trigger('change');
        }

        // ——— Event Handlers ——————————————————————————————————————

        // Domain selection
        $('#domain-select').on('select2:select', e => {
            if (!checkRateLimit()) return;
            setDomain(e.params.data.id);

            fetchDomainStats().done(res => {
                if (res.status) {
                    Object.entries(res.data).forEach(([k,v]) =>
                        animateValue($(`#card-${k}`)[0], 0, v, 1000)
                    );
                    iziToast.success({ title:'Subscribers Updated', message:'Subscriber stats refreshed', position:'topRight' });
                }
            });

            fetchNotificationStats().done(res => {
                if (res.status) {
                    Object.entries(res.data).forEach(([k,v]) =>
                        animateValue($(`#notif-${k}`)[0], 0, v, 800)
                    );
                    iziToast.success({ title:'Notifications Updated', message:'Notification stats refreshed', position:'topRight' });
                }
            });

            fetchWeeklyStats().done(res => {
                if (res.status) {
                    const { labels, subscribers, notifications } = res.data;
                    renderChart('weeklySubscribersChart', labels, subscribers, 'Subscribers');
                    renderChart('weeklyNotificationsChart', labels, notifications, 'Notifications');
                    iziToast.success({ title:'Charts Updated', message:'Weekly charts refreshed', position:'topRight' });
                }
            });
        });

        // Per-subscriber-card refresh
        const inFlightCard = {};
        $('.card-refresh').click(function() {
            if (!checkRateLimit()) return;
            const icon   = $(this);
            const metric = icon.data('metric');
            if (inFlightCard[metric]) {
                return iziToast.warning({ title:'Please wait', message:'Refresh in progress.', position:'topRight' });
            }
            inFlightCard[metric] = true;
            icon.addClass('fa-spin');

            fetchDomainStats(true)
              .done(res => {
                if (res.status) {
                    Object.entries(res.data).forEach(([k,v]) =>
                        animateValue($(`#card-${k}`)[0], 0, v, 1000)
                    );
                    iziToast.success({ title:'Subscribers Refreshed', message:'Subscriber stats updated', position:'topRight' });
                }
              })
              .always(() => {
                icon.removeClass('fa-spin');
                setTimeout(() => inFlightCard[metric] = false, 300);
              });
        });

        // Per-notification-card refresh
        const inFlightNotif = {};
        $('.notif-refresh').click(function() {
            if (!checkRateLimit()) return;
            const icon   = $(this);
            const metric = icon.data('metric');
            if (inFlightNotif[metric]) {
                return iziToast.warning({ title:'Please wait', message:'Refresh in progress.', position:'topRight' });
            }
            inFlightNotif[metric] = true;
            icon.addClass('fa-spin');

            fetchNotificationStats(true)
              .done(res => {
                if (res.status) {
                    Object.entries(res.data).forEach(([k,v]) =>
                        animateValue($(`#notif-${k}`)[0], 0, v, 800)
                    );
                    iziToast.success({ title:'Notifications Refreshed', message:'Notification stats updated', position:'topRight' });
                }
              })
              .always(() => {
                icon.removeClass('fa-spin');
                setTimeout(() => inFlightNotif[metric] = false, 300);
              });
        });

        // Per-chart refresh
        const inFlightChart = {};
        $('.chart-refresh').click(function() {
            if (!checkRateLimit()) return;
            const icon   = $(this);
            const metric = icon.data('metric');
            if (inFlightChart[metric]) {
                return iziToast.warning({ title:'Please wait', message:'Chart refresh in progress.', position:'topRight' });
            }
            inFlightChart[metric] = true;
            icon.addClass('fa-spin');

            fetchWeeklyStats(true, metric)
              .done(res => {
                if (res.status) {
                    const { labels, subscribers, notifications } = res.data;
                    if (metric==='subscribers' || !metric) {
                        renderChart('weeklySubscribersChart', labels, subscribers, 'Subscribers');
                    }
                    if (metric==='notifications' || !metric) {
                        renderChart('weeklyNotificationsChart', labels, notifications, 'Notifications');
                    }
                    iziToast.success({ title:'Chart Refreshed', message:'Weekly chart updated', position:'topRight' });
                }
              })
              .always(() => {
                icon.removeClass('fa-spin');
                setTimeout(() => inFlightChart[metric] = false, 300);
              });
        });

        // Global “Refresh All”
        $('#refresh-all').click(() => {
            if (!checkRateLimit()) return;
            const btnIcon = $('#refresh-all i.fa-sync-alt');
            btnIcon.addClass('fa-spin');
            
            // Fetch domain stats and animate values
            fetchDomainStats(true).done(res => {
                if (res.status) {
                    console.log(res);  // Debugging: Log the response
                    Object.entries(res.data).forEach(([k, v]) => {
                        animateValue($(`#card-${k}`)[0], 0, v, 1000);  // Ensure proper reference to the element and value
                    });
                    iziToast.success({ title: 'Subscribers Refreshed', message: 'Subscriber stats updated', position: 'topRight' });
                }
            });

            // Fetch notification stats and animate values
            fetchNotificationStats(true).done(res => {
                if (res.status) {
                    console.log(res);  // Debugging: Log the response
                    Object.entries(res.data).forEach(([k, v]) => {
                        animateValue($(`#notif-${k}`)[0], 0, v, 800);  // Ensure proper reference to the element and value
                    });
                    iziToast.success({ title: 'Notifications Refreshed', message: 'Notification stats updated', position: 'topRight' });
                }
            });

            // Fetch weekly stats and update the charts
            fetchWeeklyStats(true).done(res => {
                if (res.status) {
                    const { labels, subscribers, notifications } = res.data;
                    renderChart('weeklySubscribersChart', labels, subscribers, 'Subscribers');
                    renderChart('weeklyNotificationsChart', labels, notifications, 'Notifications');
                    iziToast.success({ title: 'Chart Refreshed', message: 'Weekly chart updated', position: 'topRight' });
                }
            });

            btnIcon.removeClass('fa-spin');
        });


        // ——— Initial load if pre-selected —————————————————————————————————
        if (currentDomain) {
            fetchDomainStats().done(res => {
                if (res.status) Object.entries(res.data).forEach(([k,v]) =>
                    animateValue($(`#card-${k}`)[0], 0, v, 1000)
                );
            });
            fetchNotificationStats().done(res => {
                if (res.status) Object.entries(res.data).forEach(([k,v]) =>
                    animateValue($(`#notif-${k}`)[0], 0, v, 800)
                );
            });
            fetchWeeklyStats().done(res => {
                if (res.status) {
                    const { labels, subscribers, notifications } = res.data;
                    renderChart('weeklySubscribersChart', labels, subscribers, 'Subscribers');
                    renderChart('weeklyNotificationsChart', labels, notifications, 'Notifications');
                }
            });
        }
    });
    </script>
@endpush