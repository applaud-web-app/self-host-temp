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

        .loading-overlay {
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, .6);
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
        .highcharts-title{
          display:none;
        }
    </style>
@endpush

@section('content')
    <section class="content-body">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between text-head mb-3">
                <h2 class="me-auto mb-0 text-uppercase">Subscriber Analytics</h2>
            </div>

            <!-- Filters -->
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 mt-4">
                <div class="d-flex align-items-center gap-2" style="min-width:300px">
                    <select id="domain-select" class="form-control">
                        <option value="">Select Domain</option>
                    </select>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="btn-group" role="group" aria-label="Date ranges">
                        <button type="button" class="btn btn-outline-primary perf-range active py-2 px-3" data-range="24h">last 24 hours</button>
                        <button type="button" class="btn btn-outline-primary perf-range py-2 px-3" data-range="7d">seven days</button>
                        <button type="button" class="btn btn-outline-primary perf-range py-2 px-3" data-range="28d">28 days</button>
                        <button type="button" class="btn btn-outline-primary perf-range py-2 px-3" data-range="3m">three months</button>
                    </div>
                    <button type="button" class="btn btn-outline-secondary py-2 px-3" data-bs-toggle="modal" data-bs-target="#moreRangeModal">More +</button>
                </div>
            </div>

            <!-- KPIs (3 only) -->
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-4">
                    <div class="card h-100 position-relative kpi-card" style="border-left:4px solid #4e5cf8;">
                        <div class="card-body py-3">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="text-muted small">All subscribers (selected period)</div>
                                    <div id="kpi-all" class="display-6 fw-semibold mt-1">0</div>
                                </div>
                                <label class="form-check ms-2">
                                    <input id="chk-all" class="form-check-input" type="checkbox" checked>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card h-100 position-relative kpi-card" style="border-left:4px solid #00c49a;">
                        <div class="card-body py-3">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="text-muted small">Active (selected period)</div>
                                    <div id="kpi-active" class="display-6 fw-semibold mt-1">0</div>
                                </div>
                                <label class="form-check ms-2">
                                    <input id="chk-active" class="form-check-input" type="checkbox" checked>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card h-100 position-relative kpi-card" style="border-left:4px solid #f59f00;">
                        <div class="card-body py-3">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="text-muted small">Inactive (selected period)</div>
                                    <div id="kpi-inactive" class="display-6 fw-semibold mt-1">0</div>
                                </div>
                                <label class="form-check ms-2">
                                    <input id="chk-inactive" class="form-check-input" type="checkbox" checked>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Growth -->
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-12">
                    <div class="card h-100 position-relative">
                        <div class="loading-overlay" id="chart-loader">
                            <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                        </div>
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <small class="text-muted">Subscribers over time</small>
                                <small id="range-label" class="text-muted">Last 24 hours</small>
                            </div>
                            <div style="height:300px"><canvas id="subsChart"></canvas></div>
                            <small class="text-muted d-block mt-2">Growth vs previous window: <span id="growth-pct">–</span></small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Donuts -->
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <small class="text-muted">Device share</small>
                            </div>
                            <div style="height:250px"><canvas id="deviceDonut"></canvas></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <small class="text-muted">Browser share</small>
                            </div>
                            <div style="height:250px"><canvas id="browserDonut"></canvas></div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="tab-content">
                <!-- Tables -->
                <ul class="nav nav-tabs" role="tablist">
                  <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-country" type="button" role="tab">Country</button></li>
                  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-state" type="button" role="tab">State</button></li>
                  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-city" type="button" role="tab">City</button></li>
                  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-url" type="button" role="tab">URL</button></li>
                  <li class="ms-auto"><small id="last-updated" class="text-muted d-inline-flex align-items-center px-2">—</small></li>
                </ul>
                <div class="tab-pane fade show active" id="tab-country">
                    <div class="card">
                        <div class="card-body">
                            <table class="table table-sm display align-middle" id="country-table">
                                <thead class="table-primary">
                                    <tr>
                                        <th>#</th>
                                        <th>Country</th>
                                        <th class="text-end">Subscribers</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-state">
                    <div class="card">
                        <div class="card-body">
                            <table class="table table-sm display align-middle" id="state-table">
                                <thead class="table-primary">
                                    <tr>
                                        <th>#</th>
                                        <th>Country</th>
                                        <th>State</th>
                                        <th class="text-end">Subscribers</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-city">
                    <div class="card">
                        <div class="card-body">
                            <table class="table table-sm display align-middle" id="city-table">
                                <thead class="table-primary">
                                    <tr>
                                        <th>#</th>
                                        <th>Country</th>
                                        <th>State</th>
                                        <th>City</th>
                                        <th class="text-end">Subscribers</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-url">
                    <div class="card">
                        <div class="card-body">
                            <table class="table table-sm display align-middle" id="url-table">
                                <thead class="table-primary">
                                    <tr>
                                        <th>#</th>
                                        <th>Subscribed URL</th>
                                        <th class="text-end">Subscribers</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add this new section where you want the map to appear, e.g., after the Donuts -->
            <div class="row g-3 mb-3">
                <div class="col-12">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <small class="text-muted">Subscribers by Country</small>
                                <small class="text-muted">Map Source : <b>Highcharts</b> <span class="text-danger">*</span></small>
                            </div>
                            <div id="subsMap" style="height:500px"></div>
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </section>

    <!-- More Range Modal (same UX as your existing) -->
    <div class="modal fade" id="moreRangeModal" tabindex="-1" aria-labelledby="moreRangeLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="moreRangeLabel">More Date Ranges</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="list-group">
                    <label class="pt-2 border-bottom pb-3"><input class="form-check-input me-1" type="radio" name="moreRange" value="6m"> Last 6 months</label>
                    <label class="pt-2 border-bottom pb-3"><input class="form-check-input me-1" type="radio" name="moreRange" value="12m"> Last 12 months</label>
                    <label class="pt-2 border-bottom pb-3"><input class="form-check-input me-1" type="radio" name="moreRange" value="18m"> Last 18 months</label>
                    <label class="pt-2 border-bottom pb-3"><input class="form-check-input me-1" type="radio" name="moreRange" value="24m"> Last 24 months</label>
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
        </div></div>
    </div>
