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
                    <h2 class="alert-heading mb-2">Need Help Starting Your Migration?</h2>
                    <p class="mb-1">Please connect with us to get started:</p>
                    <ul class="mb-0">
                        <li>Email: <a href="mailto:info@aplu.io">info@aplu.io</a></li>
                        <li>Phone: <a href="tel:+91-9997526894">+91-9997526894</a></li>
                    </ul>
                    <p class="mt-2 mb-0">
                        Our migration team will guide you step-by-step to ensure a smooth transition.
                    </p>
                </div>

                <h2 class="mb-3">Required Subscriber Data</h2>
                <p class="text-muted mb-3">
                    To migrate your subscribers successfully, make sure your exported data contains the following fields:
                </p>
                <ul class="info-list list-unstyled mb-4">
                    <li><code>Endpoint</code> – Push service endpoint URL</li>
                    <li><code>Public Key</code> – VAPID public key</li>
                    <li><code>Private Key</code> – VAPID private key</li>
                    <li><code>P256dh</code> – Subscription’s P-256 ECDH client key</li>
                    <li><code>Auth</code> – Subscription’s authentication secret</li>
                    <li><code>IP Address</code> – (optional) last known IP address</li>
                    <li><code>Migrate From</code> – Source system (e.g. <em>aplu</em>, <em>larapush</em>, etc)</li>
                </ul>

                <h2 class="mb-3">Old Push Service Worker File</h2>
                <p class="text-muted">
                    Keep a copy of your previous push service worker file from your old vendor. It helps for:
                </p>
                <ul class="info-list list-unstyled mb-4">
                    <li>1. Validating payload formats</li>
                    <li>2. Testing button actions and click-through events</li>
                    <li>3. Comparing notification options (icons, images, requireInteraction, etc.)</li>
                </ul>
            </div>
        </div>
    </div>
</section>
@endsection