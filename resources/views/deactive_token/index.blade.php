@extends('layouts.master')

@section('content')
    <section class="content-body">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center text-head">
                <h2 class="mb-3 me-auto">Inactive Push Tokens</h2>
                <div class="ms-auto">
                    <span class="badge bg-danger">Inactive Tokens: {{ $inactiveCount }}</span>
                </div>
            </div>

            @if(session('status'))
                <div class="alert alert-{{ session('status') }}">
                    {{ session('message') }}
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Why Clean Inactive Tokens?</h4>
                    <p class="text-danger">
                        <i class="fas fa-exclamation-triangle"></i> Inactive tokens are those that have been marked as invalid by Firebase (expired or unregistered) or tokens that users have explicitly unsubscribed from.
                        Regular cleanup improves database performance and reduces storage usage.
                    </p>
                </div>
            </div>

            @if($inactiveCount > 0)
                <div class="card mt-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="card-title text-white">Delete Deactive Subscriber</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-exclamation-circle"></i> Warning!</h5>
                            <p>
                                This will permanently delete all {{ $inactiveCount }} inactive tokens and their related data.
                                This action cannot be undone!
                            </p>
                        </div>
                        <form action="{{ route('deactive.remove-token') }}" method="POST" id="deleteForm">
                            @csrf
                            <div class="form-group">
                                <label for="confirmation">Type "DELETE" to confirm (case-sensitive):</label>
                                <input type="text" class="form-control" name="confirmation" id="confirmation" required
                                    onpaste="return false;" oncopy="return false;" oncut="return false;"
                                    autocomplete="off" spellcheck="false">
                                <small class="form-text text-muted">You must type the word exactly as shown.</small>
                            </div>
                            <button type="submit" class="btn btn-danger mt-3" id="deleteButton">
                                <i class="fas fa-trash"></i> Permanently Delete All Inactive Tokens
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <div class="card mt-4">
                    <div class="card-body text-center">
                        <h4><i class="fas fa-check-circle text-success"></i> No inactive tokens found!</h4>
                        <p class="mt-2">Your database is clean with no inactive push tokens.</p>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Disable paste, copy, cut events
        $('#confirmation').on('paste copy cut', function(e) {
            e.preventDefault();
            return false;
        });

        // Form submission handling
        $('#deleteForm').submit(function(e) {
            e.preventDefault();
            
            const confirmation = $('#confirmation').val();
            const button = $('#deleteButton');
            
            if (confirmation !== 'DELETE') {
                alert('Please type "DELETE" exactly (case-sensitive) in the confirmation box to proceed.');
                $('#confirmation').addClass('is-invalid').focus();
                return false;
            }
            
            if (confirm('Are you absolutely sure you want to permanently delete all {{ $inactiveCount }} inactive tokens?\n\nThis action cannot be undone!')) {
                // Disable button and show processing
                button.prop('disabled', true)
                      .html('<i class="fas fa-spinner fa-spin"></i> Deleting...');
                
                // Submit the form programmatically
                this.submit();
            }
        });

        // Remove error state when typing
        $('#confirmation').on('input', function() {
            $(this).removeClass('is-invalid');
        });
    });
</script>
@endpush