@extends('layouts.master')

@push('styles')
    <style>
        .config-card {
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary);

        }

        .config-card:hover {
            transform: translateY(-3px);

        }

        .config-card-header {
            cursor: pointer;
            padding: 1rem 1.5rem;
            background-color: rgba(var(--primary-rgb), 0.03);
        }

        .config-card-header.collapsed .toggle-icon {
            transform: rotate(0deg);
        }

        .toggle-icon {
            transition: transform 0.3s ease;
            transform: rotate(180deg);
        }

        .step-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;

        }

        .step-number {
            display: inline-block;
            width: 28px;
            height: 28px;
            background: var(--primary);
            color: white;
            text-align: center;
            border-radius: 50%;
            line-height: 28px;
            margin-right: 10px;
            font-weight: bold;
        }

        .copy-btn {
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .domain-key {
            font-family: monospace;
            letter-spacing: 1px;
            background: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            width: 100%;
            display: inline-block;
            text-align: center;
            border: 1px dashed #b1b1b1;
            margin-right: 10px;
        }

        .download-btn {
            transition: all 0.3s ease;
        }

        .download-btn:hover {
            background-color: var(--primary) !important;
            color: white !important;
        }

        .warning-banner {
            background-color: rgba(255, 193, 7, 0.2);
            border-left: 3px solid #ffc107;
            padding: 0.75rem 1rem;
            border-radius: 4px;
            margin: 1rem 0;
        }
    </style>
@endpush

@section('content')
    <section class="content-body">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="site-content">
                            <h2 class="mb-0 me-3">Configuration</h2>
                            <span class="text-primary"><a class="text-primary" target="_blank" href="https://{{ $domain->name }}">[{{ $domain->name }}]</a></span>
                        </div>
                        <div class="site-content">
                            <button type="button" class="btn btn-secondary" id="verifySetup">
                                <i class="fas fa-check-circle me-2"></i> Verify Setup ?
                            </button>
                        </div>
                    </div>
                    <p class="text-muted">Clear cache after installing plugin or script from everywhere like Cloudflare, LiteSpeed, etc.</p>
                </div>
            </div>
            @php
                $param = ['domain' => $domain->name];
                $downloadSwEncryptUrl = encryptUrl(route('domain.download-sw'), $param);
                $generatePluginEncryptUrl = encryptUrl(route('domain.generate-plugin'), $param);
            @endphp
            <div class="row">
                <div class="col-lg-12">
                    <div class="card config-card h-auto">
                        <div class="config-card-header d-flex justify-content-between align-items-center"
                            data-bs-toggle="collapse" href="#wpPluginCard" aria-expanded="true">
                            <div class="d-flex align-items-center">
                                <img src="https://cdn-icons-png.flaticon.com/128/174/174881.png" alt="WordPress"
                                    width="32" class="me-3">
                                <h5 class="mb-0">WordPress Plugin Setup</h5>
                            </div>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse show" id="wpPluginCard">
                            <div class="card-body">
                                <div class="step-card">
                                    <h5 class="d-flex align-items-center mb-3">
                                        <span class="step-number">1</span>
                                        <span>Domain Key</span>
                                    </h5>
                                    <div class="d-flex align-items-center mb-3">
                                        <span class="domain-key" id="domainKey">
                                            "Please Click <span class="text-primary">Generate Now</span> to create a licence key..."
                                        </span>
                                        <button class="btn px-3 py-2 btn-sm copy-btn btn-outline-dark d-none">
                                            <i class="fas fa-copy me-1"></i> Copy
                                        </button>
                                    </div>
                                    <div class="warning-banner d-flex align-items-center justify-content-between">
                                        <div>
                                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                            <strong>Important:</strong> Do not share your domain key with anyone.
                                        </div>
                                        <button type="button" class="generate-key-btn text-dark border-0 bg-primary px-2 rounded-md text-white" data-url="{{$generatePluginEncryptUrl}}">Generate Now</button>
                                    </div>
                                </div>
                                <div class="step-card">
                                    <h5 class="d-flex align-items-center mb-3">
                                        <span class="step-number">2</span>
                                        <span>Download the Plugin</span>
                                    </h5>
                                    <a href="{{route('domain.download-plugin')}}" class="btn download-plugin btn-outline-primary w-100 py-2">
                                        <i class="fas fa-download me-2"></i> Download Aplu Push Plugin
                                    </a>
                                    <p class="mt-3 mb-0 text-muted">Once activated, the plugin is ready to use on your
                                        website.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="card config-card h-auto">
                        <div class="config-card-header d-flex justify-content-between align-items-center"
                            data-bs-toggle="collapse" href="#scriptCodeCard">
                            <div class="d-flex align-items-center">
                                <img src="https://cdn-icons-png.flaticon.com/128/10817/10817310.png" alt="Code"
                                    width="32" class="me-3">
                                <h5 class="mb-0">Manual Setup</h5>
                            </div>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse" id="scriptCodeCard">
                            <div class="card-body">
                                <div class="step-card">
                                    <h5 class="d-flex align-items-center mb-3">
                                        <span class="step-number">1</span>
                                        <span>Copy this code</span>
                                    </h5>
                                    <div class="d-flex align-items-center mb-3">
                                        <code class="domain-key">&lt;script
                                            src='{{ route('api.push.notify') }}'&gt;&lt;/script&gt;</code>
                                        <button class="btn px-3 py-2 btn-sm copy-btn btn-outline-dark">
                                            <i class="fas fa-copy me-1"></i> Copy
                                        </button>
                                    </div>
                                    <p class="text-muted mb-0">Paste this code in the &lt;head&gt; section of your website.
                                    </p>
                                </div>
                                <div class="step-card">
                                    <h5 class="d-flex align-items-center mb-3">
                                        <span class="step-number">2</span>
                                        <span>Download Service Worker</span>
                                    </h5>
                                    <button data-url="{{ $downloadSwEncryptUrl }}"
                                        class="btn download-btn btn-outline-primary w-100 py-2">
                                        <i class="fas fa-download me-2"></i> Download apluselfhost-messaging-sw.js
                                    </button>
                                    <p class="mt-3 mb-0 text-muted">Place this file in the root directory of your website.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize Bootstrap collapse with options for each card
            $('.config-card-header').on('click', function() {
                $(this).find('.toggle-icon').toggleClass('collapsed');
            });

            // Copy functionality
            $('.copy-btn').click(function() {
                const textToCopy = $(this).siblings('.domain-key').text() ||
                    $(this).siblings('code').text();

                navigator.clipboard.writeText(textToCopy).then(function() {
                    const originalText = $(this).html();
                    $(this).html('<i class="fas fa-check me-1"></i> Copied');
                    setTimeout(() => {
                        $(this).html(originalText);
                    }, 2000);
                }.bind(this));
            });

            $('.download-btn').click(function() {
                // btn processing on click
                $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Processing...');
                const url = $(this).data('url');
                if (url) {
                    window.location.href = url;
                } else {
                    alert('Download URL is not set.');
                }
                setTimeout(() => {
                    $(this).prop('disabled', false).html(
                        '<i class="fas fa-download me-2"></i> Download apluselfhost-messaging-sw.js'
                        );
                }, 1000);
            });

            $('.download-plugin').click(function() {
                // btn processing on click
                $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Processing...');

                const url = $(this).data('url');
                
                if (url) {
                    // Show a success message with IziToast
                    iziToast.success({
                        title: 'Success',
                        message: 'Preparing your download...',
                        position: 'topRight',
                        timeout: 3000, // duration in ms
                    });

                    // Redirect to the download URL
                    window.location.href = url;
                } else {
                    // Show an error message with IziToast if the URL is not set
                    iziToast.error({
                        title: 'Error',
                        message: 'Download URL is not set.',
                        position: 'topRight',
                        timeout: 3000, // duration in ms
                    });
                }

                // After a short delay, reset the button state
                setTimeout(() => {
                    $(this).prop('disabled', false).html(
                        '<i class="fas fa-download me-2"></i> Download apluselfhost-messaging-sw.js'
                    );
                }, 1000);
            });

        });
    </script>
    <script>
        $(function() {
            $('.generate-key-btn').on('click', function() {
                const $btn = $(this);
                const url = $btn.data('url');
                const token = '{{ csrf_token() }}';
                if (!url) return iziToast.error({
                    message: 'Missing URL',
                    position: 'topRight'
                });

                // 1) show spinner
                $btn.prop('disabled', true)
                    .html('<i class="fas fa-spinner fa-spin me-1"></i> Generating…');

                // 2) AJAX
                $.ajax({
                    url: url,
                    method: 'post', // or 'POST' if you prefer
                    dataType: 'json',
                    headers: {
                    'X-CSRF-TOKEN': token
                    },
                    success(res) {
                        if (res.success && res.key) {
                            // update the key on screen
                            $('#domainKey').text(res.key);

                            // reveal the copy button
                            $('#domainKey').siblings('.copy-btn').removeClass('d-none');

                            // remove the button so you cannot regenerate
                            $btn.remove();
                            iziToast.success({
                                message: res.message,
                                position: 'topRight'
                            });
                        } else {
                            iziToast.error({
                                message: res.message || 'Failed to generate key.',
                                position: 'topRight'
                            });
                            $btn.prop('disabled', false).text('Generate Now');
                        }
                    },
                    error(xhr) {
                        const msg = xhr.responseJSON?.message || 'Server error generating key.';
                        iziToast.error({
                            message: msg,
                            position: 'topRight'
                        });
                        $btn.prop('disabled', false).text('Generate Now');
                    }
                });
            });
            
            $('#verifySetup').on('click', function() {
                const domain = '{{$domain->name}}';
                const $btn = $(this);  // Reference to the button

                // Disable the button and show loading text
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Verifying...');

                $.ajax({
                    url: '{{ route("domain.verify-integration") }}',
                    method: 'GET',
                    data: { domain: domain },
                    success(res) {
                        if (res.success) {
                            iziToast.success({
                                message: res.success,
                                position: 'topRight'
                            });

                            // After success, disable the button permanently
                            $btn.prop('disabled', true).html('<i class="fas fa-check-circle me-2"></i> Verified');
                        } else {
                            iziToast.error({
                                message: res.error,
                                position: 'topRight'
                            });

                            // If there's an error, re-enable the button
                            $btn.prop('disabled', false).html('Verify Setup');
                        }
                    },
                    error(xhr) {
                        const msg = xhr.responseJSON?.message || 'There was an error verifying the integration.';
                        iziToast.error({
                            message: msg,
                            position: 'topRight'
                        });

                        // If error occurs, re-enable the button
                        $btn.prop('disabled', false).html('Verify Setup');
                    }
                });
            });

        });
    </script>
@endpush