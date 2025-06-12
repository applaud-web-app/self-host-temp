@extends('layouts.master')

@section('content')
<section class="content-body">
    <div class="container-fluid">
        <div class="text-head">
            <h2 class="mb-3 me-auto">System Upgrade</h2>
        </div>

        <div class="row g-4">
            {{-- Current Version --}}
            <div class="col-md-6">
                <div class="card ">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Current Version</h5>
                         <span class="badge light badge-success">Up to Date</span>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Version:</strong> 1.0.0</p>
                        <p class="mb-1"><strong>Build:</strong> 1001</p>
                        <p class="mb-1"><strong>Release Date:</strong> 2024-01-15</p>
                        <p class="mb-3">
                            This version is stable and secure. No critical issues detected.
                        </p>
                       
                    </div>
                </div>
            </div>

            {{-- New Version Available --}}
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-download me-2"></i>New Version Available</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Version:</strong> 1.1.0</p>
                        <p class="mb-1"><strong>Build:</strong> 1102</p>
                        <p class="mb-1"><strong>Release Date:</strong> 2025-05-30</p>
                        <p class="mb-1"><strong>What's New:</strong></p>
                        <ul class="mb-3">
                            <li>Security patch for authentication module.</li>
                            <li>Performance optimizations for dashboard.</li>
                            <li>New analytics features added.</li>
                        </ul>
                        <form action="{{ route('settings.upgrade') }}" >
                            @csrf
                            <button type="submit" class="btn btn-success w-100" onclick="return confirm('Are you sure you want to upgrade to version 1.1.0?');">
                                <i class="fas fa-upload me-1"></i> Upgrade to 1.1.0
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

  

    </div>
</section>
@endsection
