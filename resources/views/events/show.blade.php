@extends('layouts.app')
@section('title', $event->title)

@section('content')
@php
    $heroImage = $event->banner_url ?: 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?auto=format&fit=crop&w=1600&q=80';
    $venueLine = collect([$event->venue_name, $event->venue_address, $event->city, $event->postcode])->filter()->implode(', ');
    $mapQuery = rawurlencode($venueLine ?: 'Central Park Amphitheater, New York, NY');
    $aboutText = trim(strip_tags($event->description ?: $event->short_description ?: 'Join us for a standout live experience with premium production, unforgettable performances, and an incredible crowd.'));
    $organiserName = $event->organiser->name ?? 'Premier Events Co.';
    $organiserBio = $event->organiser->bio ?: 'We create unforgettable experiences for all audiences.';
    $organiserLogo = $event->organiser->logo_url ?? null;
    $organiserInitials = $event->organiser->initials ?? 'PE';
    $ticketItemsOld = collect(old('items', []));
    $eventStartLabel = $event->starts_at->format('l, F j, Y') . ' at ' . $event->starts_at->format('g:i A');
    $eventEndLabel = $event->ends_at->format('l, F j, Y') . ' at ' . $event->ends_at->format('g:i A');
    $lineupItems = collect($event->performer_lineup ?? [])->filter(fn ($item) => filled(data_get($item, 'name')))->values();
    if ($lineupItems->isEmpty()) {
        $lineupItems = collect([
            ['time' => '2:00 PM', 'name' => 'Gates Open'],
            ['time' => '3:00 PM', 'name' => 'Opening Act - The Rising Stars'],
            ['time' => '5:00 PM', 'name' => 'Main Stage - Electronic Beats'],
            ['time' => '8:00 PM', 'name' => 'Headliner Performance'],
        ]);
    }
    $faqItems = [
        ["What's included in the ticket?", 'Your ticket includes event entry and access to the benefits of the tier you select.'],
        ['Is there parking available?', $event->parking_info ?: 'Parking and transport details will be shared with ticket holders before the event.'],
        ["What's the age requirement?", 'Please review organiser notes before attending. Entry rules are determined by the organiser and venue.'],
    ];
    $interestedCount = max(1200, (int) $event->ticketTiers->sum('sold_quantity') * 8);
@endphp

