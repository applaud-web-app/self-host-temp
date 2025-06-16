@extends('layouts.master')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('title', 'Server Info')

@section('content')
<section class="content-body py-3">
  <div class="container-fluid">

    {{-- ----------------------------------------------------------
         Page Header
    ---------------------------------------------------------- --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="mb-0">Server Info</h2>
      <small class="text-muted">Last refreshed: <span id="lastRefreshed">{{ now()->format('H:i:s') }}</span></small>
    </div>

    {{-- ----------------------------------------------------------
         Static App & Server Info + Disk & Memory Snapshot
    ---------------------------------------------------------- --}}
    <div class="row g-3">

      {{-- App & Server --}}
      <div class="col-lg-6">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-transparent border-bottom-0">
            <h5 class="mb-0">App & Server</h5>
          </div>
          <div class="card-body p-0">
            <ul class="list-group list-group-flush small">
              @foreach($info as $label => $value)
                <li class="list-group-item d-flex justify-content-between px-3 py-2">
                  <span class="text-muted">{{ Str::headline($label) }}</span>
                  <strong class="text-end">{{ $value }}</strong>
                </li>
              @endforeach
            </ul>
          </div>
        </div>
      </div>

      {{-- Resources --}}
      <div class="col-lg-6">
        @php
          // Helper could live in app/helpers.php but kept here for brevity
          if (!function_exists('bytes_human')) {
              function bytes_human(int $bytes): string {
                  $units = ['B','KB','MB','GB','TB'];
                  for ($i = 0; $bytes >= 1024 && $i < count($units)-1; $i++) {
                      $bytes /= 1024;
                  }
                  return number_format($bytes, 1).' '.$units[$i];
              }
          }

          // Memory of current PHP process (peak & current)
          $memUsed    = memory_get_usage(false);
          $memPeak    = memory_get_peak_usage(true);
          $memPercent = $memPeak ? round($memUsed / $memPeak * 100, 1) : 0;
        @endphp

        <div class="card shadow-sm h-100">
          <div class="card-header bg-transparent border-bottom-0">
            <h5 class="mb-0">Resources Snapshot</h5>
          </div>
          <div class="card-body">

            {{-- Disk --}}
            <p class="fw-semibold small mb-1">Disk Usage</p>
            <div class="progress mb-1" role="progressbar" aria-valuenow="{{ $diskPercent }}" aria-valuemin="0" aria-valuemax="100" style="height: 8px;">
              <div class="progress-bar bg-primary" style="width: {{ $diskPercent }}%;"></div>
            </div>
            <p class="text-muted small mb-3">
              {{ bytes_human($usedDisk) }} / {{ bytes_human($totalDisk) }}
              ({{ $diskPercent }}%)
            </p>

            {{-- Memory --}}
            <p class="fw-semibold small mb-1">Memory Usage <small class="text-muted">(PHP)</small></p>
            <div class="progress mb-1" role="progressbar" aria-valuenow="{{ $memPercent }}" aria-valuemin="0" aria-valuemax="100" style="height: 8px;">
              <div class="progress-bar bg-success" style="width: {{ $memPercent }}%;"></div>
            </div>
            <p class="text-muted small">
              {{ bytes_human($memUsed) }} / {{ bytes_human($memPeak) }}
              ({{ $memPercent }}%)
            </p>
          </div>
        </div>
      </div>
    </div>

    {{-- ----------------------------------------------------------
         Real‑time CPU & Memory Charts
    ---------------------------------------------------------- --}}
    <div class="row g-3 mt-0">
      <div class="col-lg-6">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-transparent border-bottom-0">
            <h5 class="mb-0">CPU Usage (%)</h5>
          </div>
          <div class="card-body">
            <canvas id="cpuChart" height="170" role="img" aria-label="CPU usage line chart"></canvas>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-transparent border-bottom-0">
            <h5 class="mb-0">Memory Usage (%)</h5>
          </div>
          <div class="card-body">
            <canvas id="memoryChart" height="170" role="img" aria-label="Memory usage line chart"></canvas>
          </div>
        </div>
      </div>
    </div>

    {{-- ----------------------------------------------------------
         Enabled PHP Extensions
    ---------------------------------------------------------- --}}
    <div class="row g-3 mt-0">
      <div class="col-12">
        <div class="card shadow-sm">
          <div class="card-header bg-transparent border-bottom-0">
            <h5 class="mb-0">Enabled PHP Extensions <span class="badge bg-light text-dark align-text-bottom">{{ count($extensions) }}</span></h5>
          </div>
          <div class="card-body" style="max-height:240px; overflow:auto;">
            <div class="d-flex flex-wrap gap-1">
              @foreach($extensions as $ext)
                <span class="badge bg-secondary">{{ $ext }}</span>
              @endforeach
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</section>
@endsection


@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  /** ------------------------------------------------------------------
   *  Utilities
   * -------------------------------------------------------------------*/
  const MAX_POINTS = 30;  // Show last 30 datapoints (~1 min @2s)
  const hiddenWhenTabInactive = () => document.hidden;

  function makeChart(ctx, color) {
    return new Chart(ctx, {
      type: 'line',
      data: {
        labels: Array(MAX_POINTS).fill(''),
        datasets: [{
          data: Array(MAX_POINTS).fill(null),
          borderColor: color,
          backgroundColor: color.replace(/,\s*1\)/, ', 0.1)'),
          fill: true,
          tension: 0.3,
          pointRadius: 0
        }]
      },
      options: {
        animation: false,
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            min: 0,
            max: 100,
            ticks: { callback: v => v + '%', stepSize: 25 }
          }
        },
        plugins: { legend: { display: false } }
      }
    });
  }

  const cpuChart = makeChart(document.getElementById('cpuChart').getContext('2d'), 'rgba(13,110,253,1)');
  const memChart = makeChart(document.getElementById('memoryChart').getContext('2d'), 'rgba(25,135,84,1)');
  const metricsUrl = @json(route('settings.server-info.metrics'));

  /** ------------------------------------------------------------------
   *  Polling with graceful degradation
   * -------------------------------------------------------------------*/
  const controller = new AbortController();

  async function fetchMetrics() {
    try {
      const res = await fetch(metricsUrl, { signal: controller.signal, headers: { 'X-Requested-With': 'XMLHttpRequest' }});
      if (!res.ok) throw new Error(res.statusText);
      return await res.json();
    } catch (e) {
      console.error(e);
      return null;
    }
  }

  function addPoint(chart, value) {
    const now = new Date();
    const label = now.toLocaleTimeString([], { hour12:false, hour:'2-digit', minute:'2-digit', second:'2-digit'});
    chart.data.labels.push(label);
    chart.data.datasets[0].data.push(Number(value));

    // Trim arrays
    if (chart.data.labels.length > MAX_POINTS) {
      chart.data.labels.shift();
      chart.data.datasets[0].data.shift();
    }
    chart.update('none');
  }

  async function updateCharts() {
    if (hiddenWhenTabInactive()) return; // Skip to save resources
    const data = await fetchMetrics();
    if (!data) return;

    addPoint(cpuChart, data.cpu);
    addPoint(memChart, data.memory);

    document.getElementById('lastRefreshed').textContent = new Date().toLocaleTimeString();
  }

  // Initial burst of updates
  updateCharts();
  let intervalId = setInterval(updateCharts, 2000);

  // Pause when tab hidden
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      clearInterval(intervalId);
    } else {
      updateCharts();
      intervalId = setInterval(updateCharts, 2000);
    }
  });
});
</script>
@endpush
