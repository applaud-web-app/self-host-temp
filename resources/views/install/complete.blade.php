@extends('layouts.single-master')
@section('title', 'Installation Complete | Aplu')

@section('content')
<style>
    .complete-card {
        padding: 40px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .complete-heading {
        font-size: 1.8rem;
        font-weight: 600;
        margin-bottom: 1rem;
        color: #2d3748;
    }

    .complete-subtitle {
        font-size: 1.1rem;
        color: #4a5568;
        margin-bottom: 2rem;
        line-height: 1.5;
    }

    .info-text {
        font-size: 1rem;
        color: #4a5568;
        margin-bottom: 0.5rem;
    }

    .info-strong {
        font-weight: 600;
        color: #2d3748;
    }

    .btn-dashboard {
        margin-top: 2rem;
    }

    .success-note {
        margin-top: 1.5rem;
        font-size: 0.95rem;
        color: #38a169;
        font-weight: 500;
        margin-bottom: 0;
    }

    /* Confetti styles (hidden by default until script runs) */
    #confetti-canvas {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 9999;
    }
</style>

<canvas id="confetti-canvas"></canvas>

<section class="section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="complete-card card">
                    <img src="{{ asset('images/pass.png') }}" alt="Success" width="100" height="100" class="mx-auto mb-3">
                    <h1 class="complete-heading">🎉 Welcome to Aplu! 🎉</h1>
                    <p class="complete-subtitle">
                        Your Aplu self-hosted platform has been successfully installed. Enjoy your app!
                    </p>

                    <p class="info-text">Your Super Admin email: <span class="info-strong">{{ $admin_email }}</span></p>
                    <p class="info-text mb-4">Your Super Admin password: <span class="info-strong">{{ $admin_password }}</span></p>

                    <a href="{{route('home')}}" id="dashboardBtn" class="btn btn-primary w-100 btn-dashboard">
                        Go to Dashboard
                    </a>

                    <p class="success-note">
                        ✅ If you followed all the steps correctly, everything should work perfectly.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// --------- Confetti Animation -----------
document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('confetti-canvas');
    const ctx = canvas.getContext('2d');
    let W = window.innerWidth;
    let H = window.innerHeight;
    canvas.width = W;
    canvas.height = H;

    const colors = ['#f44336', '#e91e63', '#9c27b0', '#673ab7', '#3f51b5', '#2196f3', '#03a9f4', '#00bcd4', '#009688', '#4CAF50', '#8BC34A', '#CDDC39', '#FFEB3B', '#FFC107', '#FF9800', '#FF5722'];
    const particles = [];

    // Create initial particles
    for (let i = 0; i < 150; i++) {
        particles.push({
            x: Math.random() * W,
            y: Math.random() * H - H,
            size: Math.random() * 8 + 3,
            color: colors[Math.floor(Math.random() * colors.length)],
            speed: Math.random() * 3 + 2,
            angle: Math.random() * Math.PI * 2,
            rotationSpeed: Math.random() * 0.01,
            shape: Math.random() > 0.5 ? 'circle' : 'rect'
        });
    }

    function animateConfetti() {
        ctx.clearRect(0, 0, W, H);

        particles.forEach(p => {
            ctx.save();
            ctx.translate(p.x, p.y);
            ctx.rotate(p.angle);
            ctx.fillStyle = p.color;

            if (p.shape === 'circle') {
                ctx.beginPath();
                ctx.arc(0, 0, p.size / 2, 0, Math.PI * 2);
                ctx.fill();
            } else {
                ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size);
            }
            ctx.restore();

            p.y += p.speed;
            p.angle += p.rotationSpeed;

            if (p.y > H) {
                p.y = -p.size;
                p.x = Math.random() * W;
            }
        });

        requestAnimationFrame(animateConfetti);
    }

    animateConfetti();

    window.addEventListener('resize', function() {
        W = window.innerWidth;
        H = window.innerHeight;
        canvas.width = W;
        canvas.height = H;
    });
});
// --------- End Confetti Animation ---------

// --------- Button Processing State -----------
// document.getElementById('dashboardBtn').addEventListener('click', function() {
//     const btn = this;
//     btn.disabled = true;
//     btn.innerHTML = `
//         <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
//         Redirecting...
//     `;
//     // Redirect after a short delay to allow spinner to show
//     setTimeout(function() {
//         window.location.href = "{{ route('dashboard.view') }}";
//     }, 300);
// });
// --------- End Button Processing State ---------
</script>
@endsection
