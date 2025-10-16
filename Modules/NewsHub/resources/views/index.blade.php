@extends('layouts.master')

@section('content')
    <section class="content-body">
        <div class="container-fluid position-relative">
            <div class="d-flex flex-wrap align-items-center justify-content-between text-head mb-3">
                <h2 class="me-auto mb-0">News Hub</h2>
            </div>

            <div class="card h-auto">
                <div class="card-body p-3">
                    <!-- Filter Form -->
                    <form id="filter-form" method="GET" autocomplete="off">
                        <div class="row g-3">
                            <div class="col-lg-3">
                                <div class="position-relative">
                                    <input type="text" id="search_term" name="search_term" class="form-control"
                                        placeholder="Search by Domain or Title...">
                                    <div class="invalid-feedback"></div>
                                    <i class="far fa-search text-primary position-absolute top-50 translate-middle-y"
                                        style="right: 10px;"></i>
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
                                    @foreach ($domains as $d)
                                        <option value="{{ $d->name }}">{{ $d->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3">
                                <button class="btn btn-danger light w-100" id="resetFilter"><i class="fas fa-undo me-1"></i>
                                    Reset</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- News Hub Table -->
            <div class="card h-auto">
                <div class="card-body p-3">
                    <div class="table-responsive">
                        <table id="newsHubTable" class="table display">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Domain</th>
                                    {{-- <th>Roll Title</th> --}}
                                    <th>Roll Status</th>
                                    <th>Roll Action</th>
                                    {{-- <th>Flask Title</th> --}}
                                    <th>Flask Status</th>
                                    <th>Flask Action</th>
                                    <th>Slider Status</th>
                                    <th>Slider Action</th>
                                    <th>Integrate</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Integration Modal -->
    <div class="modal fade" id="openIntegrationModal" tabindex="-1" aria-labelledby="openIntegrationModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="openIntegrationModalLabel">Integrate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>
                    Boom! 🎉  
                    <span class="text-primary fw-bold">We copied your script automatically.</span>  
                    Now just <span class="text-primary fw-bold">paste this code</span> into the 
                    <span class="text-primary fw-bold">&lt;head&gt;</span> section of your website to integrate the News Roll.
                    </p>
                    <p>
                    That’s it — your site will now load all the 
                    <span class="text-primary fw-bold">News Hub</span> magic automatically.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            var table = $('#newsHubTable').DataTable({
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
                    url: '{{ route('news-hub.index') }}',
                    data: function(d) {
                        d.search_term = $('#search_term').val();
                        d.status = $('#status').val();
                        d.site_web = $('#site_web').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false
                    },
                    {
                        data: 'domain'
                    },
                    // { data: 'nr_title' },
                    {
                        data: 'nr_status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'nr_action',
                        orderable: false,
                        searchable: false
                    },
                    // { data: 'nf_title' },
                    {
                        data: 'nf_status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'nf_action',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'nbs_status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'nbs_action',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'integrate',
                        orderable: false,
                        searchable: false
                    }
                ]
            });

            $('#search_term, #status, #site_web').on('change keyup', function() {
                table.draw();
            });

            $('#resetFilter').on('click', function(e) {
                e.preventDefault();
                $('#filter-form')[0].reset();
                table.draw();
            });

            const csrf = '{{ csrf_token() }}';

            // Unified toggle (roll + flask)
            $(document).on('change', '.js-toggle', function() {
                const $el = $(this);
                const id = $el.data('id');
                const type = $el.data('type'); // roll | flask
                const checked = $el.is(':checked') ? 1 : 0;

                if (!id || !type) {
                    return;
                }

                $.ajax({
                        url: '{{ route('news-hub.toggle.status') }}',
                        type: 'POST',
                        data: {
                            _token: csrf,
                            id: id,
                            type: type,
                            status: checked
                        },
                    })
                    .done((res) => {
                        if (res && res.ok) {
                            iziToast.success({
                                title: 'success',
                                message: 'Status updated successfully!',
                                position: 'topRight'
                            });
                        } else {
                            // if response is not ok
                            $el.prop('checked', !checked);
                            iziToast.error({
                                title: 'Error',
                                message: 'Failed to update status (invalid response).',
                                position: 'topRight'
                            });
                        }
                    })
                    .fail(() => {
                        $el.prop('checked', !checked);
                        iziToast.error({
                            title: 'Error',
                            message: 'Failed to update status (server error).',
                            position: 'topRight'
                        });
                    });
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            $(document).on('click', '.js-copy', function(e) {
                e.preventDefault();
                var copyText = $(this).data('clipboard-text');
                navigator.clipboard.writeText(copyText);
                iziToast.success({
                    title: 'Copied',
                    message: 'Code copied to clipboard',
                    position: 'topRight'
                });
            });
        })
    </script>
@endpush