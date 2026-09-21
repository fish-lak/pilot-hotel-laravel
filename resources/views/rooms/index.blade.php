<x-app-layout title="Pilot Hotel | Rooms">
    <header class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
        <div><p class="text-sm font-semibold text-moss">Find the right stay for every guest</p><h1 class="mt-2 text-4xl font-semibold">Rooms</h1><p class="mt-2 text-stone-500">Rates are shown per night in Philippine pesos.</p><p class="mt-2 text-sm font-semibold text-ink">Standard time: Check-in 2:00 PM · Check-out 11:00 AM</p></div>
        <a href="{{ route('reservations.index') }}" class="btn-primary self-start">+ New reservation</a>
    </header>
    <section class="mt-10 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($rooms as $room)
            <article class="overflow-hidden rounded-2xl border border-stone-200 bg-white">
                <div class="p-6">
                    <div class="flex items-center justify-between gap-3"><div class="flex min-w-0 items-center gap-3"><h2 class="text-xl font-semibold">{{ $room['name'] }}</h2><button type="button" data-modal-open="room-photo-{{ $room['slug'] }}" class="overflow-hidden rounded-lg border border-stone-200 bg-stone-50 p-1" title="View {{ $room['name'] }} photo"><img class="size-10 object-cover" src="{{ asset($room['image']) }}" alt="View {{ $room['name'] }} photo"></button></div><span class="status-pill {{ $room['available_units'] > 0 ? 'status-checked-in' : 'status-checked-out' }}">{{ $room['available_units'] > 0 ? 'Available' : 'Full' }}</span></div>
                    <p class="mt-2 text-sm text-stone-500">{{ $room['description'] }}</p>
                    @if ($room['rate_min'])
                        <p class="mt-5 font-display text-2xl font-semibold">₱{{ number_format($room['rate_min']) }}<span class="font-sans text-sm font-normal text-stone-400">–₱{{ number_format($room['rate_max']) }} / night</span></p>
                    @else
                        <p class="mt-5 font-display text-2xl font-semibold">Rate to confirm<span class="block font-sans text-sm font-normal text-stone-400">Please check with the front desk</span></p>
                    @endif
                    <dl class="mt-5 space-y-2 border-t border-stone-100 pt-4 text-sm"><div class="flex justify-between gap-4"><dt class="text-stone-500">Capacity</dt><dd class="font-semibold">{{ $room['capacity'] }}</dd></div><div class="flex justify-between gap-4"><dt class="text-stone-500">Location</dt><dd class="text-right font-semibold">{{ $room['locations'] }}</dd></div><div class="flex justify-between gap-4"><dt class="text-stone-500">Availability</dt><dd class="font-semibold text-moss">{{ $room['available_units'] }} of {{ $room['units'] }} rooms</dd></div></dl>
                    <div class="mt-4 border-t border-stone-100 pt-4"><p class="text-xs font-bold uppercase tracking-wider text-stone-400">Room availability</p><div class="mt-3 flex flex-wrap gap-2">@foreach ($room['room_statuses'] as $roomStatus)<span class="rounded-lg px-2.5 py-1 text-xs font-semibold {{ $roomStatus['available'] ? 'bg-sage text-moss' : 'bg-stone-100 text-stone-500 line-through' }}">{{ $roomStatus['number'] }} · {{ $roomStatus['available'] ? 'Available' : 'Not available' }}</span>@endforeach</div></div>
                    <div class="mt-4 flex flex-wrap gap-2">@foreach ($room['amenities'] as $amenity)<span class="rounded-lg bg-cream px-2.5 py-1 text-xs text-stone-600">{{ $amenity }}</span>@endforeach</div>
                </div>
            </article>
            <div id="room-photo-{{ $room['slug'] }}" class="fixed inset-0 z-20 hidden overflow-y-auto bg-ink/70 p-5" role="dialog" aria-modal="true" aria-label="{{ $room['name'] }} photo"><div class="mx-auto mt-10 max-w-3xl rounded-2xl bg-cream p-4 sm:p-6"><div class="flex items-center justify-between gap-4"><h2 class="text-xl font-semibold">{{ $room['name'] }}</h2><button type="button" data-modal-close="room-photo-{{ $room['slug'] }}" class="text-2xl text-stone-400" aria-label="Close photo">&times;</button></div><img class="mt-4 max-h-[70vh] w-full rounded-xl object-contain" src="{{ asset($room['image']) }}" alt="{{ $room['name'] }} room"></div></div>
        @endforeach
    </section>
</x-app-layout>