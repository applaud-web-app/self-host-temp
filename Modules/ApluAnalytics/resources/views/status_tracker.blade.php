@extends('layouts.master')

@section('content')
<section class="content-body">
    <div class="container-fluid position-relative">
        <div class="d-flex flex-wrap align-items-center justify-content-between text-head mb-3">
            <h2 class="me-auto mb-0">Status Tracker</h2>
        </div>
        <div class="card">
           
            <div class="card-body">
                <div class="table-responsive">
                    <table id="statusTable" class="table display">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Entity</th>
                                <th>Old Status</th>
                                <th>New Status</th>
                                <th>Changed By</th>
                                <th>Changed At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 1; $i <= 12; $i++)
                                @php
                                    $statuses = ['Active', 'Inactive', 'Pending'];
                                    $oldStatus = $statuses[array_rand($statuses)];
                                    $newStatus = $statuses[array_rand($statuses)];
                                @endphp
                                <tr>
                                    <td>{{ $i }}</td>
                                    <td>Resource {{ $i }}</td>
                                    <td>{{ $oldStatus }}</td>
                                    <td>{{ $newStatus }}</td>
                                    <td>User {{ rand(1, 5) }}</td>
                                    <td>{{ now()->subHours(rand(1, 100))->format('Y-m-d H:i') }}</td>
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
        $('#statusTable').DataTable({  pagingType: 'simple_numbers',
    info: false,
    language: {
        paginate: {
            previous: '<i class="fas fa-chevron-left"></i>',
            next: '<i class="fas fa-chevron-right"></i>'
        }
    } });
    });
</script>
@endpush
