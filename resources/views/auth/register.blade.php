<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — STRHub AI</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f1f5f9; }
        .auth-panel { max-width: 520px; margin: auto; }
    </style>
</head>
<body>
<div class="d-flex align-items-center justify-content-center py-5 px-3" style="min-height:100vh;">
    <div class="auth-panel w-100">
        <div class="text-center mb-4">
            <a href="/login" class="text-muted text-decoration-none" style="font-size:.875rem;"><i class="bi bi-arrow-left"></i> Back to login</a>
            <h4 class="fw-bold mt-3">Start your free trial</h4>
            <p class="text-muted mb-0">14 days free · No credit card required</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <form action="/register" method="POST" class="bg-white p-4 rounded-4 shadow-sm border">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold">Company / Agency Name</label>
                <input type="text" name="company_name" class="form-control" value="{{ old('company_name') }}" placeholder="e.g. SLV Group" required>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Your Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Phone</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="+60 12-345 6789" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Password</label>
                    <input type="password" name="password" class="form-control" required minlength="8">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Plan</label>
                <div class="row g-2">
                    @foreach([['starter','Starter','5 properties · RM 500/mo'],['growth','Growth','20 properties · RM 1,500/mo'],['enterprise','Enterprise','Unlimited · RM 4,000/mo']] as [$val,$label,$desc])
                    <div class="col-12">
                        <label class="d-flex align-items-center gap-3 border rounded-3 px-3 py-2 cursor-pointer {{ old('plan') === $val ? 'border-primary' : '' }}" style="cursor:pointer;">
                            <input type="radio" name="plan" value="{{ $val }}" class="form-check-input" {{ old('plan', 'starter') === $val ? 'checked' : '' }}>
                            <div>
                                <div class="fw-semibold" style="font-size:.875rem;">{{ $label }}</div>
                                <div class="text-muted" style="font-size:.75rem;">{{ $desc }}</div>
                            </div>
                        </label>
                    </div>
                    @endforeach
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2">Create Account & Start Trial</button>
            <p class="text-center text-muted mt-3 mb-0" style="font-size:.75rem;">
                By registering you agree to STRHub AI's terms of service.
            </p>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
