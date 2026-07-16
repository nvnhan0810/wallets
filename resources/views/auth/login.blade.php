<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#4f46e5">
    <title>Đăng nhập — MyWallet</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-slate-800 min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-md bg-white border border-gray-200 rounded-2xl shadow-sm p-8">
        <h1 class="text-2xl font-bold text-indigo-600 tracking-tight">MyWallet</h1>
        <p class="mt-2 text-sm text-gray-500">Đăng nhập bằng Google để quản lý ví và khoản vay.</p>

        @if (session('error'))
            <div class="mt-4 rounded-lg bg-red-50 text-red-700 text-sm px-3 py-2">{{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="mt-4 rounded-lg bg-green-50 text-green-700 text-sm px-3 py-2">{{ session('success') }}</div>
        @endif

        <a href="{{ route('auth.google') }}"
           class="mt-6 flex items-center justify-center gap-2 w-full rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-4 py-3">
            Đăng nhập bằng Google
        </a>

        <p class="mt-4 text-xs text-gray-400 text-center">
            Email phải nằm trong allowlist mới được vào.
        </p>
    </div>
</body>
</html>
