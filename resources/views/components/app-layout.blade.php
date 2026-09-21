<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Pilot Hotel' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="min-h-screen lg:flex">
        <aside class="flex w-full flex-col justify-between bg-ink p-6 text-white lg:min-h-screen lg:w-72">
            <div>
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3"><div class="grid size-11 place-items-center rounded-2xl bg-coral font-display text-lg font-bold">P</div><span class="font-display text-lg font-semibold tracking-wide">PILOT HOTEL</span></a>
                <nav class="mt-12 space-y-2"><a href="{{ route('dashboard') }}" class="block rounded-xl px-4 py-3 text-sm {{ request()->routeIs('dashboard') ? 'bg-white/10 text-white' : 'text-white/60 hover:bg-white/5 hover:text-white' }}">▦ &nbsp; Dashboard</a><a href="{{ route('reservations.index') }}" class="block rounded-xl px-4 py-3 text-sm {{ request()->routeIs('reservations.*') ? 'bg-white/10 text-white' : 'text-white/60 hover:bg-white/5 hover:text-white' }}">▤ &nbsp; Reservations</a><a href="{{ route('rooms.index') }}" class="block rounded-xl px-4 py-3 text-sm {{ request()->routeIs('rooms.*') ? 'bg-white/10 text-white' : 'text-white/60 hover:bg-white/5 hover:text-white' }}">⌂ &nbsp; Rooms</a><a href="{{ route('reports.index') }}" class="block rounded-xl px-4 py-3 text-sm {{ request()->routeIs('reports.*') ? 'bg-white/10 text-white' : 'text-white/60 hover:bg-white/5 hover:text-white' }}">≡ &nbsp; Reports</a></nav>
            </div>
            <div class="mt-8 flex items-center justify-between gap-3 border-t border-white/10 pt-5"><div class="flex items-center gap-3"><div class="grid size-10 place-items-center rounded-full bg-sage font-semibold text-moss">SA</div><div><p class="text-sm font-semibold">Sofia Anderson</p><p class="text-xs text-white/50">Front Desk Manager</p></div></div><form action="{{ route('logout') }}" method="post">@csrf<button class="text-xs font-semibold text-white/50 hover:text-white" type="submit">Log out</button></form></div>
        </aside>
        <main class="min-w-0 flex-1 p-5 sm:p-8 lg:p-12">@if (session('success'))<div class="mb-6 rounded-xl border border-moss/20 bg-sage px-4 py-3 text-sm text-moss">{{ session('success') }}</div>@endif{{ $slot }}</main>
    </div>
</body>
</html>
