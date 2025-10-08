@extends('layouts.master')

@push('styles')
    {{-- Optional extra styles if needed --}}
    <style>
        .info-list code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 90%;
        }
    </style>
@endpush

@section('content')
<section class="content-body">
    <div class="container-fluid position-relative">

        <div class="text-head mb-3 d-flex align-items-center">
            <h2 class="me-auto mb-0">Migration – Import Guide</h2>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="alert alert-primary my-2 mb-4">
                    <h5 class="alert-heading mb-2">Need Help Starting Your Migration?</h5>
                    <p class="mb-1">Please connect with us to get started:</p>
                    <ul class="mb-0">
                        <li>Email: <a href="mailto:support@example.com">support@example.com</a></li>
                        <li>Phone: +1 (555) 123-4567</li>
                        <li>Website: <a href="https://www.example.com/migrate-support" target="_blank">www.example.com/migrate-support</a></li>
                    </ul>
                    <p class="mt-2 mb-0">
                        Our migration team will guide you step-by-step to ensure a smooth transition.
                    </p>
                </div>

                <h5 class="mb-3">Required Subscriber Data</h5>
                <p class="text-muted mb-3">
                    To migrate your subscribers successfully, make sure your exported data contains the following fields:
                </p>
                <ul class="info-list list-unstyled mb-4">
                    <li><code>endpoint</code> – Push service endpoint URL</li>
                    <li><code>public_key</code> – VAPID public key</li>
                    <li><code>private_key</code> – VAPID private key</li>
                    <li><code>p256dh</code> – Subscription’s P-256 ECDH client key</li>
                    <li><code>auth</code> – Subscription’s authentication secret</li>
                    <li><code>ip_address</code> – (optional) last known IP address</li>
                    <li><code>status</code> – 1 = active, 0 = inactive</li>
                    <li><code>migrate_from</code> – Source system (e.g. <em>aplu</em>, <em>larapush</em>, default)</li>
                </ul>

                <h5 class="mb-3">Old Push Service Worker File</h5>
                <p class="text-muted">
                    Keep a copy of your previous push service worker file from your old vendor. It helps for:
                </p>
                <ul class="info-list list-unstyled mb-4">
                    <li>Validating payload formats</li>
                    <li>Testing button actions and click-through events</li>
                    <li>Comparing notification options (icons, images, requireInteraction, etc.)</li>
                </ul>

                <h5 class="mb-3">Other Important Info</h5>
                <ul class="info-list list-unstyled mb-4">
                    <li><strong>Key Grouping:</strong> Subscriptions are grouped by VAPID keypairs to avoid cross-signing issues.</li>
                    <li><strong>Health Checks:</strong> Expired or invalid endpoints will be marked inactive during delivery.</li>
                </ul>
            </div>
        </div>
    </div>
</section>
@endsection