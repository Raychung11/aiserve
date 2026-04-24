<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — STRHub AI</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f1f5f9; }
        .auth-panel { max-width: 420px; margin: auto; }
        .brand-panel { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); min-height: 100vh; }
    </style>
</head>
<body>
<div class="row g-0" style="min-height:100vh;">
    <div class="col-lg-6 brand-panel d-none d-lg-flex flex-column align-items-center justify-content-center text-white p-5">
        <i class="bi bi-house-heart-fill" style="font-size:4rem;margin-bottom:1.5rem;opacity:.9;"></i>
        <h2 class="fw-bold mb-3">STRHub AI</h2>
        <p class="text-center opacity-75 mb-0" style="max-width:320px;line-height:1.7;">
            Malaysia's intelligent rental OS — manage STR, mid-term, sublet, and corporate leasing from one platform.
        </p>
        <div class="row g-3 mt-4 text-center w-100" style="max-width:340px;">
            @foreach([['bi-buildings','Multi-Property'],['bi-shield-check','Compliance AI'],['bi-graph-up','ROI Engine']] as [$icon,$label])
            <div class="col-4">
                <div style="background:rgba(255,255,255,.12);border-radius:12px;padding:.75rem .5rem;">
                    <i class="bi {{ $icon }}" style="font-size:1.3rem;"></i>
                    <div style="font-size:.7rem;margin-top:.25rem;opacity:.85;">{{ $label }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="col-lg-6 d-flex align-items-center justify-content-center p-4">
        <div class="auth-panel w-100">
            <div class="text-center mb-4">
                <h4 class="fw-bold">Welcome back</h4>
                <p class="text-muted mb-0">Sign in to your STRHub AI account</p>
            </div>

            @if($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <form action="/login" method="POST" class="bg-white p-4 rounded-4 shadow-sm border">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label text-muted" for="remember">Remember me</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">Sign In</button>
            </form>

            <p class="text-center mt-3 text-muted" style="font-size:.875rem;">
                Don't have an account? <a href="/register" class="text-primary fw-semibold">Start free trial</a>
            </p>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
