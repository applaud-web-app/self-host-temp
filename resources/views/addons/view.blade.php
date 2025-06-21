@extends('layouts.master')

@push('styles')
    <style>
        .addon-card:hover {
            border-color: var(--primary);
        }

        .addon-icon-bg {
            width: 64px;
            height: 64px;
            display: flex;
            padding: 5px;
            overflow: hidden;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px auto;
            border-radius: 50%;
            border: 1px solid var(--primary);
        }

        .addon-card .addon-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 12px;
        }

        .addon-title {
            font-size: 1.18rem;
            font-weight: 600;
            margin-bottom: .35rem;
        }

        .addon-desc {
            font-size: .95rem;
            color: #666;
            min-height: 32px;
        }
    </style>
@endpush

@section('content')
    <section class="content-body">
        <div class="container-fluid position-relative">
            <div class="d-flex flex-wrap align-items-center justify-content-between text-head mb-3">
                <h2 class="me-auto mb-0">Addons & Modules</h2>
                <a href="{{ route('addons.upload') }}" class="btn btn-primary">
                    <i class="fas fa-plus pe-2"></i> Upload Module
                </a>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row gy-4">
                @isset($addons)
                    @foreach ($addons as $addon)
                        <div class="col-xl-3 col-md-4 col-sm-6">
                            <div class="card addon-card position-relative">
                                {{-- Badge: remote status or local state --}}
                                <span
                                    class="badge addon-badge
                    @if ($addon['is_local']) {{ $addon['local_status'] === 'installed' ? 'bg-success' : 'bg-info' }}
                    @else
                      {{ $addon['status'] === 'available' ? 'bg-success' : 'bg-secondary' }} @endif
                  ">
                                    @if ($addon['is_local'])
                                        {{ $addon['local_status'] === 'installed' ? 'Installed' : 'Uploaded' }}
                                    @else
                                        {{ ucfirst($addon['status']) }}
                                    @endif
                                </span>

                                <div class="card-body text-center d-flex flex-column">
                                    <div class="addon-icon-bg mb-3 mt-3">
                                        <img src="{{ $addon['icon'] }}" alt="{{ $addon['name'] }} icon" class="img-fluid">
                                    </div>

                                    <h5 class="addon-title">
                                        {{ $addon['name'] }}
                                        <small class="text-secondary">({{ $addon['version'] }})</small>
                                    </h5>

                                    <p class="addon-desc mb-3">
                                        {{ $addon['description'] ?: 'No description available.' }}
                                    </p>

                                    <div class="fw-bold fs-4 text-primary mb-3">
                                        {{ $addon['price'] }}
                                    </div>

                                    <div class="addon-actions">
                                        @if ($addon['is_local'])
                                            {{-- Already in DB --}}
                                            @if ($addon['local_status'] === 'installed')
                                                <span class="badge bg-success w-100">Activated</span>
                                            @else
                                                <button type="button" data-bs-toggle="modal" data-bs-target="#purchaseModal" class="btn btn-info btn-sm w-100">
                                                    <i class="fas fa-play me-1"></i> Activate
                                                </button>
                                            @endif
                                        @else
                                            {{-- Not yet uploaded locally --}}
                                            @if ($addon['status'] !== 'available')
                                                @php
                                                    $param = [
                                                        'key' => $addon['key'],
                                                        'name' => $addon['name'],
                                                        'version' => $addon['version'],
                                                    ];
                                                    $uploadUrl = encryptUrl(route('addons.upload'), $param);
                                                @endphp
                                                <a href="{{ $uploadUrl }}" class="btn btn-warning btn-sm w-100">
                                                    <i class="fas fa-upload me-1"></i> Upload
                                                </a>
                                            @else
                                                <a href="{{ $addon['purchase_url'] }}" target="_blank"
                                                    class="btn btn-primary btn-sm w-100">
                                                    <i class="fas fa-shopping-cart me-1"></i> Purchase Now
                                                </a>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endisset
            </div>
        </div>

        {{-- ===== Purchase Modal (unchanged) ===== --}}
        <div class="modal fade" id="purchaseModal" tabindex="-1" aria-labelledby="purchaseModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form id="purchaseForm" autocomplete="off">
                        <div class="modal-header">
                            <h5 class="modal-title" id="purchaseModalLabel">Activate Module</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="purchaseModuleName" name="module">
                            <div class="mb-3">
                                <label for="purchaseCode" class="form-label">Purchase Code <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="purchaseCode" name="purchase_code"
                                    placeholder="Enter your purchase code" required>
                            </div>
                            <div class="mb-3">
                                <label for="licenseKey" class="form-label">License Key <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="licenseKey" name="license_key"
                                    placeholder="Enter your license key" required>
                            </div>
                            <div class="mb-3">
                                <label for="installation_path" class="form-label">Installation Path <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="installation_path" name="installation_path"
                                    placeholder="Enter your installation path" value="{{ base_path() }}" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-unlock me-1"></i>
                                Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
