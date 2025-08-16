<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicon_io/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon_io/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon_io/favicon-16x16.png') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('/images/favicon.ico') }}">
    <title>License Required | {{ config('app.name', 'Our Platform') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #6366f1;
            --primary-lighter: #e0e7ff;
            --error: #dc2626;
            --error-light: #fee2e2;
            --text: #1f2937;
            --text-light: #6b7280;
            --bg: #f9fafb;
            --card-bg: #ffffff;
            --border: #e5e7eb;
            --border-dark: #d1d5db;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            line-height: 1.5;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(79, 70, 229, 0.03) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(220, 38, 38, 0.03) 0%, transparent 20%);
        }
        
        .license-card {
            width: 100%;
            max-width: 480px;
            background: var(--card-bg);
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--border-dark);
            animation: fadeIn 0.4s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 1.75rem;
            text-align: center;
            position: relative;
        }
        
        .card-header::after {
            content: '';
            position: absolute;
            bottom: -12px;
            left: 50%;
            transform: translateX(-50%);
            width: 24px;
            height: 24px;
            background: var(--card-bg);
            border-radius: 50%;
            border: 1px solid var(--border-dark);
            border-top-color: transparent;
            border-left-color: transparent;
            transform: translateX(-50%) rotate(45deg);
        }
        
        .card-header i {
            font-size: 2.25rem;
            margin-bottom: 1rem;
            display: inline-block;
            background: rgba(255,255,255,0.15);
            width: 72px;
            height: 72px;
            line-height: 72px;
            border-radius: 50%;
            backdrop-filter: blur(4px);
        }
        
        .card-header h1 {
            font-weight: 600;
            font-size: 1.375rem;
            letter-spacing: -0.5px;
        }
        
        .card-body {
            padding: 2.5rem 2rem;
            text-align: center;
        }
        
        .module-name {
            display: inline-block;
            background: var(--primary-lighter);
            color: var(--primary);
            font-weight: 600;
            padding: 0.5rem 1.25rem;
            border-radius: 8px;
            margin: 0.75rem 0;
            border: 1px solid rgba(79, 70, 229, 0.2);
        }
        
        .error-message {
            background: var(--error-light);
            color: var(--error);
            font-weight: 500;
            padding: 0.75rem;
            border-radius: 8px;
            margin: 1.25rem 0;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid rgba(220, 38, 38, 0.2);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--primary);
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            margin-top: 1.75rem;
            transition: all 0.2s ease;
            border: 1px solid var(--primary);
        }
        
        .btn:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            border-color: var(--primary-light);
        }
        
        .btn i {
            margin-right: 0.5rem;
        }
        
        .support-info {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
            font-size: 0.9rem;
            color: var(--text-light);
        }
        
        .support-info a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .support-info a:hover {
            color: var(--primary-light);
            text-decoration: underline;
        }
        
        @media (max-width: 480px) {
            body {
                padding: 1rem;
            }
            
            .card-body {
                padding: 2rem 1.5rem;
            }
            
            .card-header {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="license-card">
        <div class="card-header">
            <i class="fas fa-key"></i>
            <h1>License Required</h1>
        </div>
        
        <div class="card-body">
            <p>The <span class="module-name">{{ $moduleName ?? 'requested module' }}</span> requires a valid license for activation.</p>
            
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> Not available in your current plan
            </div>
            
            <p>Please contact your administrator or our support team to obtain the necessary license.</p>
            
            <a href="{{ url('/') }}" class="btn">
                <i class="fas fa-arrow-left"></i> Return to Dashboard
            </a>
            
            <div class="support-info">
                Need help? <a href="#">Contact support</a> or 
                <a href="#">view licensing options</a>
            </div>
        </div>
    </div>
</body>
</html>