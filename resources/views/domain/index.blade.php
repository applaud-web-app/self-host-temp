@extends('layouts.master')

@section('content')
    <section class="content-body">
        <div class="container-fluid position-relative">
            <div class="d-flex flex-wrap align-items-center justify-content-between text-head mb-3">
                <h2 class="me-auto mb-0">Domain Management</h2>
               
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDomainModal">
                    <i class="fas fa-plus pe-2"></i>Add Domain
                </button>
            </div>

            <div class="card h-auto mb-2">
                <div class="card-body p-3">
                    <div class="row g-2">
                        <div class="col-lg-4 col-md-4 col-12">
               

                    <div class="position-relative">
                                           <input type="text" id="searchName" class="form-control " placeholder="Search by name…">
                                            <div class="invalid-feedback"></div>
                                            <i class="far fa-search text-primary position-absolute top-50 translate-middle-y" style="right: 10px;"></i>
                                        </div>
               
                        </div>
                           <div class="col-lg-4 col-md-4 col-12">
                                <select id="filterStatus" class="form-select me-2 form-control" >
                                    <option value="">All Status</option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                        </div>
                         <div class="col-lg-4 col-md-4 col-12">
                            <button class="btn btn-danger light" id="resetFilter"><i class="fas fa-undo me-1"></i> Reset</button>
                         </div>
                    </div>
                </div>
            </div>
            <div class="card h-auto">
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
                                    <th>Import/Export</th>
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
                <form id="addDomainForm" method="POST" action="{{ route('domain.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Domain</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex align-items-center mb-3 bg-primary light p-2 rounded-1">
                            <div class="me-2">
                                <i class="fas fa-exclamation-triangle text-primary fs-6" style="background: #fff;width: 25px;height: 25px;border-radius: 50%;display: flex;justify-content: center;align-items: center;"></i>
                            </div>
                            <small class="text-white" style="line-height: 13px;">
                                Please enter the domain name.
                                <strong>Eg:</strong> example.com or www.example.com
                            </small>
                        </div>
                        <div class="mb-3">
                            <label for="domain_name" class="form-label">Domain Name</label>
                            <input type="text" name="domain_name" id="domain_name" class="form-control text-lowercase"
                                placeholder="example.com" required>
                            <div id="domainError" class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="submitDomain" class="btn btn-primary">Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#domainTable').DataTable({
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
                    url: "{{ route('domain.view') }}",
                    data: function(d) {
                        d.search_name   = $('#searchName').val();
                        d.filter_status = $('#filterStatus').val();
                    }
                },
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
                        data: 'import_export',  
                        name: 'import_export',
                        orderable: false,
                        searchable: false
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

            // redraw the table whenever the user types or changes the filter
            $('#searchName').on('keyup', function() {
                table.draw();
            });
            $('#filterStatus').on('change', function() {
                table.draw();
            });
        });
    </script>

    <script>
        $(function() {
            // Add a "domain" method for fully-qualified domain names
            $.validator.addMethod("domain", function(value, element) {
                // at least one label, dot, TLD; total length 3–253 chars
                return this.optional(element) ||
                    /^(?=.{3,253}$)([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/i.test(value);
            }, "Please enter a valid domain name (e.g. example.com)");

            // Initialize validation on your form
            $("#addDomainForm").validate({
                rules: {
                    domain_name: {
                        required: true,
                        domain: true,
                        // remote: {
                        //     url: "{{ route('domain.check') }}",
                        //     type: "post",
                        //     data: {
                        //         _token: "{{ csrf_token() }}",
                        //         domain_name: function() {
                        //             return $("#domain_name").val();
                        //         }
                        //     }
                        // }
                    }
                },
                messages: {
                    domain_name: {
                        required: "Domain name is required",
                        domain: "Invalid domain format",
                        remote: "This domain is already in use"
                    }
                },
                errorClass: "is-invalid",
                validClass: "is-valid",
                errorPlacement: function(error, element) {
                    error.addClass("invalid-feedback");
                    error.insertAfter(element);
                },
                highlight: function(element) {
                    $(element).addClass("is-invalid").removeClass("is-valid");
                },
                unhighlight: function(element) {
                    $(element).removeClass("is-invalid").addClass("is-valid");
                },
                submitHandler: function(form) {
                    var $btn = $('#submitDomain');
                    $btn.prop('disabled', true)
                        .html(
                            '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Processing...'
                            );
                    form.submit();
                }
            });

            // Reset validation & button state when modal closes
            $('#addDomainModal').on('hidden.bs.modal', function() {
                var form = $("#addDomainForm");
                form.validate().resetForm();
                form.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
                $('#submitDomain').prop('disabled', false).text('Add');
            });
        });
    </script>
    <script>
        $(function() {
            $(document).on('change', '.status_input', function() {
                let name     = $(this).data('name');
                let status = $(this).prop('checked') ? 1 : 0;

                $.ajax({
                url: "{{ route('domain.update-status') }}",
                type: "POST",
                data: {
                    _token:   "{{ csrf_token() }}",
                    name:        name,
                    status:    status
                },
                success: function(res) {
                    if (res.success) {
                    iziToast.success({
                        title:   'Success',
                        message: res.message,
                        position:'topRight'
                    });
                    } else {
                    iziToast.error({
                        title:   'Error',
                        message: res.message,
                        position:'topRight'
                    });
                    }
                },
                error: function() {
                    iziToast.error({
                    title:   'Error',
                    message: 'Unable to update status. Please try again.',
                    position:'topRight'
                    });
                }
                });
            });
        });
    </script>

    <script>
$(document).ready(function() {
    $('#resetFilter').click(function(event) {
        event.preventDefault(); // Prevent default button behavior (if any)

        // Reset the search input field
        $('#searchName').val('');

        // Reset the filter status dropdown
        $('#filterStatus').val('');

        // Reload the page after resetting the filters
        location.reload();
    });
});
    </script>

@endpush