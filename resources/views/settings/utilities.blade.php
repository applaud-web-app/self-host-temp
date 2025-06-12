{{-- resources/views/settings/utilities.blade.php --}}
@extends('layouts.master')

@section('content')
<section class="content-body">
    <div class="container-fluid">
        <div class="text-head ">
            <h2 class="mb-3 me-auto">Utilities</h2>
          
        </div>

        {{-- Utility Actions --}}
        <div class="row g-4">
            {{-- Purge Cache --}}
            <div class="col-md-4">
                <div class="card h-auto ">
                    <div class="card-header ">
                        <h5 class="mb-0"><i class="fas fa-broom me-2"></i>Purge Cache</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-3">
                            Remove all cached data (views, routes, configuration). Use this when you’ve updated code or configs.
                        </p>
                        <form action="{{ route('settings.utilities.purge-cache') }}" method="POST" onsubmit="return confirm('Are you sure you want to purge all cache?');">
                            @csrf
                            <button type="submit" class="btn btn-danger w-100"><i class="fas fa-trash-alt me-1"></i>Purge Cache</button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Clear Log --}}
            <div class="col-md-4">
                <div class="card h-auto ">
                    <div class="card-header ">
                        <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Clear Log</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-3">
                            Delete application log files to free up space. Make sure you’ve archived any important logs before clearing.
                        </p>
                        <form action="{{ route('settings.utilities.clear-log') }}" method="POST" onsubmit="return confirm('Are you sure you want to clear all logs?');">
                            @csrf
                            <button type="submit" class="btn btn-warning w-100"><i class="fas fa-eraser me-1"></i>Clear Log</button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Generate Cache --}}
            <div class="col-md-4">
                <div class="card h-auto ">
                    <div class="card-header ">
                        <h5 class="mb-0"><i class="fas fa-sync-alt me-2"></i>Generate Cache</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-3">
                            Rebuild configuration, route, and view caches. Recommended after configuration changes.
                        </p>
                        <form action="{{ route('settings.utilities.make-cache') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success w-100"><i class="fas fa-cogs me-1"></i>Generate Cache</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
