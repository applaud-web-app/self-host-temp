@extends('layouts.master')

@section('content')
<section class="content-body">
    <div class="container-fluid">
        <div class="text-head">
            <h2 class="mb-3 me-auto">Server Info</h2>
        </div>

        <div class="row">
            {{-- App & Server Info --}}
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">App & Server</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item d-flex justify-content-between px-0"><span>Laravel Version</span><strong>{{ App::version() }}</strong></li>
                            <li class="list-group-item d-flex justify-content-between px-0"><span>PHP Version</span><strong>{{ phpversion() }}</strong></li>
                            <li class="list-group-item d-flex justify-content-between px-0"><span>Environment</span><strong>{{ app()->environment() }}</strong></li>
                            <li class="list-group-item d-flex justify-content-between px-0"><span>Debug Mode</span><strong>{{ config('app.debug') ? 'ON' : 'OFF' }}</strong></li>
                            <li class="list-group-item d-flex justify-content-between px-0"><span>Server Software</span><strong>{{ $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' }}</strong></li>
                            <li class="list-group-item d-flex justify-content-between px-0"><span>OS</span><strong>{{ php_uname('s') }} {{ php_uname('r') }}</strong></li>
                            <li class="list-group-item d-flex justify-content-between px-0"><span>Uptime</span>
                                <strong>
                                    @php
                                        $uptime = @file_get_contents('/proc/uptime');
                                        $uptime = $uptime ? explode(' ', $uptime)[0] : 0;
                                        $days = floor($uptime / 86400);
                                        $hours = floor(($uptime % 86400) / 3600);
                                        $minutes = floor(($uptime % 3600) / 60);
                                    @endphp
                                    {{ $days }}d {{ $hours }}h {{ $minutes }}m
                                </strong>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Disk & Memory Info --}}
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Resources</h5>
                    </div>
                    <div class="card-body">
                        @php
                            $totalDisk  = disk_total_space(base_path());
                            $freeDisk   = disk_free_space(base_path());
                            $usedDisk   = $totalDisk - $freeDisk;
                            $diskPercent = $totalDisk > 0 ? round($usedDisk / $totalDisk * 100, 1) : 0;

                            $totalMemory = memory_get_usage(true);
                            $usedMemory  = memory_get_usage(false);
                            $memoryPercent = $totalMemory > 0 ? round($usedMemory / $totalMemory * 100, 1) : 0;

                            function bytesToHuman($bytes) {
                                $units = ['B', 'KB', 'MB', 'GB', 'TB'];
                                for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
                                    $bytes /= 1024;
                                }
                                return round($bytes, 2).' '.$units[$i];
                            }
                        @endphp

                        <p class="mb-1 small">Disk Usage</p>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-primary" style="width: {{ $diskPercent }}%"></div>
                        </div>
                        <p class="text-muted small">{{ bytesToHuman($usedDisk) }} / {{ bytesToHuman($totalDisk) }} ({{ $diskPercent }}%)</p>

                        <p class="mb-1 small mt-3">Memory Usage</p>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: {{ $memoryPercent }}%"></div>
                        </div>
                        <p class="text-muted small">{{ bytesToHuman($usedMemory) }} / {{ bytesToHuman($totalMemory) }} ({{ $memoryPercent }}%)</p>
                    </div>
                </div>
            </div>

            {{-- PHP Extensions --}}
            <div class="col-lg-12">
                <div class="card h-auto">
                    <div class="card-header">
                        <h5 class="mb-0">Enabled PHP Extensions</h5>
                    </div>
                    <div class="card-body">
                        @php $extensions = get_loaded_extensions(); sort($extensions); @endphp
                        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-2">
                            @foreach ($extensions as $ext)
                                <div class="col"><span class="badge light badge-dark d-block w-100">{{ $ext }}</span></div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Real-Time Resource Charts --}}
            <div class="col-md-6">
                <div class="card h-auto">
                    <div class="card-header">
                        <h5 class="mb-0">CPU Usage (Real-Time)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="cpuChart" height="180"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card h-auto">
                    <div class="card-header">
                        <h5 class="mb-0">Memory Usage (Real-Time)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="memoryChart" height="180"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const cpuCtx = document.getElementById('cpuChart').getContext('2d');
    const memoryCtx = document.getElementById('memoryChart').getContext('2d');

    const labels = Array.from({ length: 12 }, () => new Date().toLocaleTimeString());
    const cpuChart = new Chart(cpuCtx, {
        type: 'line',
        data: { labels: [...labels], datasets: [{ label: 'CPU %', data: Array(12).fill(0), borderColor: '#0d6efd', backgroundColor: 'rgba(13,110,253,0.1)', fill: true, tension: 0.3, pointRadius: 2 }] },
        options: { animation: false, scales: { y: { min: 0, max: 100, ticks: { callback: val => val + '%' } } }, plugins: { legend: { display: false } } }
    });
    const memoryChart = new Chart(memoryCtx, {
        type: 'line',
        data: { labels: [...labels], datasets: [{ label: 'Memory %', data: Array(12).fill(0), borderColor: '#198754', backgroundColor: 'rgba(25,135,84,0.1)', fill: true, tension: 0.3, pointRadius: 2 }] },
        options: { animation: false, scales: { y: { min: 0, max: 100, ticks: { callback: val => val + '%' } } }, plugins: { legend: { display: false } } }
    });

    function generateDummyData() {
        const cpu = Math.random() * 100; // Random value 0-100%
        const memory = Math.random() * 100;
        const now = new Date().toLocaleTimeString();

        // Update CPU chart
        cpuChart.data.labels.push(now);
        cpuChart.data.labels.shift();
        cpuChart.data.datasets[0].data.push(cpu);
        cpuChart.data.datasets[0].data.shift();
        cpuChart.update();

        // Update Memory chart
        memoryChart.data.labels.push(now);
        memoryChart.data.labels.shift();
        memoryChart.data.datasets[0].data.push(memory);
        memoryChart.data.datasets[0].data.shift();
        memoryChart.update();
    }

    setInterval(generateDummyData, 2000); // Every 2 sec
    generateDummyData(); // Initial call
});
</script>
@endsection
