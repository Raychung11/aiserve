<h2 class="text-2xl font-bold text-gray-900 mb-1">Create an account</h2>
<p class="text-sm text-gray-500 mb-6">Join eBizMedic today</p>

<form method="POST" action="<?= url('register') ?>" class="space-y-4">
    <?= csrf_field() ?>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Full Name</label>
        <input type="text" name="name" value="<?= old('name') ?>" required placeholder="Dr. Ahmad Razif"
               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Email address</label>
        <input type="email" name="email" value="<?= old('email') ?>" required placeholder="you@example.com"
               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Register as</label>
        <select name="role" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
            <option value="user"         <?= old('role') === 'user'         ? 'selected' : '' ?>>Patient (Book appointments)</option>
            <option value="medic"        <?= old('role') === 'medic'        ? 'selected' : '' ?>>Doctor / Medic</option>
            <option value="organisation" <?= old('role') === 'organisation' ? 'selected' : '' ?>>Clinic / Organisation</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
        <input type="password" name="password" required placeholder="Min 8 characters" autocomplete="new-password"
               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirm Password</label>
        <input type="password" name="confirm_password" required placeholder="Repeat password" autocomplete="new-password"
               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    <button type="submit"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg transition-colors text-sm mt-2">
        Create Account
    </button>
</form>

<p class="text-center text-sm text-gray-500 mt-6">
    Already have an account?
    <a href="<?= url('login') ?>" class="text-blue-600 font-medium hover:underline">Sign in</a>
</p>
