@extends('layouts.master')
@section('content')
<section class="content-body" id="server_info_page">
  <div class="container-fluid">

    {{-- Page Header --}}
    <div class="text-head mb-3">
      <h2>Server Info</h2>
    </div>

    {{-- App & Server Info + Disk/Memory Snapshot --}}
    <div class="row">
      <div class="col-md-6 mb-3">
        <div class="card h-100">
          <div class="card-header "><h5 class="mb-0 card-title">App & Server</h5></div>
          <div class="card-body">
            <ul class="list-group list-group-flush small">
              @foreach($info as $label => $value)
                <li class="list-group-item d-flex justify-content-between px-0">
                  <span>{{ ucwords(str_replace('_', ' ', $label)) }}</span>
                  <strong>{{ $value }}</strong>
                </li>
              @endforeach
            </ul>
          </div>
        </div>
      </div>

      <div class="col-md-6 mb-3">
        <div class="card h-100">
          <div class="card-header "><h5 class="mb-0 card-title">Resources Snapshot</h5></div>
          <div class="card-body">
            <p class="mb-1 small">Disk Usage</p>
            <div class="progress mb-1" style="height: 10px;">
              <div class="progress-bar bg-primary" style="width: {{ $diskPercent }}%;"></div>
            </div>
            <p class="text-muted small">
              {{ number_format($usedDisk / 1024 / 1024 / 1024, 2) }} GB /
              {{ number_format($totalDisk / 1024 / 1024 / 1024, 2) }} GB
              ({{ $diskPercent }}%)
            </p>

            <p class="mb-1 small mt-3">Memory Usage</p>
            <div class="progress" style="height: 10px;">
              <div class="progress-bar bg-success" style="width: {{ $memoryPercent }}%;"></div>
            </div>
            <p class="text-muted small">
              {{ number_format($usedMemory / 1024 / 1024 / 1024, 2) }} GB /
              {{ number_format($totalMemory / 1024 / 1024 / 1024, 2) }} GB
              ({{ number_format($memoryPercent, 2) }}%)
            </p>
          </div>
        </div>
      </div>
    </div>

    {{-- Real-time Charts --}}
    <div class="row">
      <div class="col-md-6 mb-3">
        <div class="card h-auto ">
          <div class="card-header "><h5 class="mb-0 card-title">CPU Usage (%)</h5></div>
          <div class="card-body">
            <canvas id="cpuChart" height="350"></canvas>
          </div>
        </div>
      </div>

      <div class="col-md-6 mb-3">
        <div class="card h-auto ">
          <div class="card-header "><h5 class="mb-0 card-title">Memory Usage (%)</h5></div>
          <div class="card-body">
            <canvas id="memoryChart" height="350"></canvas>
          </div>
        </div>
      </div>

      <div class="col-md-12 mb-3">
        <div class="card h-auto ">
          <div class="card-header "><h5 class="mb-0 card-title">Load Average</h5></div>
          <div class="card-body">
            <canvas id="loadChart" height="350"></canvas>
          </div>
        </div>
      </div>
    </div>

    {{-- PHP Extensions --}}
    <div class="row">
      <div class="col-12 mb-3">
        <div class="card h-auto ">
          <div class="card-header ">
            <h5 class="mb-0 card-title">Enabled PHP Extensions ({{ count($extensions) }})</h5>
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
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const metricsUrl = '{{ route('settings.server-info.metrics') }}';
  const cpuCores = navigator.hardwareConcurrency || 2;

  function makeLine(ctx, color, label, refValue, refLabel) {
    return new Chart(ctx, {
      type: 'line',
      data: {
        labels: Array(30).fill(''),
        datasets: [{
          label: label,
          data: Array(30).fill(0),
          borderColor: color,
          backgroundColor: color.replace(/,\s*1\)/, ', 0.1)'),
          fill: true,
          tension: 0.4,
          pointRadius: 0,
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        scales: {
          y: { beginAtZero: true },
          x: { display: false }
        },
        plugins: {
          legend: { display: true },
          annotation: {
            annotations: {
              refLine: {
                type: 'line',
                yMin: refValue,
                yMax: refValue,
                borderColor: 'rgba(0,0,0,0.5)',
                borderWidth: 2,
                borderDash: [6,6],
                label: {
                  content: refLabel,
                  enabled: true,
                  position: 'end'
                }
              }
            }
          }
        }
      }
    });
  }

  const cpuChart = makeLine(
    document.getElementById('cpuChart').getContext('2d'),
    'rgba(13,110,253,1)', 'CPU %',
    100, '100% Max'
  );

  const memChart = makeLine(
    document.getElementById('memoryChart').getContext('2d'),
    'rgba(25,135,84,1)', 'Memory %',
    100, '100% Max'
  );

  const loadChart = makeLine(
    document.getElementById('loadChart').getContext('2d'),
    'rgba(220,53,69,1)', 'Load Average',
    cpuCores, `CPU Cores (${cpuCores})`
  );

  function updateMetrics() {
    fetch(metricsUrl)
      .then(r => r.json())
      .then(({ cpu, memory, load_1 }) => {
        const now = new Date().toLocaleTimeString();

        cpuChart.data.labels.push(now); cpuChart.data.labels.shift();
        cpuChart.data.datasets[0].data.push(cpu); cpuChart.data.datasets[0].data.shift();
        cpuChart.update();

        memChart.data.labels.push(now); memChart.data.labels.shift();
        memChart.data.datasets[0].data.push(memory); memChart.data.datasets[0].data.shift();
        memChart.update();

        loadChart.data.labels.push(now); loadChart.data.labels.shift();
        loadChart.data.datasets[0].data.push(load_1); loadChart.data.datasets[0].data.shift();
        loadChart.update();
      });
  }

  updateMetrics();
  setInterval(updateMetrics, 3000);
});
</script>
@endpush
