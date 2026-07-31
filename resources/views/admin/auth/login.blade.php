<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Raines Africa</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>window.UI_API_LOGIN={{ app()->environment('local') ? 'true' : 'false' }};</script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #062a6a 0%, #0d47a1 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            max-width: 400px;
            width: 100%;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        .login-header {
            background: #062a6a;
            color: white;
            padding: 30px;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }
        .login-logo {
            height: 80px;
            max-width: 200px;
            margin-bottom: 20px;
            object-fit: contain;
        }
        @media (max-width: 576px) {
            .login-logo {
                height: 60px;
            }
        }
        .login-body {
            padding: 40px;
        }
        .btn-login {
            background: #062a6a;
            border: none;
            padding: 12px;
            font-weight: 600;
        }
        .btn-login:hover {
            background: #0d47a1;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <img src="{{ asset('light-logo.png') }}" alt="Raines Africa Logo" class="login-logo">
            <h3 class="mt-3">Raines Admin</h3>
            <p class="mb-0">Sign in to continue</p>
        </div>
        <div class="login-body">
            @if($errors->any())
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <form id="loginForm" method="POST" action="{{ route('admin.login.submit') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label"><i class="bi bi-envelope"></i> Email</label>
                    <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label"><i class="bi bi-lock"></i> Password</label>
                    <input type="password" name="password" id="password" class="form-control" required>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" name="remember" id="remember" class="form-check-input">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                <div id="errorMessage" class="alert alert-danger" style="display: none;"></div>
                <button type="submit" class="btn btn-primary btn-login w-100" id="loginButton">
                    <i class="bi bi-box-arrow-in-right"></i> Sign In
                </button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const button = document.getElementById('loginButton');
            const errorMessage = document.getElementById('errorMessage');
            const originalButtonHtml = button.innerHTML;

            form.addEventListener('submit', async function(e) {
                // If API login is disabled (production), do normal form submit
                if (!window.UI_API_LOGIN) {
                    return; // allow normal POST /admin/login with CSRF
                }

                e.preventDefault();

                const email = document.getElementById('email').value;
                const password = document.getElementById('password').value;
                const remember = document.getElementById('remember').checked;

                errorMessage.style.display = 'none';
                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Signing in...';

                try {
                    const response = await fetch('/api/backend/login', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ email, password, remember })
                    });

                    const data = await response.json().catch(() => ({}));

                    if (response.ok && data.access_token) {
                        localStorage.setItem('admin_token', data.access_token);
                        if (data.user) localStorage.setItem('admin_user', JSON.stringify(data.user));
                        window.location.href = `/admin/set-token?token=${encodeURIComponent(data.access_token)}`;
                        return;
                    }

                    errorMessage.textContent = (data && (data.message || data.error)) || 'Login failed. Falling back to secure form submission...';
                    errorMessage.style.display = 'block';

                    form.removeEventListener('submit', arguments.callee);
                    form.submit();
                } catch (error) {
                    form.removeEventListener('submit', arguments.callee);
                    form.submit();
                } finally {
                    button.disabled = false;
                    button.innerHTML = originalButtonHtml;
                }
            });
        });
    </script>
</body>
</html>
