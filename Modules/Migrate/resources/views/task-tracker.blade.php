@extends('layouts.master')
@push('styles')
    {{-- SweetAlert2 CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
@endpush
@section('content')
<section class="content-body">
    <div class="container-fluid position-relative">
        <div class="text-head mb-3 d-flex align-items-center">
            <h2 class="me-auto mb-0">Task Tracker</h2>
            <button class="btn btn-danger btn-sm" id="emptyTrackerBtn">Empty Tracker</button>
        </div>

        {{-- Filters --}}
        <div class="row g-2 mb-3">
            <div class="col-12 col-md-3">
                <input id="searchName" type="text" class="form-control" placeholder="Search task name">
            </div>
            <div class="col-12 col-md-3">
                <select id="filterStatus" class="form-control">
                    <option value="">All status</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="completed">Completed</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <table id="taskTrackerTable" class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Task Name</th>
                            <th>Status</th>
                            <th>Message</th>
                            <th>Started At</th>
                            <th>Completed At</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
$(function () {
    let table = $('#taskTrackerTable').DataTable({
        searching: false,
        paging: true,
        select: false,
        lengthChange: false,
        processing: true,
        serverSide: true,
        language: {
            paginate: {
                previous: '<i class="fas fa-angle-double-left"></i>',
                next: '<i class="fas fa-angle-double-right"></i>'
            }
        },
        ajax: {
            url: "{{ route('mig.task-tracker') }}",
            data: function(d) {
                d.search_name   = $('#searchName').val();
                d.filter_status = $('#filterStatus').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable:false, searchable:false },
            { data: 'task_name',  name: 'task_name' },
            { data: 'status',     name: 'status', orderable:false, searchable:false },
            { data: 'message',    name: 'message' },
            { data: 'started_at', name: 'started_at' },
            { data: 'completed_at', name: 'completed_at' },
        ],
        order: [[4, 'desc']]
    });

    $('#searchName').on('keyup', function(){ table.draw(); });
    $('#filterStatus').on('change', function(){ table.draw(); });
});
</script>

{{-- SweetAlert2 JS --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        $('#emptyTrackerBtn').on('click', function() {
            Swal.fire({
                title: 'Are you sure?',
                text: "This will permanently empty the task tracker!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, empty it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('mig.empty-tracker') }}",
                        type: 'GET',
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Cleared!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            $('#taskTrackerTable').DataTable().ajax.reload();
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An error occurred while trying to empty the task tracker.'
                            });
                        }
                    });
                }
            });
        });
    });
</script>
@endpush