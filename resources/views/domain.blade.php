@extends('layouts.master')

@section('content')
<section class="content-body">
    <div class="container-fluid position-relative">
        <div class="d-flex flex-wrap align-items-center justify-content-between text-head mb-3">
            <h2 class="me-auto mb-0">Domain Management</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDomainModal">
                <i class="fas fa-plus pe-2"></i>Add Domain
            </button>
        </div>

        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Connected Domains</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="domainTable" class="table display">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Created At</th>
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

<!-- Add Domain Modal -->
<div class="modal fade" id="addDomainModal" tabindex="-1" aria-labelledby="addDomainModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDomainModalLabel">Add New Domain</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addDomainForm" method="POST" action="#">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="domainName" class="form-label">
                            Domain Name <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            class="form-control"
                            id="domainName"
                            name="domain_name"
                            placeholder="example.com"
                            required
                        >
                        <small class="text-muted">
                            Enter the domain name (e.g., "example.com")
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Add Domain
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <!-- Ensure SweetAlert2 is loaded (omit if already included in your master layout) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#domainTable').DataTable({
            searching: false,
            paging: true,
            select: false,
            language: {
                paginate: {
                    previous: '<i class="fas fa-angle-double-left"></i>',
                    next: '<i class="fas fa-angle-double-right"></i>'
                }
            },
            processing: true,
            serverSide: true,
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'name',
                    name: 'name'
                },
                {
                    data: 'status',
                    name: 'status'
                },
                {
                    data: 'created_at',
                    name: 'created_at'
                },
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false
                },
            ]
        });

        // Focus on the input when modal opens
        $('#addDomainModal').on('shown.bs.modal', function() {
            $(this).find('input').focus();
        });

        // Handle Add Domain form submission (static demo)
        $('#addDomainForm').submit(function(e) {
            e.preventDefault();
            const domainName = $(this).find('input').val().trim();

            if (!domainName) {
                alert('Please enter a domain name');
                return;
            }

            $('#addDomainModal').modal('hide');
            $(this).trigger('reset');
            alert(`Domain "${domainName}" added successfully!`);
        });

        // SweetAlert2 confirmation for Delete button
        $(document).on('click', '.btn-delete', function(e) {
            e.preventDefault();

            // Find the <tr> that contains this delete button
            const row = $(this).closest('tr');
            // Grab the domain name from the second <td>
            const domainName = row.find('td:nth-child(2)').text().trim();

            Swal.fire({
                title: 'Are you sure?',
                text: `You won’t be able to revert deletion of "${domainName}"!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Remove the row from DataTable and redraw
                    const table = $('#domainTable').DataTable();
                    table.row(row).remove().draw();

                    Swal.fire(
                        'Deleted!',
                        `Domain "${domainName}" has been deleted.`,
                        'success'
                    );
                }
            });
        });
    });
    </script>
@endpush

