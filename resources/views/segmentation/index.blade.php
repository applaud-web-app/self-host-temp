@extends('layouts.master')

@section('content')
<section class="content-body">
    <div class="container-fluid position-relative">

        <!-- page header -->
        <div class="d-flex flex-wrap align-items-center justify-content-between text-head mb-3">
            <h2 class="me-auto mb-0">Segment Management</h2>

            <!-- search / filter controls -->
            <input type="text" id="searchName" class="form-control me-2"
                   placeholder="Search by name…" style="max-width:150px;">
            <select id="filterStatus" class="form-select form-control me-2" style="max-width:150px;">
                <option value="">All Status</option>
                <option value="1">Active</option>
                <option value="0">Paused</option>
            </select>
            <select id="filterType" class="form-select form-control me-2" style="max-width:150px;">
                <option value="">All Type</option>
                <option value="geo">Geo</option>
                <option value="device">Device</option>
            </select>

            <!-- separate create page -->
            <a href="{{ route('segmentation.create') }}" class="btn btn-primary">
                <i class="fas fa-plus pe-2"></i>Add Segment
            </a>
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
        const id     = $(this).data('id');
        const status = $(this).prop('checked') ? 1 : 0;

        $.post("{{ route('segmentation.update-status') }}", {
            _token : "{{ csrf_token() }}",
            id     : id,
            status : status
        }).fail(() => {
            /* revert UI if the call fails */
            $(this).prop('checked', !status);
            iziToast.error({
                title   : 'Error',
                message : 'Unable to update status. Please try again.',
                position: 'topRight'
            });
        });
    });
});
</script>
@endpush
