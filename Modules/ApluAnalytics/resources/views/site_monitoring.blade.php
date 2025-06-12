@extends('layouts.master')

@section('content')
<section class="content-body">
    <div class="container-fluid position-relative">
        <div class="d-flex flex-wrap align-items-center justify-content-between text-head mb-3">
            <h2 class="me-auto mb-0">Site Monitoring</h2>
        </div>
        <div class="card">
           
            <div class="card-body">
                <div class="table-responsive">
                    <table id="monitoringTable" class="table display">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Site</th>
                                <th>Uptime (%)</th>
                                <th>Last Check</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 1; $i <= 10; $i++)
                                @php
                                    $statuses = ['Online', 'Offline', 'Degraded'];
                                    $status = $statuses[array_rand($statuses)];
                                    $badgeClass = match(strtolower($status)) {
                                        'online' => 'badge-success',
                                        'offline' => 'badge-danger',
                                        'degraded' => 'badge-warning',
                                        default => 'badge-secondary'
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $i }}</td>
                                    <td>site{{ $i }}.com</td>
                                    <td>{{ rand(95, 100) }}%</td>
                                    <td>{{ now()->subMinutes(rand(1, 120))->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <span class="badge light {{ $badgeClass }}">{{ $status }}</span>
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
       $('#monitoringTable').DataTable({
           pagingType: 'simple_numbers',
    info: false,
            language: {
                paginate: {
                    previous: '<i class="fas fa-chevron-left"></i>',
                    next: '<i class="fas fa-chevron-right"></i>'
                }
            }
        });
    });
</script>
@endpush
