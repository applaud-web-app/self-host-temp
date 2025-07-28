@extends('layouts.master')

@section('content')
<section class="content-body" id="segmentation_page">
    <div class="container-fluid position-relative">
         <div class="d-flex flex-wrap align-items-center justify-content-between text-head mb-3">
                <h2 class="me-auto mb-0">Segment Management</h2>
               
               <a href="{{ route('segmentation.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus pe-2"></i>Add Segment
                </a>
            </div>

             <div class="card h-auto mb-2">
                <div class="card-body p-3">
                    <div class="row g-2">
                        <div class="col-lg-3 col-md-3 col-12">
               

                    <div class="position-relative">
                                          <input type="text" id="searchName" class="form-control"
                   placeholder="Search by name…" >
                                            <div class="invalid-feedback"></div>
                                            <i class="far fa-search text-primary position-absolute top-50 translate-middle-y" style="right: 10px;"></i>
                                        </div>
               
                        </div>
                        <div class="col-lg-3 col-md-3 col-6">
                        <select id="filterStatus" class="form-select form-control " >
                            <option value="">All Status</option>
                            <option value="1">Active</option>
                            <option value="0">Paused</option>
                        </select>
                        </div>
                        <div class="col-lg-3 col-md-3 col-6">
                              <select id="filterType" class="form-select form-control ">
                <option value="">All Type</option>
                <option value="geo">Geo</option>
                <option value="device">Device</option>
            </select>
                        </div>
                        <div class="col-lg-3 col-md-3 col-12">
                            <button class="btn btn-danger light w-100" id="resetFilter"><i class="fas fa-undo me-1"></i> Reset</button>
                         </div>
                    </div>
                </div>
            </div>

 




        <!-- list card -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="segmentTable" class="table display">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Segment Name</th>
                                <th>Domain</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

   <!-- Segment details modal -->
    <div class="modal fade" id="segmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title d-flex align-items-center">
                        <i class="fas fa-project-diagram text-primary me-2"></i>
                        <span id="segmentModalLabel">Segment Details</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Loading State -->
                    <div id="segLoader" class="text-center py-5">
                        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3 text-muted">Loading segment details...</p>
                    </div>
                    
                    <!-- Error State -->
                    <div id="segError" class="alert alert-danger d-none">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <span id="errorMessage"></span>
                    </div>
                    
                    <!-- Content State -->
                    <div id="segContent" class="d-none">
                        <!-- Content will be inserted here by JavaScript -->
                    </div>
                </div>
                {{-- <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Close
                    </button>
                </div> --}}
            </div>
        </div>
    </div>

</section>
@endsection

