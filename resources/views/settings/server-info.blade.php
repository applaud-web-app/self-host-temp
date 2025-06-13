@extends('layouts.master')

@section('content')
<section class="content-body">
  <div class="container-fluid">

    {{-- Page Header --}}
    <div class="text-head mb-3">
      <h2>Server Info</h2>
    </div>

    {{-- Static App & Server Info + Disk & Memory Snapshot --}}
    <div class="row ">
      {{-- App & Server --}}
      <div class="col-md-6 mb-3">
        <div class="card h-100">
          <div class="card-header"><h5>App & Server</h5></div>
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

      {{-- Disk & Memory --}}
      <div class="col-md-6 mb-3">
        @php
          function bytesToHuman($bytes) {
            $units = ['B','KB','MB','GB','TB'];
            for ($i = 0; $bytes >= 1024 && $i < count($units)-1; $i++) {
              $bytes /= 1024;
            }
            return round($bytes, 2).' '.$units[$i];
          }
        @endphp

        <div class="card h-100">
          <div class="card-header"><h5>Resources Snapshot</h5></div>
          <div class="card-body">
            {{-- Disk --}}
            <p class="mb-1 small">Disk Usage</p>
            <div class="progress mb-1" style="height: 8px;">
              <div class="progress-bar bg-primary" style="width: {{ $diskPercent }}%;"></div>
            </div>
            <p class="text-muted small">
              {{ bytesToHuman($usedDisk) }} / {{ bytesToHuman($totalDisk) }}
              ({{ $diskPercent }}%)
            </p>

            {{-- Memory (PHP process) --}}
            @php
              $usedMem      = memory_get_usage(false);
              $totalMem     = memory_get_usage(true);
              $memPercent   = $totalMem > 0
                ? round($usedMem / $totalMem * 100, 1)
                : 0;
            @endphp

            <p class="mb-1 small mt-3">Memory Usage</p>
            <div class="progress" style="height: 8px;">
              <div class="progress-bar bg-success" style="width: {{ $memPercent }}%;"></div>
            </div>
            <p class="text-muted small">
              {{ bytesToHuman($usedMem) }} / {{ bytesToHuman($totalMem) }}
              ({{ $memPercent }}%)
            </p>
          </div>
        </div>
      </div>
    </div>

    {{-- PHP Extensions --}}
    <div class="row">
      <div class="col-lg-12 mb-3">
        <div class="card h-100">
          <div class="card-header"><h5>Enabled PHP Extensions</h5></div>
          <div class="card-body">
            <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-2">
              @foreach($extensions as $ext)
                <div class="col">
                  <span class="badge bg-secondary d-block text-truncate">{{ $ext }}</span>
                </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Real-time CPU & Memory Charts --}}
    <div class="row">
      <div class="col-md-6 mb-3">
        <div class="card">
          <div class="card-header"><h5>CPU Usage (%)</h5></div>
          <div class="card-body">
            <canvas id="cpuChart" height="180"></canvas>
          </div>
        </div>
      </div>
      <div class="col-md-6 mb-3">
        <div class="card">
          <div class="card-header"><h5>Memory Usage (%)</h5></div>
          <div class="card-body">
            <canvas id="memoryChart" height="180"></canvas>
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
  const cpuCtx = document.getElementById('cpuChart').getContext('2d');
  const memCtx = document.getElementById('memoryChart').getContext('2d');

  function makeLine(ctx, color) {
    return new Chart(ctx, {
      type: 'line',
      data: {
        labels: Array(12).fill(''),
        datasets: [{
          data: Array(12).fill(0),
          borderColor: color,
          backgroundColor: color.replace('1)', '0.1)'),
          fill: true,
          tension: 0.3,
          pointRadius: 0
        }]
      },
      options: {
        animation: false,
        scales: { y: { min: 0, max: 100, ticks: { callback: v => v + '%' } } },
        plugins: { legend: { display: false } }
      }
    });
  }

  const cpuChart = makeLine(cpuCtx, 'rgba(13,110,253,1)');
  const memChart = makeLine(memCtx, 'rgba(25,135,84,1)');

  function update() {
    fetch(@json(route('settings.server-info.metrics')))
      .then(r => r.json())
      .then(({cpu, memory}) => {
        const now = new Date().toLocaleTimeString();

        // push CPU
        cpuChart.data.labels.push(now);
        cpuChart.data.labels.shift();
        cpuChart.data.datasets[0].data.push(cpu);
        cpuChart.data.datasets[0].data.shift();
        cpuChart.update();

        // push Memory
        memChart.data.labels.push(now);
        memChart.data.labels.shift();
        memChart.data.datasets[0].data.push(memory);
        memChart.data.datasets[0].data.shift();
        memChart.update();
      })
      .catch(console.error);
  }

  update();
  setInterval(update, 2000);
});
</script>
@endpush
