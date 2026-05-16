<h2 class="text-2xl font-bold text-gray-900 mb-1">Welcome back</h2>
<p class="text-sm text-gray-500 mb-6">Sign in to your eBizMedic account</p>

<form method="POST" action="<?= url('login') ?>" class="space-y-4">
    <?= csrf_field() ?>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Email address</label>
        <div class="relative">
            <i class="fa-regular fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input type="email" name="email" value="<?= old('email') ?>" required autocomplete="email"
                   placeholder="you@example.com"
                   class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>
    </div>

    <div>
        <div class="flex items-center justify-between mb-1.5">
            <label class="block text-sm font-medium text-gray-700">Password</label>
        </div>
        <div class="relative">
            <i class="fa-solid fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input type="password" name="password" required autocomplete="current-password"
                   placeholder="••••••••"
                   class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>
    </div>

    <button type="submit"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg transition-colors text-sm mt-2">
        Sign In
    </button>
</form>

<p class="text-center text-sm text-gray-500 mt-6">
    Don't have an account?
    <a href="<?= url('register') ?>" class="text-blue-600 font-medium hover:underline">Sign up</a>
</p>