@push('scripts')
<script>
$(function () {

    /* ------------------------------------------------------------------
       Server-side DataTable
    ------------------------------------------------------------------*/
    const table = $('#segmentTable').DataTable({
        searching    : false,
        paging       : true,
        lengthChange : false,
        processing   : true,
        serverSide   : true,
        language     : {
            paginate : {
                previous: '<i class="fas fa-angle-double-left"></i>',
                next    : '<i class="fas fa-angle-double-right"></i>'
            }
        },
        ajax : {
            url : "{{ route('segmentation.view') }}",
            data: d => {
                d.search_name   = $('#searchName').val().trim();
                d.filter_status = $('#filterStatus').val();
                d.filter_type   = $('#filterType').val();
            }
        },
        columns: [
            { data:'DT_RowIndex', name:'DT_RowIndex', orderable:false, searchable:false },
            { data:'name',        name:'name'       },
            { data:'domain',      name:'domain'     },
            { data:'type',        name:'type'       },
            { data:'status',      name:'status'     },
            { data:'created_at',  name:'created_at' },
            { data:'actions',     name:'actions', orderable:false, searchable:false }
        ]
    });

    /* redraw when any filter changes -------------------------------------- */
    $('#searchName, #filterStatus, #filterType').on('keyup change', () => table.draw());


    /* ------------------------------------------------------------------
       Toggle active / paused switch
    ------------------------------------------------------------------*/
    $(document).on('change', '.toggle-status', function () {
        const checkbox = $(this);
        const url = checkbox.data('url');
        const status = checkbox.prop('checked') ? 1 : 0;

        $.post(url, {
            _token: "{{ csrf_token() }}",
            status: status
        })
        .done((response) => {
            if (response.success) {
                iziToast.success({
                    title: 'Success',
                    message: response.message || 'Status updated successfully.',
                    position: 'topRight'
                });
            } else {
                // Server returned success=false
                checkbox.prop('checked', !status);
                iziToast.error({
                    title: 'Error',
                    message: response.message || 'Update failed.',
                    position: 'topRight'
                });
            }
        })
        .fail((xhr) => {
            checkbox.prop('checked', !status); // Revert toggle on error

            let errorMessage = 'Unable to update status. Please try again.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }

            iziToast.error({
                title: 'Error',
                message: errorMessage,
                position: 'topRight'
            });
        });
    });

    $(document).on('click', '.remove-btn', function () {
        const button = $(this);
        const url = button.data('url');
        const status = 2;

        // Save original content
        const originalHtml = button.html();

        // Disable button and show loading
        button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');

        $.post(url, {
            _token: "{{ csrf_token() }}",
            status: status
        })
        .done((response) => {
            if (response.success) {
                iziToast.success({
                    title: 'Success',
                    message: response.message || 'Segment removed successfully.',
                    position: 'topRight'
                });
                table.draw(); // Refresh DataTable
            } else {
                iziToast.error({
                    title: 'Error',
                    message: response.message || 'Remove failed.',
                    position: 'topRight'
                });
            }
        })
        .fail((xhr) => {
            let errorMessage = 'Unable to remove. Please try again.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }

            iziToast.error({
                title: 'Error',
                message: errorMessage,
                position: 'topRight'
            });
        })
        .always(() => {
            // Re-enable button and restore original text
            button.prop('disabled', false).html(originalHtml);
        });
    });

    /* ------------------------------------------------------------------
        View-details modal
    ------------------------------------------------------------------*/
    /* ------------------------------------------------------------------
    View-details modal
------------------------------------------------------------------*/
    $(document).on('click', '.view-btn', function() {
        const url = $(this).data('url');
        
        // Reset states
        $('#segLoader').show();
        $('#segError').addClass('d-none');
        $('#segContent').addClass('d-none').empty();
        
        // Show modal
        $('#segmentModal').modal('show');

        $.get(url)
            .done(function(res) {
                if (!res.success) {
                    throw new Error(res.message || 'Failed to load data');
                }

                const d = res.data;
                let html = `
                    <div class="segment-header mb-4 p-3 border dashbed">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-1 fw-bold">${d.name}</h4>
                                <div class="d-flex flex-wrap align-items-center text-muted">
                                    <span class="me-3">
                                        <i class="fas fa-link me-1"></i> ${d.domain}
                                    </span>
                                    <span class="me-3">
                                        <i class="far fa-calendar-alt me-1"></i> ${d.created_at}
                                    </span>
                                    <span class="badge bg-${d.status_badge}">
                                        <i class="fas fa-${d.status ? 'check' : 'pause'}-circle me-1"></i> ${d.status_text}
                                    </span>
                                </div>
                            </div>
                            <span class="badge bg-primary">
                                <i class="fas fa-${d.type === 'device' ? 'laptop-code' : 'globe'} me-1"></i> 
                                ${d.type === 'device' ? 'Device' : 'Geo'} Segment
                            </span>
                        </div>
                    </div>`;

                if (d.type === 'device') {
                    html += `
                    <div class="segment-section">
                        <h5 class="section-title">
                            <i class="fas fa-laptop-code text-primary me-2"></i>
                            Targeted Devices
                        </h5>
                        <div class="devices-container mt-3 d-flex gap-2 flex-wrap">`;
                    
                    if (d.devices && d.devices.length) {
                        d.devices.forEach(dev => {
                            const icon = {
                                desktop: 'fa-desktop',
                                tablet: 'fa-tablet-alt',
                                mobile: 'fa-mobile-alt',
                                other: 'fa-question-circle'
                            }[dev] || 'fa-question-circle';
                            
                            html += `
                            <div class="device-badge">
                                <span class="badge bg-light text-dark py-2 px-3 mb-2 fs-5">
                                    <i class="fas ${icon} me-1 text-primary"></i>
                                    <span class="text-capitalize">${dev}</span>
                                </span>
                            </div>`;
                        });
                    } else {
                        html += `<div class="alert alert-warning">No device rules configured</div>`;
                    }
                    
                    html += `</div></div>`;
                } else {
                    html += `
                    <div class="segment-section">
                        <h5 class="section-title">
                            <i class="fas fa-location text-primary me-2"></i>
                            Location Rules
                        </h5>
                        <div class="geo-rules-container mt-3 d-flex gap-2">`;
                    
                    if (d.geo && d.geo.length) {
                        d.geo.forEach(g => {
                            const badge = g.operator === 'equals' ? 'success' : 'warning';
                            const label = g.operator === 'equals' ? 'Only' : 'Exclude';
                            
                            html += `
                            <div class="geo-rule mb-0">
                                <span class="badge bg-${badge}-subtle text-${badge} p-2 bg-light d-flex align-items-center fs-6">
                                    <span class="me-1">${g.flag || '<i class="fas fa-globe-americas text-primary"></i>'}</span>
                                    <span class="text-dark">
                                        <strong class="text-warning">${label}</strong> ${g.country}${g.state ? ', ' + g.state : ''}
                                    </span>
                                </span>
                            </div>`;
                        });
                    } else {
                        html += `<div class="alert alert-warning">No geo rules configured</div>`;
                    }
                    
                    html += `</div></div>`;
                }

                $('#segContent').html(html);
                $('#segContent').removeClass('d-none');
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                let errorMessage = 'Unable to load segment details';
                
                if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                    errorMessage = jqXHR.responseJSON.message;
                } else if (errorThrown) {
                    errorMessage = errorThrown;
                }
                
                $('#errorMessage').text(errorMessage);
                $('#segError').removeClass('d-none');
            })
            .always(function() {
                $('#segLoader').hide();
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
