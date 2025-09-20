@extends('layouts.master')
@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">

    <style>
        .nav-tabs .nav-link {
            border: none;
        }

        .nav-tabs .nav-link.active {
            border-bottom: 2px solid var(--bs-primary);
            font-weight: 600;
        }

        .table td,
        .table th {
            white-space: nowrap;
        }

        .display-6 {
            font-size: 2rem;
        }

        /* info icon: bottom-right small circular */
        .kpi-card {
            overflow: hidden;
        }

        .kpi-info {
            position: absolute;
            right: .5rem;
            bottom: .5rem;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(0, 0, 0, .1);
        }

        .btn-xs {
            --bs-btn-padding-y: .15rem;
            --bs-btn-padding-x: .35rem;
            --bs-btn-font-size: .75rem;
        }

        /* Loading overlay */
        .loading-overlay {
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.6);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        .loading-overlay.show {
            display: flex;
        }

        .loading-overlay .spinner-border {
            width: 2.5rem;
            height: 2.5rem;
        }
    </style>
@endpush
@section('content')
    <section class="content-body">
        <div class="container-fluid">

            <!-- Top bar -->
            <div class="d-flex flex-wrap align-items-center justify-content-between text-head mb-3">
                <h2 class="me-auto mb-0 text-uppercase">Performance</h2>
            </div>

            <!-- Filters row -->
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 mt-4">
                <div class="d-flex align-items-center gap-2" style="min-width:300px">
                    <select id="domain-select" class="form-control">
                        <option value="">Select Domain</option>
                    </select>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="btn-group" role="group" aria-label="Date ranges">
                        <button type="button" class="btn btn-outline-primary perf-range active py-2 px-3"
                            data-range="24h">last 24 hours</button>
                        <button type="button" class="btn btn-outline-primary perf-range py-2 px-3" data-range="7d">seven
                            days</button>
                        <button type="button" class="btn btn-outline-primary perf-range py-2 px-3" data-range="28d">28
                            days</button>
                        <button type="button" class="btn btn-outline-primary perf-range py-2 px-3" data-range="3m">three
                            months</button>
                    </div>
                    <button type="button" class="btn btn-outline-secondary py-2 px-3" data-bs-toggle="modal"
                        data-bs-target="#moreRangeModal">
                        More +
                    </button>
                </div>
            </div>

            <!-- KPI cards -->
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-4">
                    <div class="card h-100 position-relative kpi-card kpi-clicks" style="border-left:4px solid #4e5cf8;">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="text-muted small">Total clicks</div>
                                <input class="form-check-input kpi-toggle" type="checkbox" checked data-kpi="clicks">
                            </div>
                            <div id="kpi-clicks" class="display-6 fw-semibold mt-1">0</div>
                            <button type="button" class="btn btn-outline-secondary btn-xs kpi-info"
                                data-bs-toggle="tooltip" data-bs-placement="left"
                                title="Total number of clicks in the selected period.">
                                <i class="far fa-info"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="card h-100 position-relative kpi-card kpi-impressions"
                        style="border-left:4px solid #7a4ff8;">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="text-muted small">Total impressions</div>
                                <input class="form-check-input kpi-toggle" type="checkbox" checked data-kpi="impressions">
                            </div>
                            <div id="kpi-impressions" class="display-6 fw-semibold mt-1">0</div>
                            <button type="button" class="btn btn-outline-secondary btn-xs kpi-info"
                                data-bs-toggle="tooltip" data-bs-placement="left"
                                title="Total number of impressions in the selected period.">
                                <i class="far fa-info"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="card h-100 position-relative kpi-card kpi-ctr" style="border-left:4px solid #00c49a;">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="text-muted small">Average CTR</div>
                                <!-- DEFAULT UNCHECKED -->
                                <input class="form-check-input kpi-toggle" type="checkbox" data-kpi="ctr">
                            </div>
                            <div id="kpi-ctr" class="display-6 fw-semibold mt-1">0%</div>
                            <button type="button" class="btn btn-outline-secondary btn-xs kpi-info"
                                data-bs-toggle="tooltip" data-bs-placement="left"
                                title="Average click-through rate = Clicks ÷ Impressions.">
                                <i class="far fa-info"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart -->
            <div class="card mb-3 position-relative">
                <div id="loading-chart" class="loading-overlay">
                    <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <small class="text-muted">Number of clicks</small>
                        <small id="range-label" class="text-muted">Last 24 hours</small>
                    </div>
                    <div style="height:280px">
                        <canvas id="perfChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Pages table -->
            <div class="card position-relative">
                <div id="loading-table" class="loading-overlay">
                    <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm display align-middle" id="pages-table">
                            <thead class="table-primary">
                                <tr>
                                    <th>#</th>
                                    <th>Main Page</th>
                                    <th class="text-end"><i class="far fa-mouse-pointer me-1"></i>Number of clicks</th>
                                    <th class="text-end">Impressions</th>
                                    <th class="text-end">CTR</th>
                                </tr>
                            </thead>
                            <tbody id="pages-tbody">
                                <!-- Rows will be injected here via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- More Range Modal -->
    <div class="modal fade" id="moreRangeModal" tabindex="-1" aria-labelledby="moreRangeLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="moreRangeLabel">More Date Ranges</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="list-group">
                        <label class="pt-2 border-bottom pb-3">
                            <input class="form-check-input me-1" type="radio" name="moreRange" value="6m"> Last 6
                            months
                        </label>
                        <label class="pt-2 border-bottom pb-3">
                            <input class="form-check-input me-1" type="radio" name="moreRange" value="12m"> Last 12
                            months
                        </label>
                        <label class="pt-2 border-bottom pb-3">
                            <input class="form-check-input me-1" type="radio" name="moreRange" value="18m"> Last 18
                            months
                        </label>
                        <label class="pt-2 border-bottom pb-3">
                            <input class="form-check-input me-1" type="radio" name="moreRange" value="24m"> Last 24
                            months
                        </label>
                        <label class="py-2">
                            <input class="form-check-input me-1" type="radio" name="moreRange" value="custom">
                            <span>Custom date</span>
                            <div class="d-flex align-items-center gap-2 mt-3">
                                <input type="date" class="form-control form-control-sm" id="customStart">
                                <span class="text-muted">to</span>
                                <input type="date" class="form-control form-control-sm" id="customEnd">
                            </div>
                        </label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button id="applyMoreRange" class="btn btn-primary">Apply</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>

    <script>
        (function() {
            // ---------- Cookie helpers ----------
            function setCookie(name, value, days = 90) {
                const d = new Date();
                d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
                document.cookie = name + "=" + encodeURIComponent(value) + ";expires=" + d.toUTCString() + ";path=/";
            }

            function getCookie(name) {
                const cname = name + "=";
                const ca = document.cookie.split(';');
                for (let i = 0; i < ca.length; i++) {
                    let c = ca[i].trim();
                    if (c.indexOf(cname) === 0) return decodeURIComponent(c.substring(cname.length, c.length));
                }
                return null;
            }

            function deleteCookie(name) {
                document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/";
            }

            const COOKIE_DOMAIN_ID = "adv_last_domain_id";
            const COOKIE_DOMAIN_TEXT = "adv_last_domain_text";

            // ---------- DataTable ----------
            const table = $('#pages-table').DataTable({
                paging: true,
                searching: false,
                ordering: true,
                info: true,
                lengthChange: true,
                pageLength: 10,
                responsive: true,
                language: {
                    paginate: {
                        previous: '<i class="fas fa-angle-double-left"></i>',
                        next: '<i class="fas fa-angle-double-right"></i>'
                    }
                },
                columnDefs: [{
                    targets: 0,
                    orderable: false
                }]
            });

            table.on('order.dt search.dt draw.dt', function() {
                let i = 1;
                table
                    .cells(null, 0, {
                        search: 'applied',
                        order: 'applied'
                    })
                    .every(function() {
                        this.data(i++);
                    });
            });

            function paintTables(pagesMaybePaginator) {
                const rows = Array.isArray(pagesMaybePaginator) ?
                    pagesMaybePaginator :
                    (pagesMaybePaginator && Array.isArray(pagesMaybePaginator.data) ? pagesMaybePaginator.data : []);

                table.clear();

                rows.forEach((x, idx) => {
                    const clicks = Number(x.clicks || 0);
                    const imps = Number(x.impressions || 0);
                    const ctr = imps ? (clicks / imps) * 100 : 0;

                    table.row.add([
                        idx + 1,
                        `<span class="text-truncate" style="max-width:420px;display:inline-block">${x.page || x.q || '-'}</span>`,
                        `<p class="text-end mb-0">${clicks.toLocaleString()}</p>`,
                        `<p class="text-end mb-0">${imps.toLocaleString()}</p>`,
                        `<p class="text-end mb-0">${ctr.toFixed(2)}%</p>`
                    ]);
                });

                table.draw(false);
            }

            // ---------- Bootstrap tooltips ----------
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(el => new bootstrap.Tooltip(el));

            // ---------- Select2 (Domain search) ----------
            $('#domain-select').select2({
                placeholder: 'Search for Domain…',
                allowClear: true,
                ajax: {
                    url: "{{ route('domain.domain-list') }}",
                    dataType: 'json',
                    delay: 250,
                    data: p => ({
                        q: p.term || ''
                    }),
                    processResults: r => ({
                        results: (r.data || []).map(i => ({
                            id: i.id,
                            text: i.text
                        }))
                    }),
                    cache: true
                },
                templateResult: d => d.loading ? d.text : $(
                    `<span><i class="fal fa-globe me-1"></i>${d.text}</span>`),
                escapeMarkup: m => m
            });

            // ---------- Chart.js (Clicks / Impressions / CTR) ----------
            const ctx = document.getElementById('perfChart').getContext('2d');
            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                            label: 'Clicks',
                            data: [],
                            borderWidth: 2,
                            tension: 0.3,
                            pointRadius: 0,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Impressions',
                            data: [],
                            borderWidth: 2,
                            tension: 0.3,
                            pointRadius: 0,
                            yAxisID: 'y'
                        },
                        {
                            label: 'CTR',
                            data: [],
                            borderWidth: 2,
                            tension: 0.3,
                            pointRadius: 0,
                            hidden: true,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            display: true
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false
                            },
                            ticks: {
                                callback: (v) => v + '%'
                            }
                        }
                    }
                }
            });

            // ---------- Helpers ----------
            const $kClicks = document.getElementById('kpi-clicks');
            const $kImps = document.getElementById('kpi-impressions');
            const $kCtr = document.getElementById('kpi-ctr');
            const $rangeLb = document.getElementById('range-label');

            const prettyRange = {
                '24h': 'Last 24 hours',
                '7d': 'Last 7 days',
                '28d': 'Last 28 days',
                '3m': 'Last 3 months'
            };

            function pct(c, i) {
                return i ? (c / i * 100) : 0;
            }

            function setKpis({
                clicks = 0,
                impressions = 0,
                ctr = null
            }) {
                const ctrVal = (ctr === null || ctr === undefined) ? pct(clicks, impressions) : ctr;
                $kClicks.textContent = Number(clicks).toLocaleString();
                $kImps.textContent = Number(impressions).toLocaleString();
                $kCtr.textContent = `${Number(ctrVal).toFixed(2)}%`;
            }

            function computeCtrSeries(clicks = [], impressions = []) {
                const out = [];
                const n = Math.max(clicks.length, impressions.length);
                for (let i = 0; i < n; i++) {
                    const c = Number(clicks[i] || 0);
                    const im = Number(impressions[i] || 0);
                    out.push(im ? (c / im * 100) : 0);
                }
                return out;
            }

            function updateChart(labels, clicks, impressions) {
                const ctrSeries = computeCtrSeries(clicks, impressions);
                chart.data.labels = labels || [];
                chart.data.datasets[0].data = clicks || [];
                chart.data.datasets[1].data = impressions || [];
                chart.data.datasets[2].data = ctrSeries;
                chart.update();
            }

            function timeAgoFrom(iso) {
                if (!iso) return '';
                const now = new Date();
                const t = new Date(iso);
                const ms = Math.max(0, now - t);
                const mins = ms / 60000;
                if (mins < 1.5) return 'Last updated: just now';
                if (mins < 120) return `Last updated: ${Math.round(mins)} minutes ago`;
                const hours = mins / 60;
                if (hours < 24) return `Last updated: ${hours.toFixed(1)} hours ago`;
                const days = hours / 24;
                return `Last updated: ${days.toFixed(1)} days ago`;
            }

            // ---------- Loading overlays ----------
            const showLoaders = (on) => {
                document.getElementById('loading-chart').classList.toggle('show', on);
                document.getElementById('loading-table').classList.toggle('show', on);
            };

            // ---------- API / state ----------
            const METRICS_URL = "{{ route('advance-analytics.fetch') }}"; // backend endpoint
            let activeRange = '24h'; // default; will be used on restore
            let moreRange = null;

            function buildQuery(domainId) {
                const q = {
                    domain: Number(domainId)
                };
                if (['24h', '7d', '28d', '3m'].includes(activeRange)) {
                    q.range = activeRange;
                } else if (moreRange) {
                    q.range = 'more';
                    if (['6m', '12m', '18m', '24m'].includes(moreRange.type)) {
                        q.months = parseInt(moreRange.type, 10);
                    } else if (moreRange.type === 'custom') {
                        q.start = moreRange.start;
                        q.end = moreRange.end;
                    }
                }
                return q;
            }

            function prettyRangeLabel() {
                return prettyRange[activeRange] || (
                    moreRange ?
                    (['6m', '12m', '18m', '24m'].includes(moreRange.type) ?
                        `Last ${parseInt(moreRange.type,10)} months` :
                        `${moreRange.start || '…'} to ${moreRange.end || '…'}`) :
                    ''
                );
            }

            function debounce(fn, ms = 400) {
                let t;
                return (...args) => {
                    clearTimeout(t);
                    t = setTimeout(() => fn(...args), ms);
                };
            }

            const loadPerformance = debounce(async function() {
                const domain = $('#domain-select').val(); // this is the **ID**
                if (!domain) {
                    setKpis({
                        clicks: 0,
                        impressions: 0,
                        ctr: 0
                    });
                    updateChart([], [], []);
                    paintTables([]);
                    $rangeLb.textContent = prettyRangeLabel();
                    return;
                }

                $rangeLb.textContent = prettyRangeLabel();
                showLoaders(true);

                try {
                    const params = buildQuery(domain);
                    const res = await $.ajax({
                        url: METRICS_URL,
                        data: params,
                        method: 'GET',
                        cache: false,
                        timeout: 20000
                    });

                    const {
                        kpis = {}, series = {}, tables = {}, meta = {}
                    } = res || {};
                    setKpis({
                        clicks: kpis.clicks,
                        impressions: kpis.impressions,
                        ctr: kpis.ctr
                    });
                    updateChart(series.labels || [], series.clicks || [], series.impressions || []);
                    paintTables(tables.pages);

                    const rel = timeAgoFrom(meta.cached_at);
                    const base = prettyRangeLabel();
                    $rangeLb.textContent = base ? `${base} • ${rel}` : rel;
                } catch (e) {
                    console.error('Analytics fetch failed:', e);
                    setKpis({
                        clicks: 0,
                        impressions: 0,
                        ctr: 0
                    });
                    updateChart([], [], []);
                    paintTables([]);
                } finally {
                    showLoaders(false);
                }
            }, 250);

            // ---------- Events ----------
            // On domain change, save cookies and load
            $('#domain-select').on('change', function() {
                const val = $(this).val();
                const data = $('#domain-select').select2('data');
                if (val) {
                    const label = data && data[0] ? (data[0].text || '') : '';
                    setCookie(COOKIE_DOMAIN_ID, String(val));
                    setCookie(COOKIE_DOMAIN_TEXT, label);
                } else {
                    deleteCookie(COOKIE_DOMAIN_ID);
                    deleteCookie(COOKIE_DOMAIN_TEXT);
                }
                loadPerformance();
            });

            // Range buttons
            document.querySelectorAll('.perf-range').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.perf-range').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    activeRange = this.dataset.range; // 24h,7d,28d,3m
                    moreRange = null; // clear “more”
                    loadPerformance();
                });
            });

            // KPI toggles
            document.querySelectorAll('.kpi-toggle').forEach(chk => {
                chk.addEventListener('change', function() {
                    const id = this.dataset.kpi;
                    if (id === 'clicks') chart.setDatasetVisibility(0, this.checked);
                    if (id === 'impressions') chart.setDatasetVisibility(1, this.checked);
                    if (id === 'ctr') chart.setDatasetVisibility(2, this.checked);
                    chart.update();
                });

                const id = chk.dataset.kpi;
                if (id === 'clicks') chart.setDatasetVisibility(0, chk.checked);
                if (id === 'impressions') chart.setDatasetVisibility(1, chk.checked);
                if (id === 'ctr') chart.setDatasetVisibility(2, chk.checked);
            });

            // More modal apply
            $('#applyMoreRange').on('click', function() {
                const val = $('input[name="moreRange"]:checked').val();
                if (!val) return;

                if (val === 'custom') {
                    const start = $('#customStart').val();
                    const end = $('#customEnd').val();
                    moreRange = {
                        type: 'custom',
                        start,
                        end
                    };
                } else {
                    moreRange = {
                        type: val
                    }; // 6m / 12m / 18m / 24m
                }

                activeRange = 'more';
                $('#moreRangeModal').modal('hide');
                document.querySelectorAll('.perf-range').forEach(b => b.classList.remove('active'));
                loadPerformance();
            });

            // ---------- Restore last selected domain on page load ----------
            (function restoreLastDomain() {
                const savedId = getCookie(COOKIE_DOMAIN_ID);
                const savedText = getCookie(COOKIE_DOMAIN_TEXT);
                if (!savedId) return;

                // Insert a preselected option so Select2 shows it without needing an AJAX search
                const option = new Option(savedText || savedId, savedId, true, true);
                $('#domain-select').append(option).trigger('change'); // triggers loadPerformance via change handler

                // Ensure range is 24h on load as requested (buttons UI)
                document.querySelectorAll('.perf-range').forEach(b => b.classList.remove('active'));
                document.querySelector('.perf-range[data-range="24h"]').classList.add('active');
                activeRange = '24h';
            })();
        })();
    </script>
