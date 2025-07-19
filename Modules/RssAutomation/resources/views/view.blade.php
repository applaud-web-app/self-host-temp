@extends('layouts.master')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/sweetalert2/dist/sweetalert2.min.css') }}">
    <style>
        .icon-rss{
            border-radius: 50%;
            width: 30px;
            height: 30px;
            justify-content: center;
            align-items: center;
            display: flex;
        }
    </style>
@endpush

@section('content')
<section class="content-body">
    <div class="container-fluid">
        <div class="d-flex flex-wrap align-items-center text-head">
            <h2 class="mb-3 me-auto">RSS Feed Report</h2>
            <div class="mb-3">
                <a href="{{ route('rss.create') }}" class="btn btn-primary">
                    <i class="far fa-plus-circle me-2"></i> Add New RSS Feed
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-3">
                <form action="{{ route('rss.report') }}" method="GET" id="filterForm">
                    <div class="row">
                        <div class="col-xl-4 col-sm-6">
                            <input type="text" class="form-control mb-xl-0 mb-3" name="search_name" value="{{ request('search_name') }}" placeholder="Search Feed Name or URL...">
                        </div>
                        <div class="col-xl-3 col-sm-6 mb-3 mb-xl-0">
                            <select class="form-control form-select" name="status">
                                <option value="">Select Feed Type</option>
                                <option value="latest" {{ request('status') == 'latest' ? 'selected' : '' }}>Latest Feed</option>
                                <option value="random" {{ request('status') == 'random' ? 'selected' : '' }}>Random Type</option>
                            </select>
                        </div>
                        <div class="col-xl-3 col-sm-6 mb-3 mb-xl-0">
                            <select class="form-control form-select" name="order_by">
                                <option value="new_to_old" {{ request('order_by') == 'new_to_old' ? 'selected' : '' }}>Newest to Oldest</option>
                                <option value="old_to_new" {{ request('order_by') == 'old_to_new' ? 'selected' : '' }}>Oldest to Newest</option>
                            </select>
                        </div>
                        <div class="col-xl-2 col-sm-6 align-self-end">
                            <a href="{{ route('rss.view') }}" class="w-100 btn btn-secondary">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body p-3" id="tableData">
                        <div class="table-responsive">
                            <table class="table display" id="datatable">
                                <thead>
                                    <tr>
                                        <th>S.No</th>
                                        <th>Feed Info</th>
                                        <th>Sent Time</th>
                                        <th>Time Diff.</th>
                                        <th>Last Send</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be filled by DataTables -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: Show Feed Info -->
        <div class="modal fade" id="rssModal" tabindex="-1" aria-labelledby="rssModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-rss"></i> <span id="feed_title"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Feed URL:</strong> <span id="feed_url"></span></p>
                        <p><strong>Added At:</strong> <span id="feed_added"></span></p>
                        <p class="text-muted">This is a preview of your RSS feed details.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/sweetalert2/dist/sweetalert2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#datatable').DataTable({
                searching: false,
                paging: true,
                select: false,
                language: {
                    paginate: {
                        previous: '<i class="fas fa-chevron-left"></i>',
                        next: '<i class="fas fa-chevron-right"></i>'
                    }
                },
                lengthChange: true,
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route("rss.view") }}',
                    data: function(d) {
                        d.search_name = $('input[name="search_name"]').val(); // Get search term
                        d.status = $('select[name="status"]').val(); // Get selected feed type
                        d.order_by = $('select[name="order_by"]').val(); // Get selected order
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex' },
                    { data: 'feed_info', name: 'feed_info' },
                    { data: 'sent_time', name: 'sent_time' },
                    { data: 'time_diff', name: 'time_diff' },
                    { data: 'last_send', name: 'last_send' },
                    { data: 'type', name: 'type' },
                    { data: 'status', name: 'status' },
                    { data: 'actions', name: 'actions' }
                ],
                order: [[4, 'desc']], // Default sorting by created_at (desc)
                pageLength: 10,
                lengthChange: true,
                info: true,
                searching: false,
                language: {
                    paginate: {
                        previous: '<i class="fas fa-chevron-left"></i>',
                        next: '<i class="fas fa-chevron-right"></i>'
                    },
                    zeroRecords: "No RSS feeds found",
                    emptyTable: "No data available in table"
                },
                dom: '<"top"f>rt<"bottom"p><"clear">'
            });

            // Redraw the table whenever the user types or changes the filter
            $('input[name="search_name"]').on('keyup', function() {
                table.draw();
            });
            $('select[name="status"], select[name="order_by"]').on('change', function() {
                table.draw();
            });
        });
    </script>
    <script>
        $(document).ready(function () {
            // Handle status change with AJAX
            $(document).on('change', '.status_input', function() {
                var feedId = $(this).data('id');
                var status = $(this).prop('checked') ? 1 : 0;
                var statusUrl = $(this).data('url');

                $.ajax({
                    url: statusUrl,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        status: status
                    },
                    success: function(response) {
                        if (response.status) {
                            Swal.fire('Success', response.message, 'success');
                            $('#datatable').DataTable().ajax.reload(); // Reload the table
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    }
                });
            });
        });
    </script>
    <script>
        $(document).ready(function () {
            // Delete button with SweetAlert confirmation
            $(document).on('click', '.delete-btn', function() {
                var delUrl = $(this).data('url');

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This action will delete the feed permanently!',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.value) {
                        $.ajax({
                            url: delUrl,  // The URL from the data-url attribute
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.status) {
                                    Swal.fire('Deleted!', response.message, 'success');
                                    $('#datatable').DataTable().ajax.reload(); // Reload the table
                                } else {
                                    Swal.fire('Error', response.message, 'error');
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error("AJAX Error:", error);  // Check if there's an error in the request
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush