@extends('layouts.master')

@push('styles')
    <link rel="stylesheet" href="{{ asset('/vendor/bootstrap-daterangepicker/daterangepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/sweetalert2/dist/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
@endpush

@section('content')
<section class="content-body">
    <div class="container-fluid">
        <!-- Header Section -->
        <div class="d-flex flex-wrap align-items-center text-head ">
            <h2 class="mb-3 me-auto">RSS Feed Report</h2>
            <div class="mb-3">
                <a href="{{ route('rssautomation.add') }}" class="btn btn-primary">
                    <i class="far fa-plus-circle me-2"></i> Add New RSS Feed
                </a>
            </div>
        </div>

        <!-- Success Message -->
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <div class="card h-auto">
                    <div class="card-body p-3">
                        <form action="" method="GET" autocomplete="off" id="filterForm">
                            <div class="row">
                                <div class="col-xl-4 col-sm-6">
                                    <input type="text" class="form-control mb-xl-0 mb-3" name="feed_title" id="feed_title" placeholder="Search Feed....">
                                </div>
                                <div class="col-xl-3 col-sm-6 mb-3 mb-xl-0">
                                    <div>
                                        <select class="form-control form-select" aria-label="Default select example" name="status">
                                            <option value="">Select Feed Type</option>
                                            <option value="latest">Latest Feed</option>
                                            <option value="random">Random Type</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-xl-2 col-sm-6 align-self-end">
                                    <div>
                                        <button type="button" id="resetFilter" class="w-100 btn btn-danger light" title="Click here to remove filter">
                                            <i class="fas fa-undo me-1"></i> Reset
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
        <!-- RSS Feed Table -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body p-3" id="tableData">
                        <div class="table-responsive">
                            <table class="table display" id="datatable">
                                <thead>
                                    <tr>
                                        <th>S.No</th>
                                        <th>Feed Title</th>
                                        <th>Feed URL</th>
                                        <th>Added At</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        // If you use backend data, replace this with your $feeds loop
                                        // Example: foreach($feeds as $i => $feed)
                                    @endphp
                                    @for ($i = 1; $i <= 10; $i++)
                                        @php
                                            $feedTitle = "Feed Title $i";
                                            $feedUrl = "https://rssdomain$i.com/feed";
                                            $addedAt = \Carbon\Carbon::create(2025, 6, $i, 10, 0)->format('Y-m-d h:i A');
                                        @endphp
                                        <tr>
                                            <td>{{ $i }}</td>
                                            <td>
                                                <a href="javascript:void(0)"
                                                   class="open-modal text-decoration-none"
                                                   data-title="{{ $feedTitle }}"
                                                   data-url="{{ $feedUrl }}"
                                                   data-added="{{ $addedAt }}"
                                                   data-bs-toggle="modal"
                                                   data-bs-target="#rssModal">
                                                    {{ $feedTitle }}
                                                </a>
                                            </td>
                                            <td><a href="{{ $feedUrl }}" target="_blank">{{ $feedUrl }}</a></td>
                                            <td>{{ $addedAt }}</td>
                                            <td>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-secondary open-modal"
                                                    data-title="{{ $feedTitle }}"
                                                    data-url="{{ $feedUrl }}"
                                                    data-added="{{ $addedAt }}"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#rssModal">
                                                    <i class="fas fa-eye me-1"></i> View
                                                </button>
                                            </td>
                                        </tr>
                                    @endfor
                                 
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RSS Feed Modal -->
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
    <script src="{{ asset('vendor/bootstrap-daterangepicker/daterangepicker.js') }}"></script>
    <script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert2/dist/sweetalert2.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            // Initialize DataTable on the static table
            $('#datatable').DataTable({
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

            // Modal: Show RSS feed info
            $(document).on('click', '.open-modal', function(){
                $('#feed_title').text($(this).data('title'));
                $('#feed_url').html('<a href="' + $(this).data('url') + '" target="_blank">' + $(this).data('url') + '</a>');
                $('#feed_added').text($(this).data('added'));
            });

            // Reset modal on close
            $('#rssModal').on('hidden.bs.modal', function () {
                $('#feed_title').text('');
                $('#feed_url').text('');
                $('#feed_added').text('');
            });
        });
    </script>
@endpush
