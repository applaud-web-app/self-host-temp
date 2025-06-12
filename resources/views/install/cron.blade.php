@extends('layouts.single-master')
@section('title', 'Cron & Queue Setup | Aplu')

@section('content')
<style>
    .cron-card {
        padding: 40px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        text-align: center;
    }

    .cron-heading {
        font-size: 1.8rem;
        font-weight: 600;
        margin-bottom: 1rem;
        color: #2d3748;
    }

    .cron-subtitle {
        font-size: 1.1rem;
        color: #4a5568;
        margin-bottom: 2rem;
        line-height: 1.5;
    }

    .code-block {
        background-color: #f7fafc;
        border: 1px solid #e2e8f0;
        padding: 12px;
        border-radius: 6px;
        text-align: left;
        font-family: monospace;
        font-size: 0.95rem;
        margin-bottom: 1rem;
        word-break: break-word;
        color: #2d3748;
        position: relative;
    }

    .copy-btn {
        position: absolute;
        top: 8px;
        right: 8px;
        font-size: 0.85rem;
        padding: 2px 6px;
        border: none;
        background: #edf2f7;
        border-radius: 4px;
        cursor: pointer;
    }
    .copy-btn:hover {
        background: #e2e8f0;
    }

    .section-title {
        font-weight: 600;
        color: #2d3748;
        margin-top: 1.5rem;
        margin-bottom: 0.5rem;
        text-align: left;
    }

    .instruction-text {
        text-align: left;
        color: #4a5568;
        margin-bottom: 1rem;
    }

    .btn-continue {
        margin-top: 2rem;
    }

    .note-text {
        font-size: 0.9rem;
        color: #718096;
        margin-top: 1rem;
        text-align: left;
    }
</style>

<section class="section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="cron-card card">
                    <h1 class="cron-heading">🕒 Cron & Queue Setup</h1>
                    <p class="cron-subtitle">
                        Configure scheduled tasks and queue workers to keep Aplu running smoothly.
                    </p>

                    <h4 class="section-title">1️⃣ Add Cron Job</h4>
                    <p class="instruction-text">
                        Add the following command to your server's cron jobs to schedule Laravel tasks:
                    </p>
                    <div class="code-block" id="cronBlock">
                        /opt/php/bin/php /var/www/my-app/artisan schedule:run >> /dev/null 2>&1
                        <button class="copy-btn" data-target="cronBlock">Copy</button>
                    </div>

                    <h4 class="section-title">2️⃣ Add Queue Worker (Supervisor)</h4>
                    <p class="instruction-text">
                        Add the following command to your supervisor configuration to process queue jobs:
                    </p>
                    <div class="code-block" id="queueBlock">
                        /opt/php/bin/php /var/www/my-app/artisan queue:work --sleep=3 --timeout=900 --tries=3 --max-jobs=1000 --memory=1024 --max-time=3600
                        <button class="copy-btn" data-target="queueBlock">Copy</button>
                    </div>

                    <p class="note-text">
                        🔔 <strong>Note:</strong> Replace <code>/opt/php/bin/php</code> and <code>/var/www/my-app</code> with your actual PHP path and project directory.
                    </p>

                    <form method="POST" action="{{ route('install.cron.post') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100">Continue</button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Copy-to-clipboard functionality
document.querySelectorAll('.copy-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const targetId = this.getAttribute('data-target');
        const codeText = document.getElementById(targetId).innerText.trim();
        navigator.clipboard.writeText(codeText).then(() => {
            this.innerText = 'Copied';
            setTimeout(() => { this.innerText = 'Copy'; }, 1500);
        });
    });
});

// Button processing state and redirect
document.getElementById('continueBtn').addEventListener('click', function() {
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = `
        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
        Processing...
    `;
    // Small delay to allow spinner to show
    setTimeout(() => {
        window.location.href = "{{ route('install.admin-setup') }}";
    }, 300);
});
</script>
@endsection
