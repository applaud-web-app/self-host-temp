@extends('layouts.master')

@section('content')
<section class="content-body">
    <div class="container-fluid position-relative">
        <div class="d-flex flex-wrap align-items-center justify-content-between text-head mb-3">
            <h2 class="me-auto mb-0">URL Management</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUrlModal">
                <i class="fas fa-plus pe-2"></i>Add URL
            </button>
        </div>
        <div class="card">
           
            <div class="card-body">
                <div class="table-responsive">
                    <table id="urlTable" class="table display">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>URL</th>
                                <th>Clicks</th>
                                <th>Last Accessed</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 1; $i <= 10; $i++)
                                @php
                                    $statuses = ['Active', 'Inactive'];
                                    $status = $statuses[array_rand($statuses)];
                                    $badgeClass = $status === 'Active' ? 'badge-success' : 'badge-danger';
                                @endphp
                                <tr>
                                    <td>{{ $i }}</td>
                                    <td>https://example.com/page{{ $i }}</td>
                                    <td>{{ rand(10, 500) }}</td>
                                    <td>{{ now()->subDays(rand(0, 20))->format('Y-m-d') }}</td>
                                    <td><span class="badge light {{ $badgeClass }}">{{ $status }}</span></td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-secondary"><i class="fas fa-edit me-1"></i>Edit</a>
                                        <a href="#" class="btn btn-sm btn-danger btn-delete"><i class="fas fa-trash-alt me-1"></i>Delete</a>
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Add URL Modal -->
<div class="modal fade" id="addUrlModal" tabindex="-1" aria-labelledby="addUrlModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUrlModalLabel">Add New URL</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addUrlForm" method="POST" action="#">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="urlInput" class="form-label">URL <span class="text-danger">*</span></label>
                        <input type="url" class="form-control" id="urlInput" name="url" placeholder="https://example.com" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add URL</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#urlTable').DataTable({  pagingType: 'simple_numbers',
    info: false,
    language: {
        paginate: {
            previous: '<i class="fas fa-chevron-left"></i>',
            next: '<i class="fas fa-chevron-right"></i>'
        }
    } });
        $('#addUrlModal').on('shown.bs.modal', function() {
            $(this).find('input').focus();
        });
        $('#addUrlForm').submit(function(e) {
            e.preventDefault();
            $('#addUrlModal').modal('hide');
            $(this).trigger('reset');
            alert('URL added!');
        });
        $(document).on('click', '.btn-delete', function(e) {
            e.preventDefault();
            $(this).closest('tr').remove();
        });
    });
</script>
@endpush
