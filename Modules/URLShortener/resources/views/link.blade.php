@extends('layouts.master')

@section('content')
@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
@endpush
<section class="content-body">
    <div class="container-fluid">
        <div class="d-flex flex-wrap align-items-center justify-content-between text-head mb-3">
            <h2 class="me-auto mb-0">Short Links</h2>
            <a href="{{ route('url_shortener.link.create') }}" class="btn btn-primary">
                <i class="fas fa-plus pe-2"></i> Create Short Links
            </a>
        </div>

        <div class="card h-auto mb-2">
            <div class="card-body p-3">
                <div class="row g-2">
                    <div class="col-lg-3 col-md-3 col-12">
                        <div class="position-relative">
                            <input type="text" id="searchChannel" class="form-control" placeholder="Search by short URL / full URL…">
                            <div class="invalid-feedback"></div>
                            <i class="far fa-search text-primary position-absolute top-50 translate-middle-y" style="right: 10px;"></i>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-3 col-12">
                        <select id="filterStatus" class="form-select me-2 form-control">
                            <option value="">All Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-3 col-12">
                        <button class="btn btn-danger light" id="resetFilter"><i class="fas fa-undo me-1"></i> Reset</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card h-auto">
            <div class="card-header">
                <h4 class="card-title">Short URLs</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="linkTable" class="table display">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Target URL</th>
                                <th>Short URL</th>
                                <th>Total Subscribers</th>
                                <th>Created At</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/izitoast/dist/js/iziToast.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // --- CSRF for all AJAX ---
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    });


    // Initialize DataTable
    var table = $('#linkTable').DataTable({
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
            url: "{{ route('url_shortener.link') }}",
            data: function(d) {
                d.search_term   = $('#searchChannel').val(); // unified search term
                d.filter_status = $('#filterStatus').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'target_url', name: 'target_url', orderable: false },
            { data: 'short_url',  name: 'short_url',  orderable: false },
            { data: 'total_subscribers',  name: 'total_subscribers',  orderable: false },
            { data: 'created_at', name: 'created_at' },
            { data: 'status',     name: 'status', orderable: false },
            { data: 'actions',    name: 'actions', orderable: false, searchable: false }
        ]
    });

    // Live search
    $('#searchChannel').on('keyup', function() { table.draw(); });

    // Filter changes
    $('#filterStatus').on('change', function() { table.draw(); });

    // Reset filters
    $('#resetFilter').click(function(e) {
        e.preventDefault();
        $('#searchChannel').val('');
        $('#filterStatus').val('');
        table.draw();
    });

    // --- Copy to Clipboard + iziToast ---
    $(document).on('click', '.copy-url', function() {
        var url = $(this).data('url');
        navigator.clipboard.writeText(url).then(function() {
            iziToast.success({
                title: 'Success',
                message: 'Copied: ' + url,
                position: 'topRight'
            });
        }).catch(function(err) {
            iziToast.error({
                title: 'Error',
                message: 'Could not copy: ' + (err && err.message ? err.message : 'Unknown error'),
                position: 'topRight'
            });
        });
    });

    // --- Status switch (toggle) ---
    $(document).on('change', '.status_input', function() {
        var $el = $(this);
        var id = $el.data('id');
        var status = $el.is(':checked') ? 1 : 0;

        // Disable while processing
        $el.prop('disabled', true);

        $.post("{{ route('url_shortener.youtube.status') }}", { id: id, status: status })
            .done(function(res) {
                if (res && res.success) {
                    iziToast.success({
                        title: 'Success',
                        message: res.message || 'Status updated.',
                        position: 'topRight'
                    });
                } else {
                    // revert on failure
                    $el.prop('checked', !status);
                    iziToast.error({
                        title: 'Error',
                        message: (res && res.message) ? res.message : 'Failed to update status.',
                        position: 'topRight'
                    });
                }
            })
            .fail(function(xhr) {
                // revert on failure
                $el.prop('checked', !status);
                let msg = 'Failed to update status.';
                if (xhr && xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                iziToast.error({ title: 'Error', message: msg, position: 'topRight' });
            })
            .always(function() {
                $el.prop('disabled', false);
                // Optionally refresh row to keep in sync
                table.ajax.reload(null, false);
            });
    });

    // --- Delete (SweetAlert2 confirm) ---
    window.deleteUrl = function(id) {
        Swal.fire({
            title: 'Delete this entry?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('url_shortener.youtube.delete', ['id' => '___ID___']) }}".replace('___ID___', id),
                    type: 'get'
                })
                .done(function(res) {
                    if (res && res.success) {
                        iziToast.success({
                            title: 'Success',
                            message: res.message || 'Deleted successfully.',
                            position: 'topRight'
                        });
                        table.ajax.reload(null, false);
                    } else {
                        iziToast.error({
                            title: 'Error',
                            message: (res && res.message) ? res.message : 'Failed to delete.',
                            position: 'topRight'
                        });
                    }
                })
                .fail(function(xhr) {
                    let msg = 'Failed to delete.';
                    if (xhr && xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    iziToast.error({ title: 'Error', message: msg, position: 'topRight' });
                });
            }
        });
    };
});
</script>
@endpush