@extends('layouts.master')

@section('content')
<section class="content-body">
    <div class="container-fluid position-relative">
        <!-- page header -->
        <div class="d-flex flex-wrap align-items-center justify-content-between text-head mb-3">
            <h2 class="me-auto mb-0">Segment Management</h2>
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
                        <tbody>
                            @php
                                $segments = [
                                    ['Newsletter Subscribers','example.com','Active','2025-06-16 | 09:15 AM'],
                                    ['Cart Abandoners','shop.com','Paused','2025-05-20 | 06:45 PM'],
                                    ['Loyal Customers','example.com','Active','2025-04-10 | 11:00 AM'],
                                    ['New Visitors','demo.com','Active','2025-03-30 | 02:20 PM'],
                                    ['Blog Readers','example.com','Active','2025-03-15 | 10:10 AM'],
                                    ['Holiday Shoppers','shop.com','Paused','2024-12-21 | 04:05 PM'],
                                    ['VIP Members','example.com','Active','2025-01-05 | 01:50 PM'],
                                    ['Trial Users','saas.io','Active','2025-02-12 | 08:40 AM'],
                                    ['Churned Users','saas.io','Paused','2024-11-10 | 05:25 PM'],
                                    ['Survey Respondents','poll.com','Active','2025-04-02 | 09:35 AM'],
                                    ['Beta Testers','app.com','Active','2025-05-01 | 03:15 PM'],
                                    ['Mobile Users','app.com','Active','2025-03-14 | 07:55 AM'],
                                    ['Inactive Accounts','example.com','Paused','2024-10-22 | 07:05 PM'],
                                    ['Upsell Targets','shop.com','Active','2025-04-18 | 12:20 PM'],
                                    ['Downloaders','files.com','Active','2025-01-30 | 11:45 AM'],
                                    ['Event Attendees','events.io','Active','2025-06-01 | 04:30 PM'],
                                    ['Feedback Givers','poll.com','Active','2025-05-25 | 09:05 AM'],
                                    ['First-time Buyers','shop.com','Active','2025-06-10 | 10:50 AM'],
                                    ['Re-engagement List','example.com','Paused','2024-12-05 | 09:15 PM'],
                                    ['Upsell Success','shop.com','Active','2025-06-15 | 02:40 PM'],
                                ];
                            @endphp
                            @foreach ($segments as $index => $seg)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $seg[0] }}</td>
                                    <td>{{ $seg[1] }}</td>
                                    <td>
                                        <span class="badge {{ $seg[2]==='Active' ? 'bg-success' : 'bg-warning' }}">{{ $seg[2] }}</span>
                                    </td>
                                    <td>{{ $seg[3] }}</td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-primary me-1" title="View"><i class="far fa-eye"></i></a>
                                        <button type="button" class="btn btn-sm btn-danger btn-delete" title="Delete"><i class="far fa-trash"></i></button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(function () {
    // enhance static table if DataTables available
    if ($.fn.DataTable) {
        $('#segmentTable').DataTable({
            searching: false,
            paging: true,
            lengthChange: false,
            language: {
                paginate: {
                    previous: '<i class="fas fa-angle-double-left"></i>',
                    next: '<i class="fas fa-angle-double-right"></i>'
                }
            }
        });
    }

    // delete confirmation
    $(document).on('click', '.btn-delete', function () {
        const row = $(this).closest('tr');
        const name = row.find('td:nth-child(2)').text().trim();
        Swal.fire({
            title: 'Are you sure?',
            text: `You won’t be able to revert deletion of "${name}"!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                if ($.fn.DataTable) {
                    $('#segmentTable').DataTable().row(row).remove().draw();
                } else {
                    row.remove();
                }
                Swal.fire('Deleted!', `Segment "${name}" has been deleted.`, 'success');
            }
        });
    });
});
</script>
@endpush