@endpush

{{-- @push('scripts')
    <script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>

    <script>
        (function() {
            // ---------- DataTable (with row numbering in column 0) ----------
            const table = $('#pages-table').DataTable({
                paging: true,
                searching: true,
                ordering: true,
                info: true,
                lengthChange: true,
                pageLength: 10,
                responsive: true,
                language: {
                    paginate: {
                        previous: '<i class="fas fa-angle-double-left"></i>',
                        next: '<i class="fas fa-angle-double-right"></i>'
                    }
                },
                columnDefs: [{
                    targets: 0,
                    orderable: false
                }]
            });

            // Renumber the "#" column after any order/search/draw
            table.on('order.dt search.dt draw.dt', function() {
                let i = 1;
                table
                    .cells(null, 0, {
                        search: 'applied',
                        order: 'applied'
                    })
                    .every(function() {
                        this.data(i++);
                    });
            });

            // Paint pages table using DataTables API. Accepts paginator or plain array.
            function paintTables(pagesMaybePaginator) {
                const rows = Array.isArray(pagesMaybePaginator) ?
                    pagesMaybePaginator :
                    (pagesMaybePaginator && Array.isArray(pagesMaybePaginator.data) ?
                        pagesMaybePaginator.data :
                        []);

                table.clear();

                rows.forEach((x, idx) => {
                    const clicks = Number(x.clicks || 0);
                    const imps = Number(x.impressions || 0);
                    const ctr = imps ? (clicks / imps) * 100 : 0;

                    table.row.add([
                        idx + 1,
                        `<span class="text-truncate" style="max-width:420px;display:inline-block">${x.page || x.q || '-'}</span>`,
                        `<p class="text-end mb-0">${clicks.toLocaleString()}</p>`,
                        `<p class="text-end mb-0">${imps.toLocaleString()}</p>`,
                        `<p class="text-end mb-0">${ctr.toFixed(2)}%</p>`
                    ]);
                });

                table.draw(false);
            }

            // ---------- Bootstrap tooltips ----------
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(el => new bootstrap.Tooltip(el));

            // ---------- Select2 (Domain search) ----------
            $('#domain-select').select2({
                placeholder: 'Search for Domain…',
                allowClear: true,
                ajax: {
                    url: "{{ route('domain.domain-list') }}",
                    dataType: 'json',
                    delay: 250,
                    data: p => ({
                        q: p.term || ''
                    }),
                    processResults: r => ({
                        results: (r.data || []).map(i => ({
                            id: i.id,
                            text: i.text
                        }))
                    }),
                    cache: true
                },
                templateResult: d => d.loading ? d.text : $(
                    `<span><i class="fal fa-globe me-1"></i>${d.text}</span>`),
                escapeMarkup: m => m
            });

            // ---------- Chart.js (Clicks / Impressions / CTR) ----------
            const ctx = document.getElementById('perfChart').getContext('2d');
            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                            label: 'Clicks',
                            data: [],
                            borderWidth: 2,
                            tension: 0.3,
                            pointRadius: 0,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Impressions',
                            data: [],
                            borderWidth: 2,
                            tension: 0.3,
                            pointRadius: 0,
                            yAxisID: 'y'
                        },
                        {
                            label: 'CTR',
                            data: [],
                            borderWidth: 2,
                            tension: 0.3,
                            pointRadius: 0,
                            hidden: true,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            display: true
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false
                            },
                            ticks: {
                                callback: (v) => v + '%'
                            }
                        }
                    }
                }
            });

            // ---------- Helpers ----------
            const $kClicks = document.getElementById('kpi-clicks');
            const $kImps = document.getElementById('kpi-impressions');
            const $kCtr = document.getElementById('kpi-ctr');
            const $rangeLb = document.getElementById('range-label');

            const prettyRange = {
                '24h': 'Last 24 hours',
                '7d': 'Last 7 days',
                '28d': 'Last 28 days',
                '3m': 'Last 3 months'
            };

            function pct(c, i) {
                return i ? (c / i * 100) : 0;
            }

            function setKpis({
                clicks = 0,
                impressions = 0,
                ctr = null
            }) {
                const ctrVal = (ctr === null || ctr === undefined) ? pct(clicks, impressions) : ctr;
                $kClicks.textContent = Number(clicks).toLocaleString();
                $kImps.textContent = Number(impressions).toLocaleString();
                $kCtr.textContent = `${Number(ctrVal).toFixed(2)}%`;
            }

            function computeCtrSeries(clicks = [], impressions = []) {
                const out = [];
                const n = Math.max(clicks.length, impressions.length);
                for (let i = 0; i < n; i++) {
                    const c = Number(clicks[i] || 0);
                    const im = Number(impressions[i] || 0);
                    out.push(im ? (c / im * 100) : 0);
                }
                return out;
            }

            function updateChart(labels, clicks, impressions) {
                const ctrSeries = computeCtrSeries(clicks, impressions);
                chart.data.labels = labels || [];
                chart.data.datasets[0].data = clicks || [];
                chart.data.datasets[1].data = impressions || [];
                chart.data.datasets[2].data = ctrSeries;
                chart.update();
            }

            // Relative "Last updated: X ago"
            function timeAgoFrom(iso) {
                if (!iso) return '';
                const now = new Date();
                const t = new Date(iso);
                const ms = Math.max(0, now - t);
                const mins = ms / 60000;
                if (mins < 1.5) return 'Last updated: just now';
                if (mins < 120) return `Last updated: ${Math.round(mins)} minutes ago`;
                const hours = mins / 60;
                if (hours < 24) return `Last updated: ${hours.toFixed(1)} hours ago`;
                const days = hours / 24;
                return `Last updated: ${days.toFixed(1)} days ago`;
            }

            // ---------- Loading overlays ----------
            const showLoaders = (on) => {
                document.getElementById('loading-chart').classList.toggle('show', on);
                document.getElementById('loading-table').classList.toggle('show', on);
            };

            // ---------- API / state ----------
            const METRICS_URL = "{{ route('advance-analytics.fetch') }}"; // backend endpoint
            let activeRange = '24h';
            let moreRange = null; // {type:'6m'|'12m'|'18m'|'24m'|'custom', start:'YYYY-MM-DD', end:'YYYY-MM-DD'}

            function buildQuery(domainId) {
                const q = {
                    domain: Number(domainId)
                };
                if (['24h', '7d', '28d', '3m'].includes(activeRange)) {
                    q.range = activeRange;
                } else if (moreRange) {
                    q.range = 'more';
                    if (['6m', '12m', '18m', '24m'].includes(moreRange.type)) {
                        q.months = parseInt(moreRange.type, 10);
                    } else if (moreRange.type === 'custom') {
                        q.start = moreRange.start; // YYYY-MM-DD
                        q.end = moreRange.end; // YYYY-MM-DD
                    }
                }
                return q;
            }

            function prettyRangeLabel() {
                return prettyRange[activeRange] || (
                    moreRange ?
                    (['6m', '12m', '18m', '24m'].includes(moreRange.type) ?
                        `Last ${parseInt(moreRange.type,10)} months` :
                        `${moreRange.start || '…'} to ${moreRange.end || '…'}`) :
                    ''
                );
            }

            // Debounce
            function debounce(fn, ms = 400) {
                let t;
                return (...args) => {
                    clearTimeout(t);
                    t = setTimeout(() => fn(...args), ms);
                };
            }

            const loadPerformance = debounce(async function() {
                const domain = $('#domain-select').val(); // this is the **ID**
                if (!domain) {
                    // Reset UI when no domain is selected
                    setKpis({
                        clicks: 0,
                        impressions: 0,
                        ctr: 0
                    });
                    updateChart([], [], []);
                    paintTables([]); // empty table
                    $rangeLb.textContent = prettyRangeLabel();
                    return;
                }

                $rangeLb.textContent = prettyRangeLabel();
                showLoaders(true);

                try {
                    const params = buildQuery(domain);
                    const res = await $.ajax({
                        url: METRICS_URL,
                        data: params,
                        method: 'GET',
                        cache: false,
                        timeout: 20000
                    });

                    const {
                        kpis = {}, series = {}, tables = {}, meta = {}
                    } = res || {};
                    setKpis({
                        clicks: kpis.clicks,
                        impressions: kpis.impressions,
                        ctr: kpis.ctr
                    });
                    updateChart(series.labels || [], series.clicks || [], series.impressions || []);
                    paintTables(tables.pages); // pass paginator/array directly

                    // Range + relative "Last updated"
                    const rel = timeAgoFrom(meta.cached_at);
                    const base = prettyRangeLabel();
                    $rangeLb.textContent = base ? `${base} • ${rel}` : rel;
                } catch (e) {
                    console.error('Analytics fetch failed:', e);
                    setKpis({
                        clicks: 0,
                        impressions: 0,
                        ctr: 0
                    });
                    updateChart([], [], []);
                    paintTables([]); // empty on error
                } finally {
                    showLoaders(false);
                }
            }, 250);

            // ---------- Events ----------
            // domain change → load
            $('#domain-select').on('change', loadPerformance);

            // range buttons
            document.querySelectorAll('.perf-range').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.perf-range').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    activeRange = this.dataset.range; // 24h,7d,28d,3m
                    moreRange = null; // clear “more”
                    loadPerformance();
                });
            });

            // KPI toggles (dataset visibility)
            document.querySelectorAll('.kpi-toggle').forEach(chk => {
                chk.addEventListener('change', function() {
                    const id = this.dataset.kpi;
                    if (id === 'clicks') chart.setDatasetVisibility(0, this.checked);
                    if (id === 'impressions') chart.setDatasetVisibility(1, this.checked);
                    if (id === 'ctr') chart.setDatasetVisibility(2, this.checked);
                    chart.update();
                });

                // Initialize visibility to match default checkbox states
                const id = chk.dataset.kpi;
                if (id === 'clicks') chart.setDatasetVisibility(0, chk.checked);
                if (id === 'impressions') chart.setDatasetVisibility(1, chk.checked);
                if (id === 'ctr') chart.setDatasetVisibility(2, chk.checked);
            });

            // More modal apply
            $('#applyMoreRange').on('click', function() {
                const val = $('input[name="moreRange"]:checked').val();
                if (!val) return;

                if (val === 'custom') {
                    const start = $('#customStart').val();
                    const end = $('#customEnd').val();
                    moreRange = {
                        type: 'custom',
                        start,
                        end
                    };
                } else {
                    moreRange = {
                        type: val
                    }; // 6m / 12m / 18m / 24m
                }

                activeRange = 'more';
                $('#moreRangeModal').modal('hide');
                document.querySelectorAll('.perf-range').forEach(b => b.classList.remove('active'));
                loadPerformance();
            });
        })();
    </script>
@endpush --}}
