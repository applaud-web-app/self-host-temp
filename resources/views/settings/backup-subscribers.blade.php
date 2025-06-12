@extends('layouts.master')

@section('content')
<section class="content-body">
    <div class="container-fluid">
        <div class="text-head">
            <h2 class="mb-3 me-auto">Backup Subscribers</h2>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card h-auto">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-database me-2"></i>Download New Backup</h5>
                    </div>
                    <div class="card-body">
                        <p>
                            Click the button below to generate and download a new CSV backup of all subscribers.
                        </p>
                        <a href="#" class="btn btn-primary" onclick="return confirm('Are you sure you want to backup subscribers?');">
                            <i class="fas fa-download me-1"></i> Download New Backup
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- List of Previous Backups (Static) --}}
        <div class="row ">
            <div class="col-md-12">
                <div class="card h-auto">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Previous Backups</h5>
                       
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                subscribers_backup_20240601_120000.csv
                                <a href="#" class="btn btn-sm btn-secondary" download>
                                    <i class="fas fa-download me-1"></i> Download
                                </a>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                subscribers_backup_20240520_093000.csv
                                <a href="#" class="btn btn-sm btn-secondary" download>
                                    <i class="fas fa-download me-1"></i> Download
                                </a>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                subscribers_backup_20240510_154500.csv
                                <a href="#" class="btn btn-sm btn-secondary" download>
                                    <i class="fas fa-download me-1"></i> Download
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>
@endsection
