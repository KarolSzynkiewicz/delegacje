<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Stocznia PRO - {{ $title ?? 'Visual Concept' }}</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-body: #0a0f1d;
            --bg-card: rgba(30, 41, 59, 0.6);
            --bg-input: rgba(15, 23, 42, 0.8);
            --primary: #3b82f6;
            --accent: #a855f7;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        body {
            background-color: var(--bg-body);
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(168, 85, 247, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(59, 130, 246, 0.15) 0%, transparent 40%);
            color: var(--text-main);
            min-height: 100vh;
            padding: 0;
            margin: 0;
        }

        /* --- KARTY (GLASSMORPHISM) --- */
        .card {
            background: var(--bg-card);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 24px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card:hover {
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .card-label {
            color: var(--text-muted);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
            display: block;
            font-weight: 600;
        }

        /* --- PRZYCISKI --- */
        .btn {
            padding: 10px 24px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
        }
        .btn-primary:hover {
            filter: brightness(1.1);
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5);
        }
        .btn-ghost {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-main);
            border: 1px solid var(--glass-border);
        }
        .btn-ghost:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        .btn-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        .btn-danger:hover {
            background: rgba(239, 68, 68, 0.2);
            color: var(--danger);
        }

        /* --- FORMULARZE --- */
        .form-control {
            background: var(--bg-input);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            color: white;
            padding: 12px 16px;
        }
        .form-control:focus {
            background: var(--bg-input);
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
            color: white;
            outline: none;
        }
        .form-control::placeholder {
            color: var(--text-muted);
        }
        .form-select.form-control {
            background-color: var(--bg-input);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23f1f5f9' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px 16px;
            padding-right: 40px;
            color: white;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
        }
        .form-select.form-control:focus {
            background-color: var(--bg-input);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23f1f5f9' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px 16px;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
            color: white;
        }
        .form-select.form-control option {
            background: var(--bg-input);
            color: var(--text-main);
            padding: 8px;
        }
        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }
        .form-label {
            margin-bottom: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-muted);
        }

        /* --- CHECKBOXES & RADIO --- */
        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--glass-border);
            transition: all 0.2s;
            margin-bottom: 8px;
        }
        .form-check:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.15);
        }
        .form-check input[type="checkbox"],
        .form-check input[type="radio"] {
            width: 20px;
            height: 20px;
            margin: 0;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            background: var(--bg-input);
            border: 2px solid var(--glass-border);
            border-radius: 6px;
            position: relative;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        .form-check input[type="checkbox"]:checked,
        .form-check input[type="radio"]:checked {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        .form-check input[type="checkbox"]:checked::after {
            content: '';
            position: absolute;
            left: 6px;
            top: 2px;
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }
        .form-check input[type="radio"] {
            border-radius: 50%;
        }
        .form-check input[type="radio"]:checked::after {
            content: '';
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 8px;
            height: 8px;
            background: white;
            border-radius: 50%;
        }
        .form-check label {
            color: var(--text-main);
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            margin: 0;
            flex-grow: 1;
        }
        .form-check input[type="checkbox"]:focus,
        .form-check input[type="radio"]:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        /* --- STATYSTYKI & BADGE --- */
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 4px;
            color: var(--text-main);
        }
        .badge {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .badge-success { 
            background: rgba(16, 185, 129, 0.15); 
            color: var(--success); 
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .badge-danger { 
            background: rgba(239, 68, 68, 0.15); 
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        .badge-info {
            background: rgba(59, 130, 246, 0.15);
            color: var(--primary);
            border: 1px solid rgba(59, 130, 246, 0.3);
        }
        .badge-warning {
            background: rgba(245, 158, 11, 0.15);
            color: var(--warning);
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        .badge-accent {
            background: rgba(168, 85, 247, 0.15);
            color: var(--accent);
            border: 1px solid rgba(168, 85, 247, 0.3);
        }

        /* --- TABELA --- */
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 8px;
        }
        .table thead th {
            color: var(--text-muted);
            font-size: 0.8rem;
            padding: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border: none;
        }
        .table tbody tr {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            transition: background 0.2s;
        }
        .table tbody tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        .table td {
            padding: 16px;
            border: none;
            color: var(--text-main);
        }
        .table td:first-child { 
            border-radius: 12px 0 0 12px; 
        }
        .table td:last-child { 
            border-radius: 0 12px 12px 0; 
        }

        .avatar-ui {
            width: 40px; 
            height: 40px; 
            border-radius: 10px;
            background: linear-gradient(135deg, var(--accent), var(--primary));
            display: flex; 
            align-items: center; 
            justify-content: center;
            font-weight: bold;
            color: white;
            font-size: 0.875rem;
        }

        /* --- ALERTY --- */
        .alert {
            padding: 16px;
            border-radius: 12px;
            border-left: 4px solid var(--primary);
            background: rgba(59, 130, 246, 0.05);
            display: flex; 
            align-items: center; 
            gap: 15px;
        }
        .alert-danger {
            border-left-color: var(--danger);
            background: rgba(239, 68, 68, 0.05);
        }
        .alert-success {
            border-left-color: var(--success);
            background: rgba(16, 185, 129, 0.05);
        }
        .alert-warning {
            border-left-color: var(--warning);
            background: rgba(245, 158, 11, 0.05);
        }

        /* --- PROGRESS BAR --- */
        .progress-ui {
            height: 6px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 9999px;
            overflow: hidden;
        }
        .progress-bar-ui {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            border-radius: 9999px;
            transition: width 0.3s ease;
            box-shadow: 0 0 10px rgba(59, 130, 246, 0.5);
        }

        /* --- NAVBAR --- */
        .navbar-ui {
            background: var(--bg-card);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--glass-border);
            padding: 1rem 0;
        }
        .navbar-ui .nav-link {
            color: var(--text-muted);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .navbar-ui .nav-link:hover,
        .navbar-ui .nav-link.active {
            color: var(--text-main);
            background: rgba(255, 255, 255, 0.05);
        }

        /* --- SEKCJE --- */
        h2.section-title {
            font-size: 1rem;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 25px;
            margin-top: 50px;
            border-bottom: 1px solid var(--glass-border);
            padding-bottom: 10px;
        }

        /* --- UTILITY --- */
        .text-primary {
            color: var(--primary) !important;
        }
        .text-accent {
            color: var(--accent) !important;
        }
        .text-muted {
            color: var(--text-muted) !important;
        }
    </style>
    
    @stack('styles')
</head>
<body>
    @yield('content')
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    @stack('scripts')
</body>
</html>
