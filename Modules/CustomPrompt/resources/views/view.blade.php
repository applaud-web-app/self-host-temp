@extends('layouts.master')

@section('content')
<section class="content-body">
    <div class="container-fluid position-relative">
        <div class="d-flex flex-wrap align-items-center justify-content-between text-head mb-3">
            <h2 class="me-auto mb-0">Custom Prompts</h2>
        </div>

        <div class="card h-auto">
            <div class="card-body p-3">
                <!-- Filter Form -->
                <form id="filter-form" method="GET" autocomplete="off">
                    <div class="row g-3">
                        <div class="col-lg-3">
                            <div class="position-relative">
                                <input type="text" id="search_term" name="search_term" class="form-control" placeholder="Search by Title or Domain...">
                                <div class="invalid-feedback"></div>
                                <i class="far fa-search text-primary position-absolute top-50 translate-middle-y" style="right: 10px;"></i>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <select name="status" id="status" class="form-control form-select">
                                <option value="">All Statuses</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="col-lg-3">
                           <select name="site_web" id="site_web" class="form-control form-select">
                                <option value="">All Domains</option>
                                @foreach ($domains as $domain)
                                    <option value="{{ $domain->name }}">{{ $domain->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3">
                            <button class="btn btn-danger light w-100" id="resetFilter"><i class="fas fa-undo me-1"></i> Reset</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Custom Prompts Table -->
        <div class="card h-auto">
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table id="customPromptTable" class="table display">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Domain</th>
                                <th>Title</th>
                                <th>Allow Btn</th>
                                <th>Deny Btn</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
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
    $(document).ready(function () {
        var table = $('#customPromptTable').DataTable({
            searching: false,
            paging: true,
            select: false,
            language: {
                paginate: {
                    previous: '<i class="fas fa-angle-double-left"></i>',
                    next: '<i class="fas fa-angle-double-right"></i>'
                }
            },
            lengthChange: false,
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("customprompt.index") }}',
                data: function(d) {
                    d.search_term = $('#search_term').val();
                    d.status = $('#status').val();
                    d.site_web = $('#site_web').val();
                }
            },
            columns: [
                { data: 'DT_RowIndex', orderable: false },
                { data: 'domain' },
                { data: 'title' },
                { data: 'allow_btn_text' },
                { data: 'deny_btn_text' },
                { data: 'status' },
                { data: 'action', orderable: false, searchable: false }
            ]
        });

        // Redraw the table on filter change
        $('#search_term, #status, #site_web').on('change keyup', function () {
            table.draw();
        });

        // Reset filter button
        $('#resetFilter').on('click', function (e) {
            e.preventDefault();
            $('#filter-form')[0].reset(); // Reset all filter form fields
            table.draw(); // Redraw the table with reset filters
        });
    });
</script>
@endpush
