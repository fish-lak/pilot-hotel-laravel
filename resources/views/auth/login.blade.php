<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilot Hotel | Staff Portal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-screen place-items-center bg-ink p-6">
    <main class="w-full max-w-md rounded-[2rem] bg-cream p-8 shadow-2xl sm:p-10">
        <div class="mb-12 flex items-center gap-3"><div class="grid size-11 place-items-center rounded-2xl bg-coral font-display text-lg font-bold text-white">P</div><span class="font-display text-lg font-semibold">PILOT HOTEL</span></div>
        <p class="mb-2 text-xs font-bold uppercase tracking-[0.2em] text-moss">Staff portal</p>
        <h1 class="font-display text-4xl font-semibold">Welcome back</h1>
        <p class="mt-2 text-stone-500">Sign in to your employee account</p>
        <form class="mt-8 space-y-5" action="{{ route('login.store') }}" method="post">@csrf
            <div><label for="username" class="text-sm font-semibold">Username / Employee ID</label><input id="username" name="username" class="field" placeholder="Enter your username" required></div>
            <div><label for="password" class="text-sm font-semibold">Password</label><input id="password" name="password" type="password" class="field" placeholder="Enter your password" required></div>
            <label class="flex items-center gap-2 text-sm text-stone-600"><input type="checkbox" checked> Remember me on this device</label>
            <button class="btn-primary w-full" type="submit">Sign in</button>
        </form>
        <p class="mt-8 text-center text-xs text-stone-400">Authorized personnel only · Pilot Hotel</p>
    </main>
</body>
</html>