<div class="bg-[#ffffff] text-slate-900">
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-cover bg-center" style="background-image:url('{{ $heroImage }}');"></div>
        <div class="absolute inset-0"></div>
        <div class="relative mx-auto max-w-[1440px] px-2 pb-28 pt-4 sm:px-4 sm:pb-40 sm:pt-5 lg:px-4 lg:pb-44">
            <div class="flex items-start justify-between gap-4">
                <button type="button" onclick="history.back()" class="inline-flex items-center gap-2 rounded-xl bg-white/95 px-4 py-2.5 text-sm font-medium text-slate-800 shadow-sm ring-1 ring-slate-200">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor"><path d="M12.5 4.5 7 10l5.5 5.5" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>
                    <span>Back</span>
                </button>
                <div class="flex items-center gap-2">
                    <button type="button" id="share-event-button" class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-white/95 text-slate-700 shadow-sm ring-1 ring-slate-200" aria-label="Share event">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path d="M15 8 9 12l6 4" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/>
                            <circle cx="18" cy="6" r="2.25" stroke-width="1.8"/>
                            <circle cx="6" cy="12" r="2.25" stroke-width="1.8"/>
                            <circle cx="18" cy="18" r="2.25" stroke-width="1.8"/>
                        </svg>
                    </button>
                    <!-- <button type="button" id="favorite-event-button" class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-white/95 text-slate-700 shadow-sm ring-1 ring-slate-200" aria-label="Save event" aria-pressed="false">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12.62 20.55a1 1 0 0 1-1.24 0C6.53 16.88 4 14.39 4 10.92 4 8.37 5.97 6.5 8.31 6.5c1.42 0 2.79.68 3.69 1.83.9-1.15 2.27-1.83 3.69-1.83C18.03 6.5 20 8.37 20 10.92c0 3.47-2.53 5.96-7.38 9.63Z" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"/></svg>
                    </button> -->
                </div>
            </div>

            <div class="mx-auto mt-12 max-w-5xl text-center text-white sm:mt-16">
                <div class="inline-flex rounded-full bg-[linear-gradient(135deg,#6d28d9,#8b5cf6)] px-5 py-2 text-[12px] font-semibold uppercase tracking-[0.14em] text-white shadow-[0_12px_30px_rgba(109,40,217,0.35)] sm:px-8 sm:py-3 sm:text-[15px] sm:tracking-[0.2em]" style="color:#ffffff !important;">
                    {{ $event->category ? strtoupper($event->category) : 'Live Event' }}
                </div>
                <h1 class="mt-6 text-3xl font-black tracking-[-0.045em] text-white sm:mt-7 sm:text-5xl lg:text-[5rem] lg:leading-[1.02]" style="color:#ffffff !important;">{{ $event->title }}</h1>
                <div class="mt-4 space-y-1 text-lg font-medium text-white/95 sm:mt-5 sm:text-[2rem]">
                    <p>Start: {{ $eventStartLabel }}</p>
                    <p>End: {{ $eventEndLabel }}</p>
                </div>
            </div>
        </div>
    </section>

    <div class="relative z-10 -mt-10 pb-14 sm:-mt-16 sm:pb-20">
        <div class="mx-auto max-w-[1440px] px-2 sm:px-4 lg:px-4">
            @if($errors->any())
                <div class="mb-6 rounded-[22px] border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700 shadow-[0_20px_45px_rgba(239,68,68,0.08)]">
                    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                </div>
            @endif

            <div class="grid gap-7 lg:grid-cols-[minmax(0,1fr)_340px] lg:items-start">
                <div class="space-y-6">
                    <section class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-[0_20px_50px_rgba(15,23,42,0.06)] sm:p-8">
                        <span class="inline-flex rounded-lg bg-violet-600 px-3 py-1 text-xs font-semibold text-white" style="color:#ffffff !important;">{{ $event->category ? $event->category : 'Event' }}</span>
                        <h2 class="mt-5 text-[1.75rem] font-bold tracking-[-0.03em] text-slate-900">{{ $event->title }}</h2>
                        <div class="mt-6 grid gap-5 sm:grid-cols-2">
                            <div class="flex items-start gap-4">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-violet-100 text-violet-600"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M8 3v3m8-3v3M4 9h16M5.5 5.5h13A1.5 1.5 0 0 1 20 7v11.5a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 18.5V7a1.5 1.5 0 0 1 1.5-1.5Z" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg></div>
                                <div>
                                    <p class="text-[1.05rem] font-semibold text-slate-900">Start: {{ $eventStartLabel }}</p>
                                    <p class="mt-1 text-[1rem] text-slate-500">End: {{ $eventEndLabel }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-violet-100 text-violet-600"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 20.5s6.5-5.8 6.5-11A6.5 6.5 0 0 0 5.5 9.5c0 5.2 6.5 11 6.5 11Z" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/><path d="M12 12.5a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg></div>
                                <div><p class="text-[1.05rem] font-semibold text-slate-900">{{ $event->venue_name ?: 'Central Park Amphitheater' }}</p><p class="mt-1 text-[1rem] text-slate-500">{{ $venueLine ?: 'Central Park, New York, NY' }}</p></div>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-[0_20px_50px_rgba(15,23,42,0.06)] sm:p-8">
                        <h2 class="text-[1.75rem] font-bold tracking-[-0.03em] text-slate-900">About This Event</h2>
                        <p class="mt-6 max-w-4xl text-[1.02rem] leading-8 text-slate-500">{{ $aboutText }}</p>
                    </section>

                    <section class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-[0_20px_50px_rgba(15,23,42,0.06)] sm:p-8">
                        <h2 class="text-[1.75rem] font-bold tracking-[-0.03em] text-slate-900">Lineup / Schedule</h2>
                        <div class="mt-6 space-y-5">@foreach($lineupItems as $item)<div class="grid gap-3 text-[1.02rem] sm:grid-cols-[116px_minmax(0,1fr)] sm:items-center"><div class="font-medium text-slate-500">{{ data_get($item, 'time', $event->starts_at->format('g:i A')) }}</div><div class="font-medium text-slate-900">{{ data_get($item, 'name') }}</div></div>@endforeach</div>
                    </section>

                    <section class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-[0_20px_50px_rgba(15,23,42,0.06)] sm:p-8">
                        <h2 class="text-[1.75rem] font-bold tracking-[-0.03em] text-slate-900">Venue</h2>
                        <div class="mt-5 overflow-hidden rounded-[20px] border border-slate-200"><iframe title="Venue map" src="https://maps.google.com/maps?q={{ $mapQuery }}&t=&z=13&ie=UTF8&iwloc=&output=embed" class="h-[320px] w-full sm:h-[400px]" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe></div>
                        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                            <div><p class="text-[1.12rem] font-semibold text-slate-900">{{ $event->venue_name ?: 'Central Park Amphitheater' }}</p><p class="mt-1 text-[1rem] text-slate-500">{{ $venueLine ?: 'Central Park, New York, NY' }}</p></div>
                            <a href="https://www.google.com/maps/search/?api=1&query={{ $mapQuery }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-800 hover:bg-slate-50"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor"><path d="M10 3.75h5.25V9M9.25 10.75l6-6M14.25 10.75v4.5a1 1 0 0 1-1 1h-8.5a1 1 0 0 1-1-1v-8.5a1 1 0 0 1 1-1h4.5" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg><span>Get Directions</span></a>
                        </div>
                    </section>

                    <section class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-[0_20px_50px_rgba(15,23,42,0.06)] sm:p-8">
                        <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                            @if($organiserLogo)
                                <img src="{{ $organiserLogo }}" alt="{{ $organiserName }}" class="h-16 w-16 rounded-full object-cover ring-4 ring-violet-100">
                            @else
                                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-[linear-gradient(135deg,#7c3aed,#a855f7)] text-lg font-bold text-white ring-4 ring-violet-100">{{ $organiserInitials }}</div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-3"><h2 class="text-[1.35rem] font-bold text-slate-900">{{ $organiserName }}</h2>@if(($event->organiser->is_approved ?? false))<span class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">Verified</span>@endif</div>
                                <p class="mt-4 max-w-3xl text-[1rem] leading-7 text-slate-500">{{ $organiserBio }}</p>
                                <!-- <div class="mt-5 flex flex-wrap items-center gap-3">@if($event->organiser->website ?? false)<a href="{{ $event->organiser->website }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-800 hover:bg-slate-50">View Profile</a>@else<button type="button" class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-800 hover:bg-slate-50">View Profile</button>@endif<button type="button" class="text-sm font-medium text-slate-900">Follow</button></div> -->
                            </div>
                        </div>
                    </section>

                    <section class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-[0_20px_50px_rgba(15,23,42,0.06)] sm:p-8">
                        <h2 class="text-[1.75rem] font-bold tracking-[-0.03em] text-slate-900">FAQs</h2>
                        <div class="mt-6 divide-y divide-slate-200">
                            @foreach($faqItems as [$question, $answer])
                                <div class="py-1">
                                    <button type="button" class="faq-toggle flex w-full items-center justify-between gap-4 py-5 text-left" aria-expanded="false"><span class="text-[1.12rem] font-medium text-slate-900">{{ $question }}</span><svg class="h-5 w-5 shrink-0 text-slate-500 transition-transform" viewBox="0 0 20 20" fill="none" stroke="currentColor"><path d="m6 8 4 4 4-4" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"/></svg></button>
                                    <div class="faq-answer hidden pb-5 pr-8 text-[1rem] leading-7 text-slate-500">{{ $answer }}</div>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-[0_20px_50px_rgba(15,23,42,0.06)] sm:p-8">
                        <h2 class="text-[1.75rem] font-bold tracking-[-0.03em] text-slate-900">Refund Policy</h2>
                        <p class="mt-6 max-w-4xl text-[1.02rem] leading-8 text-slate-500">{{ $event->refund_policy ?: 'Full refund available up to 7 days before the event. 50% refund up to 3 days before. No refunds within 72 hours of the event.' }}</p>
                    </section>
                </div>

                <aside class="lg:sticky lg:top-24" style="width : 25rem">
                    @if($event->isCancelled())
                        <div class="rounded-[24px] border border-red-200 bg-white p-4 shadow-[0_20px_50px_rgba(15,23,42,0.06)] sm:p-6"><h2 class="text-[1.55rem] font-bold tracking-[-0.03em] text-slate-900 sm:text-[2rem]">Event Unavailable</h2><p class="mt-4 text-[1rem] leading-7 text-slate-500">{{ $event->cancellation_reason ?: 'This event has been cancelled by the organiser.' }}</p></div>
                    @elseif($event->starts_at->isPast())
                        <div class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-[0_20px_50px_rgba(15,23,42,0.06)] sm:p-6"><h2 class="text-[1.55rem] font-bold tracking-[-0.03em] text-slate-900 sm:text-[2rem]">Event Closed</h2><p class="mt-4 text-[1rem] leading-7 text-slate-500">This event has already taken place.</p></div>
                    @elseif($event->ticketTiers->isEmpty())
                        <div class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-[0_20px_50px_rgba(15,23,42,0.06)] sm:p-6"><h2 class="text-[1.55rem] font-bold tracking-[-0.03em] text-slate-900 sm:text-[2rem]">Tickets Coming Soon</h2><p class="mt-4 text-[1rem] leading-7 text-slate-500">Tickets for this event are not available yet. Please check back later.</p></div>
                    @else
                        <div class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-[0_20px_50px_rgba(15,23,42,0.06)] sm:p-6">
                            <h2 class="text-[1.75rem] font-bold tracking-[-0.03em] text-slate-900">Select Tickets</h2>
                            <form action="{{ route('reservation.store') }}" method="POST" id="ticket-form" class="mt-5">
                                @csrf
                                <input type="hidden" name="event_id" value="{{ $event->id }}">
                                <div class="space-y-3">
                                    @foreach($event->ticketTiers as $tier)
                                        @php
                                            $soldOut = $tier->available_quantity <= 0;
                                            $maxSelect = min($tier->max_per_order, $tier->available_quantity);
                                            $oldItem = $ticketItemsOld->firstWhere('ticket_tier_id', $tier->id);
                                            $initialQty = min($maxSelect, max(0, (int) data_get($oldItem, 'quantity', 0)));
                                        @endphp
                                        <div id="tier-card-{{ $tier->id }}" class="rounded-[18px] border bg-white p-4 transition {{ $soldOut ? 'border-slate-200 opacity-60' : ($initialQty > 0 ? 'border-violet-500 shadow-[0_0_0_3px_rgba(124,58,237,0.08)]' : 'border-slate-200') }}">
                                            <div class="space-y-2">
                                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                    <div class="min-w-0 flex-1">
                                                        <h3 class="truncate text-[1.02rem] font-semibold text-slate-900">
                                                            {{ $tier->name }}
                                                        </h3>
                                                    </div>
                                                    @if(!$soldOut)
                                                        <div class="flex items-center gap-3 self-start sm:ml-4">
                                                            <button type="button" onclick="changeQty({{ $tier->id }}, -1)" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 text-lg font-medium text-slate-500 hover:bg-slate-50">-</button>
                                                            <span id="qty-display-{{ $tier->id }}" class="w-5 text-center text-[1.05rem] font-medium text-slate-900">{{ $initialQty }}</span>
                                                            <button type="button" onclick="changeQty({{ $tier->id }}, 1)" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 text-lg font-medium text-slate-900 hover:bg-slate-50">+</button>
                                                        </div>
                                                        <input type="hidden" name="items[{{ $loop->index }}][ticket_tier_id]" value="{{ $tier->id }}">
                                                        <input type="hidden" name="items[{{ $loop->index }}][quantity]" id="qty-{{ $tier->id }}" data-max="{{ $maxSelect }}" value="{{ $initialQty }}">
                                                    @else
                                                        <div class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-500">Sold Out</div>
                                                        <input type="hidden" name="items[{{ $loop->index }}][ticket_tier_id]" value="{{ $tier->id }}">
                                                        <input type="hidden" name="items[{{ $loop->index }}][quantity]" id="qty-{{ $tier->id }}" data-max="0" value="0">
                                                    @endif
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="mt-0 text-[0.98rem] leading-6 text-slate-500">{{ \Illuminate\Support\Str::limit($tier->description ?: 'Standard entry to all stages and event areas.', 64) }}</p>
                                                    <div class="mt-3 flex flex-wrap items-center gap-2"><span class="text-[1.08rem] font-bold text-violet-600">{{ $tier->price == 0 ? 'Free' : ticketly_money($tier->price) }}</span></div>
                                                    <p class="mt-1 text-xs text-slate-500">{{ $soldOut ? 'Sold out' : number_format($tier->available_quantity) . ' left' }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="mt-6">
                                    <h3 class="text-[1.45rem] font-bold tracking-[-0.03em] text-slate-900">Order Summary</h3>
                                    <div id="summary-items" class="mt-4 space-y-3 border-b border-slate-200 pb-4 text-sm"></div>
                                    <div class="space-y-2 pt-4 text-[0.98rem] text-slate-500">
                                        <div class="flex items-center justify-between"><span>Subtotal</span><span id="subtotal-display" class="font-medium text-slate-900">{{ ticketly_money(0) }}</span></div>
                                        <div class="flex items-center justify-between"><span>Portal Fee ({{ config('ticketly.portal_fee_percentage', 10) }}%)</span><span id="portal-fee-display" class="font-medium text-slate-900">{{ ticketly_money(0) }}</span></div>
                                        <div class="flex items-center justify-between"><span>Service Fee ({{ config('ticketly.service_fee_percentage', 5) }}%)</span><span id="fee-display" class="font-medium text-slate-900">{{ ticketly_money(0) }}</span></div>
                                    </div>
                                </div>

                                <!-- <div class="mt-5 flex gap-2">
                                    <input type="text" placeholder="Promo code" class="h-11 min-w-0 flex-1 rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-violet-300 focus:ring-2 focus:ring-violet-100">
                                    <button type="button" class="rounded-xl border border-slate-200 px-4 text-sm font-medium text-slate-500 hover:bg-slate-50">Apply</button>
                                </div> -->

                                <div class="mt-6 flex items-center justify-between text-[1.1rem] font-semibold text-slate-900"><span>Total</span><span id="total-display" class="font-bold text-violet-600">{{ ticketly_money(0) }}</span></div>
                                <button type="submit" id="reserve-btn" class="mt-6 inline-flex w-full items-center justify-center rounded-2xl bg-[linear-gradient(135deg,#7c3aed,#9333ea)] px-5 py-4 text-base font-semibold text-white shadow-[0_16px_40px_rgba(124,58,237,0.28)] disabled:cursor-not-allowed disabled:opacity-50" style="color:#ffffff !important;" disabled>Get Ticket</button>
                                <div class="mt-4 flex items-center justify-center gap-4 text-xs text-slate-500"><span>{{ number_format($interestedCount / 1000, 1) }}k interested</span><span>Selling fast</span></div>
                            </form>
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@php
    $tiersForJs = $event->ticketTiers->map(function ($tier) use ($ticketItemsOld) {
        $oldItem = $ticketItemsOld->firstWhere('ticket_tier_id', $tier->id);
        $maxSelect = min($tier->max_per_order, $tier->available_quantity);
        return ['id' => $tier->id, 'name' => $tier->name, 'price' => (float) $tier->price, 'max' => (int) $maxSelect, 'quantity' => min($maxSelect, max(0, (int) data_get($oldItem, 'quantity', 0)))];
    })->values();
@endphp
<script>
const tiers = @json($tiersForJs), feePct = {{ config('ticketly.service_fee_percentage', 5) }}, portalFeePct = {{ config('ticketly.portal_fee_percentage', 10) }}, currencySymbol = @js(ticketly_currency_symbol());
const money = (amount) => currencySymbol + Number(amount).toFixed(2);
function setTierState(id, qty) { const card = document.getElementById('tier-card-' + id); if (!card) return; card.classList.toggle('border-violet-500', qty > 0); card.classList.toggle('border-slate-200', qty === 0); card.style.boxShadow = qty > 0 ? '0 0 0 3px rgba(124,58,237,.08)' : ''; }
function changeQty(id, delta) { const input = document.getElementById('qty-' + id), display = document.getElementById('qty-display-' + id); if (!input || !display) return; const max = parseInt(input.dataset.max || '0', 10); const value = Math.max(0, Math.min(max, (parseInt(input.value || '0', 10) + delta))); input.value = value; display.textContent = value; updateSummary(); }
function updateSummary() {
    const summary = document.getElementById('summary-items'), subtotalEl = document.getElementById('subtotal-display'), portalFeeEl = document.getElementById('portal-fee-display'), feeEl = document.getElementById('fee-display'), totalEl = document.getElementById('total-display'), btn = document.getElementById('reserve-btn');
    if (!summary || !subtotalEl || !portalFeeEl || !feeEl || !totalEl || !btn) return;
    let subtotal = 0, count = 0, html = '';
    tiers.forEach((tier) => {
        const input = document.getElementById('qty-' + tier.id), qty = parseInt(input ? input.value : tier.quantity, 10) || 0, lineTotal = qty * Number(tier.price);
        setTierState(tier.id, qty);
        if (qty > 0) { count += qty; subtotal += lineTotal; html += `<div class="flex items-center justify-between gap-3 text-[0.98rem]"><span class="min-w-0 truncate text-slate-500">${tier.name} x ${qty}</span><span class="shrink-0 font-medium text-slate-900">${money(lineTotal)}</span></div>`; }
    });
    if (!html) html = '<p class="text-[0.98rem] text-slate-400">Select tickets to see your order summary.</p>';
    const portalFee = count > 0 && subtotal > 0 ? Number((subtotal * portalFeePct / 100).toFixed(2)) : 0;
    const fee = count > 0 && subtotal > 0 ? Number((subtotal * feePct / 100).toFixed(2)) : 0;
    const total = subtotal + portalFee + fee;
    summary.innerHTML = html; subtotalEl.textContent = money(subtotal); portalFeeEl.textContent = money(portalFee); feeEl.textContent = money(fee); totalEl.textContent = money(total); btn.disabled = count === 0; btn.textContent = count > 0 ? `Get ${count} Ticket${count > 1 ? 's' : ''}` : 'Get Ticket';
}
document.getElementById('ticket-form')?.addEventListener('submit', (e) => { if (!tiers.some((tier) => parseInt(document.getElementById('qty-' + tier.id)?.value || '0', 10) > 0)) e.preventDefault(); });
document.querySelectorAll('.faq-toggle').forEach((btn) => btn.addEventListener('click', function () { const answer = this.nextElementSibling, icon = this.querySelector('svg'), open = this.getAttribute('aria-expanded') === 'true'; this.setAttribute('aria-expanded', open ? 'false' : 'true'); answer?.classList.toggle('hidden', open); icon?.classList.toggle('rotate-180', !open); }));
document.getElementById('favorite-event-button')?.addEventListener('click', function () { const pressed = this.getAttribute('aria-pressed') === 'true'; this.setAttribute('aria-pressed', pressed ? 'false' : 'true'); this.classList.toggle('text-red-500', !pressed); });
document.getElementById('share-event-button')?.addEventListener('click', async function () { const url = window.location.href, data = { title: @json($event->title), url }; if (navigator.share) { try { await navigator.share(data); return; } catch (error) {} } try { await navigator.clipboard.writeText(url); this.classList.add('text-violet-600'); setTimeout(() => this.classList.remove('text-violet-600'), 1200); } catch (error) {} });
updateSummary();
</script>
@endsection