@endsection
@push('scripts')
    {{-- Vendor libs (assumes jQuery/DataTables already loaded in your master) --}}
    <script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    {{-- Highcharts Maps (load ONCE) --}}
    <script src="https://code.highcharts.com/maps/highmaps.js" defer></script>
    <script src="https://code.highcharts.com/modules/accessibility.js" defer></script>

    <script defer>
    // =========================
    // 1) MAP MODULE (fixed)
    // =========================
    (function () {
      // ---- graceful hide helpers ----
      const mapEl = () => document.getElementById("subsMap");
      const mapCard = () => mapEl()?.closest(".card");
      function hideMapCard() { const c = mapCard(); if (c) c.classList.add("d-none"); }
      function showMapCard() { const c = mapCard(); if (c) c.classList.remove("d-none"); }

      // Hide by default until we have data
      document.addEventListener("DOMContentLoaded", () => hideMapCard(), { once: true });

      // ---- tiny cache for ISO3 per country (7 days) ----
      const ISO_CACHE_KEY = "iso3_by_country_v2";
      const ISO_CACHE_TTL = 7 * 24 * 60 * 60 * 1000;
      function readIsoCache() {
        try {
          const raw = JSON.parse(localStorage.getItem(ISO_CACHE_KEY));
          if (!raw) return {};
          if (Date.now() - raw.t > ISO_CACHE_TTL) return {};
          return raw.v || {};
        } catch { return {}; }
      }
      function writeIsoCache(map) {
        localStorage.setItem(ISO_CACHE_KEY, JSON.stringify({ t: Date.now(), v: map }));
      }

      // ---- API resolvers (no hard-coded aliases) ----
      async function iso3FromCountriesNow(country) {
        try {
          const res = await fetch("https://countriesnow.space/api/v0.1/countries/iso", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ country })
          });
          if (!res.ok) return null;
          const j = await res.json();
          const code = j?.data?.Iso3 || j?.data?.iso3;
          return (typeof code === "string" && code.length === 3) ? code.toUpperCase() : null;
        } catch { return null; }
      }
      async function iso3FromRestCountries(country) {
        try {
          const res = await fetch("https://restcountries.com/v3.1/name/" + encodeURIComponent(country) + "?fields=cca3");
          if (!res.ok) return null;
          const arr = await res.json();
          const cca3 = Array.isArray(arr) && arr[0]?.cca3;
          return (typeof cca3 === "string" && cca3.length === 3) ? cca3.toUpperCase() : null;
        } catch { return null; }
      }
      async function resolveIso3(country) {
        let code = await iso3FromCountriesNow(country);
        if (!code) code = await iso3FromRestCountries(country);
        return code;
      }
      async function getIso3For(countries) {
        const cache = readIsoCache();
        const unique = [...new Set((countries || []).filter(Boolean))];
        const missing = unique.filter(c => !cache[c]);

        if (missing.length) {
          const settled = await Promise.allSettled(
            missing.map(async c => [c, await resolveIso3(c)])
          );
          for (const r of settled) {
            if (r.status === "fulfilled") {
              const [name, iso3] = r.value;
              if (iso3) cache[name] = iso3;
            }
          }
          writeIsoCache(cache);
        }
        return cache;
      }

      // ---- Highcharts map (init once, update many) ----
      let mapChart = null;
      let topology = null;

      async function ensureTopology() {
        if (topology) return topology;
        const res = await fetch("https://code.highcharts.com/mapdata/custom/world.topo.json");
        if (!res.ok) throw new Error("world topo fetch failed");
        topology = await res.json();
        return topology;
      }

      async function initMapIfNeeded() {
        if (mapChart || !mapEl()) return;
        await ensureTopology();
        mapChart = Highcharts.mapChart("subsMap", {
          chart: { map: topology },
          credits: { enabled: true, href: "#", text: "Subscribers by Country" },
          mapNavigation: { enabled: true, buttonOptions: { verticalAlign: "bottom" } },
          colorAxis: { min: 0 },
          tooltip: { pointFormat: "{point.name}: <b>{point.value}</b>", valueSuffix: " subs" },
          series: [{
            name: "Subscribers",
            keys: ["code", "value"],
            data: [],
            joinBy: ["iso-a3", "code"],
            states: { hover: { enabled: true } },
            dataLabels: {
              enabled: true,
              format: "{point.value:.0f}",
              filter: { operator: ">", property: "labelrank", value: 250 },
              style: { fontWeight: "normal" }
            }
          }]
        });
      }

      function toSeriesData(rows, isoMap) {
        return (rows || [])
          .map(r => [ isoMap[r.country], Number(r.subs || 0) ])
          .filter(([code, val]) => code && Number.isFinite(val));
      }

      async function updateMap(rows) {
        try {
          if (!rows || !rows.length) { hideMapCard(); return; }
          await initMapIfNeeded();
          if (!mapChart) { hideMapCard(); return; }

          const names = rows.map(r => r.country).filter(Boolean);
          const isoMap = await getIso3For(names);
          const seriesData = toSeriesData(rows, isoMap);
          if (!seriesData.length) { hideMapCard(); return; }

          mapChart.series?.[0]?.setData(seriesData, true, { duration: 250 });
          showMapCard();
        } catch {
          hideMapCard();
        }
      }

      // Expose direct hook (optional)
      window.updateSubsMap = updateMap;

      // CRITICAL: register listener immediately (prevents race with dispatch)
      window.addEventListener("subsCountryData", (ev) => {
        updateMap(ev.detail?.rows || []);
      }, { passive: true });
    })();
    </script>

    <script defer>
    // =========================
    // 2) ANALYTICS + UI MODULE
    // =========================
    (function() {
      // --------- Cookie helpers ----------
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
              if (c.indexOf(cname) === 0) return decodeURIComponent(c.substring(cname.length));
          }
          return null;
      }
      function deleteCookie(name) {
          document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/";
      }
      const COOKIE_DOMAIN_ID = "adv_last_domain_id";
      const COOKIE_DOMAIN_TEXT = "adv_last_domain_text";

      // --------- Select2 Domain ----------
      $('#domain-select').select2({
          placeholder: 'Search for Domain…',
          allowClear: true,
          ajax: {
              url: "{{ route('domain.domain-list') }}",
              dataType: 'json',
              delay: 250,
              data: p => ({ q: p.term || '' }),
              processResults: r => ({
                  results: (r.data || []).map(i => ({ id: i.id, text: i.text }))
              }),
              cache: true
          },
          templateResult: d => d.loading ? d.text : $(`<span><i class="fal fa-globe me-1"></i>${d.text}</span>`),
          escapeMarkup: m => m
      });

      // --------- DataTables ----------
      function makeDT(selector) {
          const dt = $(selector).DataTable({
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
              columnDefs: [{ targets: 0, orderable: false }]
          });
          dt.on('order.dt search.dt draw.dt', function() {
              let i = 1;
              dt.cells(null, 0, { search: 'applied', order: 'applied' }).every(function() {
                  this.data(i++);
              });
          });
          return dt;
      }
      const dtCountry = makeDT('#country-table');
      const dtState = makeDT('#state-table');
      const dtCity = makeDT('#city-table');
      const dtUrl = makeDT('#url-table');

      function fillDT(dt, rows, build) {
          dt.clear();
          (rows || []).forEach((r, idx) => dt.row.add(build(r, idx)));
          dt.draw(false);
      }

      // --------- Chart (3 datasets) ----------
      const subsCtx = document.getElementById('subsChart').getContext('2d');
      const subsChart = new Chart(subsCtx, {
          type: 'line',
          data: {
              labels: [],
              datasets: [
                { label: 'All',      data: [], borderWidth: 2, tension: .3, pointRadius: 0,
                  borderColor: "#4e5cf8", backgroundColor: "rgba(78, 92, 248, 0.2)" },
                { label: 'Active',   data: [], borderWidth: 2, tension: .3, pointRadius: 0,
                  borderColor: "#00c49a", backgroundColor: "rgba(0, 196, 154, 0.2)" },
                { label: 'Inactive', data: [], borderWidth: 2, tension: .3, pointRadius: 0,
                  borderColor: "#f59f00", backgroundColor: "rgba(245, 159, 0, 0.2)" }
              ]
          },
          options: {
              responsive: true,
              maintainAspectRatio: false,
              interaction: { mode: 'index', intersect: false },
              plugins: { legend: { display: false }, decimation: { enabled: true, algorithm: 'min-max', samples: 1000 } },
              scales: {
                  x: { grid: { display: false } },
                  y: { beginAtZero: true, ticks: { precision: 0 } }
              }
          }
      });

      // --------- Donuts ----------
      const deviceDonut = new Chart(document.getElementById('deviceDonut').getContext('2d'), {
          type: 'doughnut',
          data: { labels: [], datasets: [{ data: [], backgroundColor: ["#3498db", "#e74c3c", "#2ecc71", "#9b59b6", "#f39c12"] }] },
          options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
      });
      const browserDonut = new Chart(document.getElementById('browserDonut').getContext('2d'), {
          type: 'doughnut',
          data: { labels: [], datasets: [{ data: [], backgroundColor: ["#4e5cf8", "#00c49a", "#f59f00", "#ff3b3b", "#ffd700"] }] },
          options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
      });

      // --------- Helpers ----------
      const $kAll = document.getElementById('kpi-all');
      const $kAct = document.getElementById('kpi-active');
      const $kIn  = document.getElementById('kpi-inactive');
      const $rangeLb = document.getElementById('range-label');
      const $lastUpdated = document.getElementById('last-updated');
      const $growthPct = document.getElementById('growth-pct');
      const $loader = document.getElementById('chart-loader');

      const chkAll = document.getElementById('chk-all');
      const chkActive = document.getElementById('chk-active');
      const chkInactive = document.getElementById('chk-inactive');

      const prettyRange = { '24h': 'Last 24 hours', '7d': 'Last 7 days', '28d': 'Last 28 days', '3m': 'Last 3 months' };
      let activeRange = '24h';
      let moreRange = null;

      function prettyRangeLabel() {
          return prettyRange[activeRange] || (
              moreRange ?
                (['6m','12m','18m','24m'].includes(moreRange.type) ? `Last ${parseInt(moreRange.type,10)} months`
                                                                  : `${moreRange.start || '…'} to ${moreRange.end || '…'}`) :
              ''
          );
      }

      const METRICS_URL = "{{ route('advance-analytics.subscribers.fetch') }}";

      function buildQuery(originText) {
          const q = { origin: originText };
          if (['24h','7d','28d','3m'].includes(activeRange)) {
              q.range = activeRange;
          } else if (moreRange) {
              q.range = 'more';
              if (['6m','12m','18m','24m'].includes(moreRange.type)) q.months = parseInt(moreRange.type, 10);
              else if (moreRange.type === 'custom') { q.start = moreRange.start; q.end = moreRange.end; }
          }
          return q;
      }

      function timeAgoFrom(iso) {
          if (!iso) return '';
          const now = new Date(), t = new Date(iso);
          const ms = Math.max(0, now - t), mins = ms / 60000;
          if (mins < 1.5) return 'Last updated: just now';
          if (mins < 120) return `Last updated: ${Math.round(mins)} minutes ago`;
          const hours = mins / 60;
          if (hours < 24) return `Last updated: ${hours.toFixed(1)} hours ago`;
          const days = hours / 24;
          return `Last updated: ${days.toFixed(1)} days ago`;
      }

      function topNPlusOther(rows, labelKey, valueKey, n = 6) {
          const top = (rows || []).slice(0, n);
          const rest = (rows || []).slice(n);
          const otherSum = rest.reduce((a, b) => a + Number(b[valueKey] || 0), 0);
          const labels = top.map(r => r[labelKey] || 'Unknown');
          const values = top.map(r => Number(r[valueKey] || 0));
          if (otherSum > 0) { labels.push('Other'); values.push(otherSum); }
          return { labels, values };
      }

      function updateDonut(chart, rows, labelKey, valueKey) {
          const { labels, values } = topNPlusOther(rows, labelKey, valueKey, 6);
          chart.data.labels = labels;
          chart.data.datasets[0].data = values;
          chart.update();
      }

      function setDatasetsVisibility() {
          subsChart.getDatasetMeta(0).hidden = !chkAll.checked;
          subsChart.getDatasetMeta(1).hidden = !chkActive.checked;
          subsChart.getDatasetMeta(2).hidden = !chkInactive.checked;
          subsChart.update();
      }
      chkAll.addEventListener('change', setDatasetsVisibility);
      chkActive.addEventListener('change', setDatasetsVisibility);
      chkInactive.addEventListener('change', setDatasetsVisibility);

      function showLoader(flag) {
          if ($loader) $loader.classList.toggle('show', !!flag);
      }

      function debounce(fn, ms = 400) {
          let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
      }

      const loadData = debounce(async function() {
          const data = $('#domain-select').select2('data');
          const originText = data && data[0] ? (data[0].text || '') : '';

          // Reset visuals if no domain yet
          if (!originText) {
              $kAll.textContent = $kAct.textContent = $kIn.textContent = '0';
              subsChart.data.labels = [];
              subsChart.data.datasets[0].data = [];
              subsChart.data.datasets[1].data = [];
              subsChart.data.datasets[2].data = [];
              subsChart.update();
              updateDonut(deviceDonut, [], 'device', 'subs');
              updateDonut(browserDonut, [], 'browser', 'subs');
              fillDT(dtCountry, [], (r, i) => [i + 1, '-', `<p class="text-end mb-0">0</p>`]);
              fillDT(dtState,   [], (r, i) => [i + 1, '-', '-', `<p class="text-end mb-0">0</p>`]);
              fillDT(dtCity,    [], (r, i) => [i + 1, '-', '-', '-', `<p class="text-end mb-0">0</p>`]);
              fillDT(dtUrl,     [], (r, i) => [i + 1, '-', `<p class="text-end mb-0">0</p>`]);
              $rangeLb.textContent = prettyRangeLabel();
              $lastUpdated.textContent = '—';
              $growthPct.textContent = '–';
              // also hide the map
              window.dispatchEvent(new CustomEvent("subsCountryData", { detail: { rows: [] }}));
              return;
          }

          $rangeLb.textContent = prettyRangeLabel();

          showLoader(true);
          try {
              const params = buildQuery(originText);
              const res = await $.ajax({
                  url: "{{ route('advance-analytics.subscribers.fetch') }}",
                  data: params, method: 'GET', cache: false, timeout: 20000
              });

              const { kpis = {}, series = {}, breakdowns = {}, meta = {} } = res || {};

              // KPIs
              $kAll.textContent = Number(kpis.all_in_window || 0).toLocaleString();
              $kAct.textContent = Number(kpis.active_in_window || 0).toLocaleString();
              $kIn.textContent  = Number(kpis.inactive_in_window || 0).toLocaleString();
              $growthPct.textContent = (kpis.growth_pct === null || kpis.growth_pct === undefined) ? '–'
                                        : `${Number(kpis.growth_pct).toFixed(2)}%`;

              // Chart
              subsChart.data.labels = series.labels || [];
              subsChart.data.datasets[0].data = series.all || [];
              subsChart.data.datasets[1].data = series.active || [];
              subsChart.data.datasets[2].data = series.inactive || [];
              setDatasetsVisibility();

              // Donuts
              updateDonut(deviceDonut, breakdowns.device || [], 'device', 'subs');
              updateDonut(browserDonut, breakdowns.browser || [], 'browser', 'subs');

              // Tables
              fillDT(dtCountry, breakdowns.country || [], (r, idx) => [
                idx + 1, r.country || 'Unknown', `<p class="text-end mb-0">${Number(r.subs||0).toLocaleString()}</p>`
              ]);
              fillDT(dtState, breakdowns.state || [], (r, idx) => [
                idx + 1, r.country || 'Unknown', r.state || 'Unknown', `<p class="text-end mb-0">${Number(r.subs||0).toLocaleString()}</p>`
              ]);
              fillDT(dtCity, breakdowns.city || [], (r, idx) => [
                idx + 1, r.country || 'Unknown', r.state || 'Unknown', r.city || 'Unknown',
                `<p class="text-end mb-0">${Number(r.subs||0).toLocaleString()}</p>`
              ]);
              fillDT(dtUrl, breakdowns.url || [], (r, idx) => [
                idx + 1,
                r.url ? `<span class="text-truncate d-inline-block" style="max-width:520px">${r.url}</span>` : 'Unknown',
                `<p class="text-end mb-0">${Number(r.subs||0).toLocaleString()}</p>`
              ]);

              // Meta
              const rel = timeAgoFrom(meta.cached_at);
              $lastUpdated.textContent = rel || '—';

              // 🔔 Map: emit rows (listener is already registered)
              window.dispatchEvent(new CustomEvent("subsCountryData", {
                detail: { rows: breakdowns.country || [] }
              }));

          } catch (e) {
              console.error('Subscribers fetch failed:', e);
              $kAll.textContent = $kAct.textContent = $kIn.textContent = '0';
              subsChart.data.labels = [];
              subsChart.data.datasets[0].data = [];
              subsChart.data.datasets[1].data = [];
              subsChart.data.datasets[2].data = [];
              subsChart.update();
              updateDonut(deviceDonut, [], 'device', 'subs');
              updateDonut(browserDonut, [], 'browser', 'subs');
              fillDT(dtCountry, [], (r, i) => [i + 1, '-', `<p class="text-end mb-0">0</p>`]);
              fillDT(dtState,   [], (r, i) => [i + 1, '-', '-', `<p class="text-end mb-0">0</p>`]);
              fillDT(dtCity,    [], (r, i) => [i + 1, '-', '-', '-', `<p class="text-end mb-0">0</p>`]);
              fillDT(dtUrl,     [], (r, i) => [i + 1, '-', `<p class="text-end mb-0">0</p>`]);
              $lastUpdated.textContent = '—';
              $growthPct.textContent = '–';

              // tell the map to hide
              window.dispatchEvent(new CustomEvent("subsCountryData", { detail: { rows: [] }}));
          } finally {
              showLoader(false);
          }
      }, 250);

      // Range buttons
      document.querySelectorAll('.perf-range').forEach(btn => {
          btn.addEventListener('click', function() {
              document.querySelectorAll('.perf-range').forEach(b => b.classList.remove('active'));
              this.classList.add('active');
              activeRange = this.dataset.range; // 24h,7d,28d,3m
              moreRange = null;
              loadData();
          });
      });

      // More modal apply
      $('#applyMoreRange').on('click', function() {
          const val = $('input[name="moreRange"]:checked').val();
          if (!val) return;
          if (val === 'custom') {
              moreRange = { type: 'custom', start: $('#customStart').val(), end: $('#customEnd').val() };
          } else {
              moreRange = { type: val };
          }
          document.querySelectorAll('.perf-range').forEach(b => b.classList.remove('active'));
          activeRange = 'more';
          $('#moreRangeModal').modal('hide');
          loadData();
      });

      // Persist domain in cookies and auto-load on change
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
          loadData();
      });

      // Restore last domain on load + force 24h
      (function restore() {
          const savedId = getCookie(COOKIE_DOMAIN_ID);
          const savedText = getCookie(COOKIE_DOMAIN_TEXT);
          document.querySelectorAll('.perf-range').forEach(b => b.classList.remove('active'));
          document.querySelector('.perf-range[data-range="24h"]').classList.add('active');
          activeRange = '24h';
          if (savedId) {
              const option = new Option(savedText || savedId, savedId, true, true);
              $('#domain-select').append(option).trigger('change'); // triggers loadData via change
          }
      })();
    })();
    </script>
@endpush