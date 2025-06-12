@extends('layouts.single-master')
@section('title', 'Welcome | Self Hosted')

@section('content')
<style>
    .welcome-card {
        padding: 40px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        text-align: center;
    }

    .welcome-heading {
        font-size: 1.8rem;
        font-weight: 600;
        margin-bottom: 1rem;
        color: #2d3748;
    }

    .welcome-subtitle {
        font-size: 1.1rem;
        color: #4a5568;
        margin-bottom: 2rem;
        line-height: 1.5;
    }

    .divider {
        width: 80px;
        height: 4px;
        background: var(--bs-primary);
        margin: 1.5rem auto;
        border-radius: 2px;
    }

    .steps-list {
        text-align: left;
        margin: 2rem 0;
        padding-left: 1.5rem;
    }

    .steps-list li {
        margin-bottom: 1rem;
        color: #4a5568;
    }

    .steps-list strong {
        color: #2d3748;
    }

    .support-links {
        margin-top: 2rem;
        font-size: 0.95rem;
        color: #718096;
    }

    .support-links a {
        color: var(--bs-primary);
        text-decoration: none;
        transition: all 0.2s;
    }

    .support-links a:hover {
        text-decoration: underline;
    }
</style>

<section class="section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="welcome-card card">
                    <h1 class="welcome-heading">👋 Welcome to Aplu</h1>
                    <p class="welcome-subtitle">
                        Thank you for choosing Aplu's self-hosted solution.
                        Let's get your platform up and running in just a few steps.
                    </p>

                    <div class="divider"></div>

                    <h3 class="mb-3">Setup Instructions</h3>
                    <ol class="steps-list">
                        <li><strong>Environment Setup:</strong> Configure your .env file with database and mail settings</li>
                        <li><strong>License Verification:</strong> Activate with your license key</li>
                        <li><strong>Database Setup:</strong> Establish your database connection</li>
                        <li><strong>Background Services:</strong> Configure cron and queue workers</li>
                        <li><strong>Finalize:</strong> Complete installation and launch</li>
                    </ol>

                    <button id="beginInstallBtn" class="btn btn-primary w-100">
                        Begin Installation
                    </button>

                    <div class="support-links">
                        Need help? <a href="https://aplu.io/docs" target="_blank">View docs</a> or
                        <a href="https://aplu.io/support" target="_blank">contact support</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Button processing state and redirect
document.getElementById('beginInstallBtn').addEventListener('click', function() {
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = `
        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
        Starting...
    `;
    setTimeout(() => {
        window.location.href = "{{ route('install.environment') }}";
    }, 300);
});
</script>
@endsection
