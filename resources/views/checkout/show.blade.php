@extends('layouts.app')
@section('title', 'Checkout - ' . $reservation->event->title)

@section('head')
<script src="https://js.stripe.com/v3/" defer></script>

@endsection

@section('content')
@php
    $eventPageUrl = route('events.show', $reservation->event->slug);
    $ticketSelectionUrl = $eventPageUrl . '#ticket-form';
    $heroImage = $reservation->event->banner_url ?: 'https://images.unsplash.com/photo-1470229722913-7c0e2dbbafd3?w=600&h=400&fit=crop';
    $startsAt = $reservation->event->starts_at;
    $endsAt = $reservation->event->ends_at;
    $desktopDateLine = $startsAt->format('Y-m-d') . ' at ' . $startsAt->format('g:i A');
    $mobileDateLine = $startsAt->format('l, F j') . ' | ' . $startsAt->format('g:i A') . ($endsAt ? ' - ' . $endsAt->format('g:i A') : '');
    $desktopLocation = collect([$reservation->event->venue_name, $reservation->event->city])->filter()->implode(', ');
    $mobileLocation = collect([$reservation->event->venue_name, $reservation->event->city])->filter()->implode(' | ');
    $initialPricing = \App\Services\ServiceFeeCalculator::total(
        (float) $reservation->subtotal,
        (float) ($reservation->discount_amount ?? 0)
    );
    $initialDiscount = (float) $initialPricing['discount'];
    $initialTotal = (float) $initialPricing['total'];
@endphp

<div class="min-h-screen bg-[#ffffff] text-slate-900">
    <section class="relative overflow-hidden lg:hidden">
        <img src="{{ $heroImage }}" alt="{{ $reservation->event->title }}" class="h-[238px] w-full object-cover max-[375px]:h-[220px] sm:h-[274px]">
        <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(32,14,77,0.1)_0%,rgba(34,15,79,0.48)_28%,rgba(16,10,41,0.8)_100%)]"></div>
        <div class="absolute inset-x-0 bottom-0 px-5 pb-7 text-center text-white max-[375px]:px-3 max-[375px]:pb-5">
            <div class="inline-flex rounded-full bg-[linear-gradient(135deg,#7c3aed,#8b5cf6)] px-4 py-1.5 text-[11px] font-bold uppercase tracking-[0.18em] max-[375px]:px-3 max-[375px]:text-[10px]" style="color:#ffffff !important;">FEATURED</div>
            <h1 class="mt-4 break-words text-[1.65rem] leading-none tracking-[-0.05em] max-[375px]:text-[1.4rem] sm:text-[1.95rem]" style="color:#ffffff !important;">{{ $reservation->event->title }}</h1>
            <p class="mt-3 text-[0.95rem] font-medium text-white/90 max-[375px]:text-[0.85rem] sm:text-[1rem]">{{ $mobileDateLine }}</p>
            <p class="mt-3 text-[0.9rem] font-semibold text-white/88 max-[375px]:mt-2 max-[375px]:text-[0.82rem] sm:text-[0.95rem]" style="color:#ffffff !important;">{{ $mobileLocation }}</p>
        </div>
    </section>

    <div class="mx-auto max-w-[1480px] px-4 pb-10 pt-6 max-[375px]:px-3 sm:px-6 lg:px-8 lg:py-8">
        @if(session('payment_cancelled'))
            <div class="mb-6 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" role="alert" aria-live="polite">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m8.938-3A9.002 9.002 0 0012 3a9.002 9.002 0 00-8.938 9 9.002 9.002 0 008.938 9 9.002 9.002 0 008.938-9z"></path>
                </svg>
                <span class="font-medium">Your payment was cancelled. Your tickets are still held. Complete checkout before the timer expires.</span>
            </div>
        @endif

        @if(session('payment_error'))
            <div class="mb-6 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M4.93 19h14.14c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.2 16c-.77 1.33.19 3 1.73 3z"></path>
                </svg>
                <span class="font-medium">Payment failed: {{ session('payment_error') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
                @foreach($errors->all() as $e)
                    <div class="flex items-start gap-2">
                        <span class="pt-0.5 text-rose-500">&bull;</span>
                        <span>{{ $e }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <div id="hold-expired-alert" class="hidden mb-6 rounded-[24px] border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-700" role="alert" aria-live="assertive">
            <p id="hold-expired-message" class="font-semibold text-rose-800"></p>
            <a id="hold-expired-return-link"
               href="{{ $ticketSelectionUrl }}"
               class="mt-3 inline-flex items-center justify-center rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white">
                Return to ticket selection
            </a>
            <p class="mt-2 text-xs text-rose-500">Redirecting automatically...</p>
        </div>

        <div class="grid gap-6 max-[375px]:gap-5 lg:grid-cols-[minmax(0,1fr)_404px] lg:gap-14">
            <div class="order-2 space-y-8 lg:order-1">
                <div class="hidden lg:flex">
                    <a href="{{ $ticketSelectionUrl }}" class="inline-flex items-center gap-3 text-[0.98rem] font-medium text-slate-800">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 6 9 12l6 6"></path>
                            </svg>
                        </span>
                        <span>Back to Event</span>
                    </a>
                </div>

                <section class="hidden rounded-[30px] border border-slate-200 bg-white p-7 shadow-[0_10px_30px_rgba(15,23,42,0.04)] lg:block">
                    <div class="flex items-center gap-5">
                        <img src="{{ $heroImage }}" alt="{{ $reservation->event->title }}" class="h-[142px] w-[174px] rounded-[18px] object-cover">
                        <div class="min-w-0">
                            <h2 class="text-[1.75rem] font-extrabold tracking-[-0.04em] text-slate-900">{{ $reservation->event->title }}</h2>
                            <p class="mt-4 text-[0.98rem] text-slate-500">{{ $desktopDateLine }}</p>
                            <p class="mt-3 text-[0.98rem] text-slate-500">{{ $desktopLocation }}</p>
                        </div>
                    </div>
                </section>

                <section id="contact-section" class="rounded-[30px] border border-slate-200 bg-white p-6 shadow-[0_10px_30px_rgba(15,23,42,0.04)] max-[375px]:rounded-[24px] max-[375px]:p-4 sm:p-8">
                    <h2 class="text-[1.45rem] font-extrabold tracking-[-0.04em] text-slate-900 max-[375px]:text-[1.25rem] sm:text-[1.6rem]">Contact Information</h2>
                    <div class="mt-8 space-y-7 max-[375px]:mt-5 max-[375px]:space-y-5">
                        <div>
                            <label for="customer-name" class="mb-3 block text-[0.98rem] font-medium text-slate-700 max-[375px]:mb-2 max-[375px]:text-[0.9rem]">Full Name</label>
                            <input id="customer-name" type="text" autocomplete="name" required aria-describedby="customer-name-error"
                                   class="h-16 w-full rounded-2xl border border-slate-200 bg-white px-5 text-[1rem] text-slate-900 outline-none transition focus:border-violet-300 focus:ring-4 focus:ring-violet-100 max-[375px]:h-14 max-[375px]:px-4 max-[375px]:text-[0.95rem]"
                                   placeholder="Enter your full name">
                            <p id="customer-name-error" class="hidden mt-2 text-sm text-rose-600"></p>
                        </div>
                        <div>
                            <label for="customer-email" class="mb-3 block text-[0.98rem] font-medium text-slate-700 max-[375px]:mb-2 max-[375px]:text-[0.9rem]">Email Address</label>
                            <input id="customer-email" type="email" autocomplete="email" required aria-describedby="customer-email-error"
                                   class="h-16 w-full rounded-2xl border border-slate-200 bg-white px-5 text-[1rem] text-slate-900 outline-none transition focus:border-violet-300 focus:ring-4 focus:ring-violet-100 max-[375px]:h-14 max-[375px]:px-4 max-[375px]:text-[0.95rem]"
                                   placeholder="Enter your email address">
                            <p id="customer-email-error" class="hidden mt-2 text-sm text-rose-600"></p>
                        </div>
                        <div>
                            <label for="customer-phone" class="mb-3 block text-[0.98rem] font-medium text-slate-700 max-[375px]:mb-2 max-[375px]:text-[0.9rem]">Phone Number</label>
                            <input id="customer-phone" type="tel" autocomplete="tel" required aria-describedby="customer-phone-error"
                                   class="h-16 w-full rounded-2xl border border-slate-200 bg-white px-5 text-[1rem] text-slate-900 outline-none transition focus:border-violet-300 focus:ring-4 focus:ring-violet-100 max-[375px]:h-14 max-[375px]:px-4 max-[375px]:text-[0.95rem]"
                                   placeholder="Enter your phone number">
                            <p id="customer-phone-error" class="hidden mt-2 text-sm text-rose-600"></p>
                        </div>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="customer-city" class="mb-3 block text-[0.98rem] font-medium text-slate-700 max-[375px]:mb-2 max-[375px]:text-[0.9rem]">City</label>
                                <input id="customer-city" type="text" autocomplete="address-level2" required aria-describedby="customer-city-error"
                                       class="h-16 w-full rounded-2xl border border-slate-200 bg-white px-5 text-[1rem] text-slate-900 outline-none transition focus:border-violet-300 focus:ring-4 focus:ring-violet-100 max-[375px]:h-14 max-[375px]:px-4 max-[375px]:text-[0.95rem]"
                                       placeholder="City">
                                <p id="customer-city-error" class="hidden mt-2 text-sm text-rose-600"></p>
                            </div>
                            <div>
                                <label for="customer-state" class="mb-3 block text-[0.98rem] font-medium text-slate-700 max-[375px]:mb-2 max-[375px]:text-[0.9rem]">State</label>
                                <input id="customer-state" type="text" autocomplete="address-level1" required aria-describedby="customer-state-error"
                                       class="h-16 w-full rounded-2xl border border-slate-200 bg-white px-5 text-[1rem] text-slate-900 outline-none transition focus:border-violet-300 focus:ring-4 focus:ring-violet-100 max-[375px]:h-14 max-[375px]:px-4 max-[375px]:text-[0.95rem]"
                                       placeholder="State">
                                <p id="customer-state-error" class="hidden mt-2 text-sm text-rose-600"></p>
                            </div>
                        </div>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="customer-postal" class="mb-3 block text-[0.98rem] font-medium text-slate-700 max-[375px]:mb-2 max-[375px]:text-[0.9rem]">Postal Code</label>
                                <input id="customer-postal" type="text" autocomplete="postal-code" required aria-describedby="customer-postal-error"
                                       class="h-16 w-full rounded-2xl border border-slate-200 bg-white px-5 text-[1rem] text-slate-900 outline-none transition focus:border-violet-300 focus:ring-4 focus:ring-violet-100 max-[375px]:h-14 max-[375px]:px-4 max-[375px]:text-[0.95rem]"
                                       placeholder="Pin Code">
                                <p id="customer-postal-error" class="hidden mt-2 text-sm text-rose-600"></p>
                            </div>
                            <div>
                                <label for="customer-country" class="mb-3 block text-[0.98rem] font-medium text-slate-700 max-[375px]:mb-2 max-[375px]:text-[0.9rem]">Country Code (2 letters)</label>
                                <input id="customer-country" type="text" autocomplete="country" required aria-describedby="customer-country-error"
                                       class="h-16 w-full rounded-2xl border border-slate-200 bg-white px-5 text-[1rem] text-slate-900 uppercase tracking-[0.2em] placeholder:normal-case placeholder:tracking-normal outline-none transition focus:border-violet-300 focus:ring-4 focus:ring-violet-100 max-[375px]:h-14 max-[375px]:px-4 max-[375px]:text-[0.95rem]"
                                       placeholder="Country Code">
                                <p id="customer-country-error" class="hidden mt-2 text-sm text-rose-600"></p>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="payment-section" class="rounded-[30px] border border-slate-200 bg-white p-6 shadow-[0_10px_30px_rgba(15,23,42,0.04)] max-[375px]:rounded-[24px] max-[375px]:p-4 sm:p-8">
                    <h2 class="text-[1.45rem] font-extrabold tracking-[-0.04em] text-slate-900 max-[375px]:text-[1.25rem] sm:text-[1.6rem]">Payment Method</h2>
                    <div class="mt-8 rounded-[24px] border border-slate-200 bg-[#fbfbfc] p-5 max-[375px]:mt-5 max-[375px]:p-4 sm:p-8">
                        <div class="mb-6 flex items-center gap-4 text-slate-900">
                            <svg class="h-7 w-7 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <rect x="3.5" y="5.5" width="17" height="13" rx="2.5" stroke-width="1.8"></rect>
                                <path d="M3.5 10h17" stroke-width="1.8" stroke-linecap="round"></path>
                            </svg>
                            <span class="text-[1rem] font-medium max-[375px]:text-[0.92rem] sm:text-[1.05rem]">Credit / Debit Card</span>
                        </div>

                        <div id="payment-element" class="rounded-2xl border border-slate-200 bg-white p-4 max-[375px]:p-3" aria-label="Card payment form"></div>
                        <div id="payment-placeholder" class="hidden mt-4 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600" role="status"></div>
                        <div id="payment-message" class="hidden mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700" role="alert" aria-live="assertive"></div>
                    </div>

                    <div class="mt-7 flex items-center gap-3 text-[0.98rem] text-slate-500 max-[375px]:mt-5 max-[375px]:items-start max-[375px]:text-[0.88rem]">
                        <svg class="h-6 w-6 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 10V7a5 5 0 0 1 10 0v3"></path>
                            <rect x="5" y="10" width="14" height="10" rx="2" stroke-width="1.8"></rect>
                        </svg>
                        <span>Your payment information is encrypted and secure</span>
                    </div>
                </section>

                <section class="rounded-[30px] border border-slate-200 bg-white p-6 shadow-[0_10px_30px_rgba(15,23,42,0.04)] max-[375px]:rounded-[24px] max-[375px]:p-4">
                    <label class="flex items-center gap-4 text-[1rem] text-slate-700 max-[375px]:items-start max-[375px]:gap-3 max-[375px]:text-[0.9rem]">
                        <input id="terms-checkbox" type="checkbox" aria-describedby="terms-checkbox-error" class="h-6 w-6 rounded-md border border-slate-300 text-violet-600 focus:ring-violet-500 max-[375px]:mt-0.5 max-[375px]:h-5 max-[375px]:w-5">
                        <span>I agree to the <a href="#" class="font-medium text-violet-500">Terms of Service</a> and <a href="#" class="font-medium text-violet-500">Privacy Policy</a></span>
                    </label>
                    <p id="terms-checkbox-error" class="hidden mt-3 text-sm text-rose-600"></p>
                </section>

                <button id="pay-btn" type="button"
                        class="inline-flex h-16 w-full items-center justify-center rounded-2xl bg-[linear-gradient(90deg,#7c3aed,#8b5cf6)] px-6 text-[1.02rem] font-semibold text-white shadow-[0_18px_45px_rgba(124,58,237,0.24)] transition max-[375px]:h-14 max-[375px]:px-4 max-[375px]:text-[0.95rem] sm:h-[70px] sm:text-[1.08rem] disabled:cursor-not-allowed disabled:opacity-50"
                        aria-label="Complete payment" disabled style="color:#ffffff !important;">
                    <span id="pay-btn-text">Complete Purchase <span class="sr-only">for {{ ticketly_currency_symbol() }}<span id="final-total">{{ number_format($initialTotal, 2) }}</span></span></span>
                    <span id="pay-spinner" class="hidden">Processing...</span>
                </button>
            </div>

            <aside class="order-1 lg:order-2" aria-label="Order summary">
                <div class="rounded-t-[34px] bg-[#f5f5f7] px-6 pb-8 pt-7 shadow-none max-[375px]:px-3 max-[375px]:pb-6 max-[375px]:pt-5 sm:px-8 lg:hidden lg:rounded-none lg:bg-transparent lg:px-0 lg:pb-0 lg:pt-0">
                    <div class="-mx-6 mb-7 border-b border-slate-200 px-6 pb-5 max-[375px]:-mx-3 max-[375px]:mb-5 max-[375px]:px-3 max-[375px]:pb-4 sm:-mx-8 sm:px-8">
                        <div class="flex items-center justify-between gap-4">
                            <h2 class="text-[1.5rem] font-extrabold tracking-[-0.05em] text-slate-900 max-[375px]:text-[1.3rem] sm:text-[1.7rem]">Checkout</h2>
                            <a href="{{ $ticketSelectionUrl }}" class="inline-flex h-11 w-11 items-center justify-center rounded-full text-slate-400" aria-label="Close checkout">
                                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m6 6 12 12M18 6 6 18"></path>
                                </svg>
                            </a>
                        </div>
                    </div>

                    <div class="mb-6 rounded-[24px] border border-violet-100 bg-white px-5 py-4 max-[375px]:px-4 max-[375px]:py-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-violet-500">Reservation Timer</p>
                        <div class="mt-2 flex items-center justify-between gap-4">
                            <span class="text-[1rem] font-medium text-slate-700 max-[375px]:text-[0.9rem]">Time remaining</span>
                            <span data-countdown-display class="text-[1.2rem] font-extrabold tracking-[0.18em] text-violet-600 max-[375px]:text-[1.05rem] sm:text-[1.3rem]">{{ gmdate('i:s', $reservation->secondsRemaining()) }}</span>
                        </div>
                    </div>

                    <div class="rounded-[26px] border border-slate-200 bg-white px-5 py-6 shadow-[0_10px_30px_rgba(15,23,42,0.04)] max-[375px]:px-4 max-[375px]:py-5">
                        <h3 class="text-[1.1rem] font-extrabold tracking-[-0.03em] text-slate-900 sm:text-[1.2rem]">Order Summary</h3>
                        <div class="mt-8 space-y-5 text-[1rem] max-[375px]:mt-6 max-[375px]:space-y-4 max-[375px]:text-[0.92rem]">
                            @foreach($reservation->items as $item)
                                <div class="flex items-center justify-between gap-4">
                                    <span class="min-w-0 flex-1 break-words pr-2 text-slate-600">{{ $item->ticketTier->name }} <span class="ml-2 text-slate-500">x {{ $item->quantity }}</span></span>
                                    <span class="shrink-0 font-semibold text-slate-900">{{ $item->unit_price == 0 ? 'Free' : ticketly_money($item->subtotal) }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-8 border-t border-slate-200 pt-6 text-[1rem] text-slate-500">
                            <div class="flex items-center justify-between">
                                <span>Subtotal</span>
                                <span data-summary="subtotal" class="font-medium text-slate-900">{{ ticketly_money($reservation->subtotal) }}</span>
                            </div>
                            <div class="mt-4 flex items-center justify-between">
                                <span>Portal Fee ({{ $portalFeePct }}%)</span>
                                <span data-summary="portal-fee" class="font-medium text-slate-900">{{ ticketly_money($initialPricing['portal_fee']) }}</span>
                            </div>
                            <div class="mt-4 flex items-center justify-between">
                                <span>Service Fee ({{ $feePct }}%)</span>
                                <span data-summary="service-fee" class="font-medium text-slate-900">{{ ticketly_money($initialPricing['service_fee']) }}</span>
                            </div>
                            <div data-discount-row class="{{ $initialDiscount > 0 ? '' : 'hidden ' }}mt-4 flex items-center justify-between text-emerald-600">
                                <span>Discount</span>
                                <span data-discount-value>-{{ ticketly_money($initialDiscount) }}</span>
                            </div>
                            <div class="mt-8 flex items-center justify-between text-[1.05rem] font-extrabold text-slate-900 max-[375px]:mt-6 max-[375px]:text-[0.98rem] sm:text-[1.15rem]">
                                <span>Total</span>
                                <span data-summary="total" class="text-[1.2rem] text-violet-600 max-[375px]:text-[1.08rem] sm:text-[1.3rem]">{{ ticketly_money($initialTotal) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6 lg:releteve lg:top-24 sticky ">
                    <div class="hidden space-y-6 lg:block">
                        <div class="rounded-[28px] border border-violet-100 bg-white px-6 py-5 shadow-[0_10px_30px_rgba(15,23,42,0.04)]" style="margin-top:4.3rem;">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-violet-500">Reservation Timer</p>
                            <div class="mt-2 flex items-center justify-between gap-4">
                                <span class="text-[1rem] font-medium text-slate-700">Time remaining</span>
                                <span data-countdown-display class="text-[1.25rem] font-extrabold tracking-[0.18em] text-violet-600">{{ gmdate('i:s', $reservation->secondsRemaining()) }}</span>
                            </div>
                        </div>

                        <div class="rounded-[28px] border border-slate-200 bg-white px-8 py-8 shadow-[0_10px_30px_rgba(15,23,42,0.04)]">
                            <h3 class="text-[1.1rem] font-extrabold tracking-[-0.03em] text-slate-900 sm:text-[1.2rem]">Order Summary</h3>
                            <div class="mt-9 space-y-6 text-[1rem]">
                                @foreach($reservation->items as $item)
                                    <div class="flex items-center justify-between gap-4">
                                        <span class="min-w-0 flex-1 break-words pr-2 text-slate-600">{{ $item->ticketTier->name }} x {{ $item->quantity }}</span>
                                        <span class="shrink-0 font-semibold text-slate-900">{{ $item->unit_price == 0 ? 'Free' : ticketly_money($item->subtotal) }}</span>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-9 border-t border-slate-200 pt-7 text-[1rem] text-slate-500">
                                <div class="flex items-center justify-between">
                                    <span>Subtotal</span>
                                    <span data-summary="subtotal" class="font-medium text-slate-900">{{ ticketly_money($reservation->subtotal) }}</span>
                                </div>
                                <div class="mt-4 flex items-center justify-between">
                                    <span>Portal Fee ({{ $portalFeePct }}%)</span>
                                    <span data-summary="portal-fee" class="font-medium text-slate-900">{{ ticketly_money($initialPricing['portal_fee']) }}</span>
                                </div>
                                <div class="mt-4 flex items-center justify-between">
                                    <span>Service Fee ({{ $feePct }}%)</span>
                                    <span data-summary="service-fee" class="font-medium text-slate-900">{{ ticketly_money($initialPricing['service_fee']) }}</span>
                                </div>
                                <div data-discount-row class="{{ $initialDiscount > 0 ? '' : 'hidden ' }}mt-4 flex items-center justify-between text-emerald-600">
                                    <span>Discount</span>
                                    <span data-discount-value>-{{ ticketly_money($initialDiscount) }}</span>
                                </div>
                                <div class="mt-8 flex items-center justify-between border-t border-slate-200 pt-6 text-[1.05rem] font-extrabold text-slate-900 sm:text-[1.15rem]">
                                    <span>Total</span>
                                    <span data-summary="total" class="text-[1.2rem] text-violet-600 sm:text-[1.3rem]">{{ ticketly_money($initialTotal) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[28px] border border-slate-200 bg-white px-5 py-5 shadow-[0_10px_30px_rgba(15,23,42,0.04)] max-[375px]:px-4 max-[375px]:py-4 sm:px-6">
                        <div class="grid grid-cols-[minmax(0,1fr)_auto] gap-3 max-[375px]:gap-2">
                            <label for="promo-input" class="sr-only">Enter promo code</label>
                            <input id="promo-input" type="text" placeholder="Promo code"
                                   class="h-14 min-w-0 flex-1 rounded-2xl border border-slate-200 bg-white px-4 text-[0.95rem] text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-violet-300 focus:ring-4 focus:ring-violet-100 max-[375px]:h-12 max-[375px]:px-3 max-[375px]:text-[0.9rem] sm:h-16 sm:px-5 sm:text-[1rem]"
                                   style="text-transform:uppercase">
                            <button type="button" id="apply-promo"
                                    class="inline-flex h-14 min-w-[92px] shrink-0 items-center justify-center whitespace-nowrap rounded-2xl border border-slate-200 bg-[#f7f7fb] px-5 text-[0.95rem] font-medium text-slate-700 transition hover:bg-slate-50 max-[375px]:h-12 max-[375px]:min-w-[80px] max-[375px]:px-4 max-[375px]:text-[0.88rem] sm:h-16 sm:px-7 sm:text-[1rem]"
                                    aria-label="Apply promo code">
                                Apply
                            </button>
                        </div>
                        <div id="promo-result" class="mt-3 text-sm" aria-live="polite" role="status"></div>
                    </div>

                    <div class="hidden items-center justify-center gap-3 pt-3 text-[1rem] text-slate-500 lg:flex">
                        <svg class="h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3l7 4v5c0 5-3.5 8-7 9-3.5-1-7-4-7-9V7l7-4Z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m9.5 12 1.7 1.7 3.8-4"></path>
                        </svg>
                        <span>Secure checkout</span>
                    </div>
                </div>

                <button id="mobile-continue-btn" type="button"
                        class="mt-6 inline-flex h-16 w-full items-center justify-center rounded-2xl bg-[linear-gradient(90deg,#7c3aed,#8b5cf6)] px-6 text-[1.02rem] font-medium text-white shadow-[0_18px_45px_rgba(124,58,237,0.24)] max-[375px]:h-14 max-[375px]:text-[0.95rem] sm:h-[70px] sm:text-[1.08rem] lg:hidden" style="color:#ffffff !important;">
                    Continue to Payment
                </button>

                <div class="mt-6 text-center text-[0.95rem] text-slate-400 max-[375px]:text-[0.85rem] lg:hidden">
                    Secure checkout | Tickets delivered instantly
                </div>

                <div class="mt-7 rounded-[28px] border border-slate-200 bg-white px-6 py-7 text-center shadow-[0_10px_30px_rgba(15,23,42,0.04)] max-[375px]:px-4 max-[375px]:py-5 lg:hidden">
                    <div class="flex items-center justify-center gap-3 text-[1rem] font-semibold text-slate-700 max-[375px]:text-[0.9rem]">
                        <svg class="h-8 w-8 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 10V7a5 5 0 0 1 10 0v3"></path>
                            <rect x="5" y="10" width="14" height="10" rx="2" stroke-width="1.8"></rect>
                        </svg>
                        <span>Secure Payment</span>
                    </div>
                    <!-- <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                        <div class="flex h-16 w-[92px] items-center justify-center rounded-2xl border border-slate-200 text-[0.9rem] font-extrabold text-[#2346b7]">VISA</div>
                        <div class="flex h-16 w-[74px] items-center justify-center rounded-2xl border border-slate-200">
                            <span class="h-6 w-6 rounded-full bg-[#ee6b5a] opacity-95"></span>
                            <span class="-ml-2 h-6 w-6 rounded-full bg-[#f3b15d] opacity-95"></span>
                        </div>
                        <div class="flex h-16 w-[94px] items-center justify-center rounded-2xl bg-[#2563eb] text-[0.9rem] font-extrabold text-white">AMEX</div>
                        <div class="flex h-16 w-[104px] items-center justify-center rounded-2xl border border-slate-200 text-[0.9rem] font-semibold text-slate-800">G Pay</div>
                        <div class="flex h-16 w-[70px] items-center justify-center rounded-2xl bg-black text-[0.9rem] font-semibold text-white">Pay</div>
                    </div> -->
                </div>

            </aside>
        </div>
    </div>
    <div id="payment-processing-overlay" class="hidden fixed inset-0 z-50 bg-white/95 backdrop-blur-sm">
        <div class="h-full w-full flex items-center justify-center px-4" role="status" aria-live="polite">
            <div class="text-center max-w-md">
                <div class="w-20 h-20 mx-auto mb-6 rounded-full flex items-center justify-center" style="background:linear-gradient(135deg,rgba(99,102,241,0.2),rgba(139,92,246,0.2));border:2px solid rgba(99,102,241,0.4)">
                    <svg class="w-10 h-10 text-indigo-500 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-extrabold text-slate-900 mb-2">Processing Your Payment</h2>
                <p class="text-slate-600">Please wait, this usually takes a few seconds.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    const intentUrl  = '{{ route('checkout.intent', $reservation->token) }}';
    const statusUrl  = '{{ route('checkout.poll', $reservation->token) }}';
    const eventPageUrl = '{{ route('events.show', $reservation->event->slug) }}';
    const eventUrl   = @js($ticketSelectionUrl);
    const releaseUrl = '{{ route('reservation.release', $reservation->token) }}';
    const csrfToken  = document.querySelector('meta[name=csrf-token]').content;
    let stripe, elements, paymentElement;
    let intentReady = false;
    let paymentInProgress = false;
    let lockPayButton = false;
    let currentIntentId = null;
    let currentClientSecret = null;
    let isExpired = false;
    let promoCode = '';
    let discountAmount = {{ $initialDiscount }};
    let currentTotal = {{ (float) $initialTotal }};
    const subtotal = {{ (float) $reservation->subtotal }};
    const feePct = {{ (int) config('ticketly.service_fee_percentage', 5) }};
    const portalFeePct = {{ (int) config('ticketly.portal_fee_percentage', 10) }};
    const currencySymbol = @js(ticketly_currency_symbol());
    const currentTheme = () => document.documentElement.getAttribute('data-theme-lock') === 'light'
        || document.documentElement.getAttribute('data-theme') === 'light'
        ? 'light'
        : 'dark';

    function stripeAppearance(theme) {
        if (theme === 'light') {
            return {
                theme: 'stripe',
                variables: {
                    colorPrimary: '#7c3aed',
                    colorBackground: '#ffffff',
                    colorText: '#0f172a',
                    colorDanger: '#be123c',
                    borderRadius: '16px',
                    fontFamily: 'Inter, sans-serif',
                },
                rules: {
                    '.Input': { borderColor: '#e2e8f0', boxShadow: 'none' },
                    '.Input:focus': { borderColor: '#c4b5fd', boxShadow: '0 0 0 4px rgba(139,92,246,0.12)' },
                    '.Tab': { borderColor: '#e2e8f0' },
                    '.Tab:hover': { color: '#111827' },
                    '.Tab--selected': { borderColor: '#7c3aed' },
                },
            };
        }

        return {
            theme: 'night',
            variables: {
                colorPrimary: '#7c3aed',
                colorBackground: '#1f2937',
                colorText: '#f9fafb',
                borderRadius: '16px',
                fontFamily: 'Inter, sans-serif',
            },
        };
    }

    function updateCountdownDisplays(seconds) {
        const safeSeconds = Math.max(0, seconds);
        const m = String(Math.floor(safeSeconds / 60)).padStart(2, '0');
        const s = String(safeSeconds % 60).padStart(2, '0');
        document.querySelectorAll('[data-countdown-display]').forEach((el) => {
            el.textContent = m + ':' + s;
            el.setAttribute('aria-label', m + ' minutes ' + s + ' seconds remaining');
        });
    }

    let secs = {{ $reservation->secondsRemaining() }};
    updateCountdownDisplays(secs);
    const tick = setInterval(() => {
        if (isExpired) return;
        if (secs <= 0) {
            clearInterval(tick);
            updateCountdownDisplays(0);
            handleExpiry('Your 10-minute hold has expired. Tickets are now being released.');
            return;
        }
        secs--;
        updateCountdownDisplays(secs);
    }, 1000);

    async function initStripe(data) {
        stripe = Stripe('{{ config('services.stripe.key') }}');
        elements = stripe.elements({
            clientSecret: data.client_secret,
            appearance: stripeAppearance(currentTheme()),
        });
        if (paymentElement) {
            paymentElement.unmount();
        }
        paymentElement = elements.create('payment', { layout: 'tabs' });
        paymentElement.mount('#payment-element');
        currentIntentId = data.intent_id || currentIntentId;
        currentClientSecret = data.client_secret || currentClientSecret;
        paymentElement.on('change', (event) => {
            if (event.error?.message) {
                showError(event.error.message);
            } else {
                hideError();
            }
        });
        intentReady = true;
        updateTotals();
    }

    function setFieldError(fieldId, message) {
        const input = document.getElementById(fieldId);
        const errorEl = document.getElementById(fieldId + '-error');
        if (!input || !errorEl) return;

        if (message) {
            errorEl.textContent = message;
            errorEl.classList.remove('hidden');
            input.classList.add('border-rose-400');
            input.setAttribute('aria-invalid', 'true');
        } else {
            errorEl.textContent = '';
            errorEl.classList.add('hidden');
            input.classList.remove('border-rose-400');
            input.removeAttribute('aria-invalid');
        }
    }

    function setTermsError(message) {
        const termsCheckbox = document.getElementById('terms-checkbox');
        const errorEl = document.getElementById('terms-checkbox-error');
        if (!termsCheckbox || !errorEl) return;

        if (message) {
            errorEl.textContent = message;
            errorEl.classList.remove('hidden');
            termsCheckbox.setAttribute('aria-invalid', 'true');
        } else {
            errorEl.textContent = '';
            errorEl.classList.add('hidden');
            termsCheckbox.removeAttribute('aria-invalid');
        }
    }

    function validateCustomerDetails(showInlineErrors = true) {
        const name  = document.getElementById('customer-name').value.trim();
        const email = document.getElementById('customer-email').value.trim();
        const phone = document.getElementById('customer-phone').value.trim();
        const city = document.getElementById('customer-city').value.trim();
        const state = document.getElementById('customer-state').value.trim();
        const postal = document.getElementById('customer-postal').value.trim();
        const country = document.getElementById('customer-country').value.trim();
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const phonePattern = /^[0-9+\-\s()]{7,20}$/;
        const countryPattern = /^[A-Za-z]{2}$/;

        let valid = true;
        const requireAddress = currentTotal > 0;
        let nameError = '';
        let emailError = '';
        let phoneError = '';
        let cityError = '';
        let stateError = '';
        let postalError = '';
        let countryError = '';

        if (!name) {
            nameError = 'Full name is required.';
            valid = false;
        }

        if (!email) {
            emailError = 'Email address is required.';
            valid = false;
        } else if (!emailPattern.test(email)) {
            emailError = 'Please enter a valid email address.';
            valid = false;
        }

        if (!phone) {
            phoneError = 'Phone number is required.';
            valid = false;
        } else if (!phonePattern.test(phone)) {
            phoneError = 'Please enter a valid phone number.';
            valid = false;
        }

        if (requireAddress) {
            if (!city) {
                cityError = 'City is required.';
                valid = false;
            }

            if (!state) {
                stateError = 'State or province is required.';
                valid = false;
            }

            if (!postal) {
                postalError = 'Postal code is required.';
                valid = false;
            }

            if (!country) {
                countryError = 'Country code is required.';
                valid = false;
            } else if (!countryPattern.test(country)) {
                countryError = 'Use a 2-letter country code (e.g., IN, US, UK).';
                valid = false;
            }
        }

        if (showInlineErrors) {
            setFieldError('customer-name', nameError);
            setFieldError('customer-email', emailError);
            setFieldError('customer-phone', phoneError);
            setFieldError('customer-city', cityError);
            setFieldError('customer-state', stateError);
            setFieldError('customer-postal', postalError);
            setFieldError('customer-country', countryError);
        }

        return valid;
    }

    function validateField(fieldId) {
        const value = document.getElementById(fieldId)?.value.trim() || '';
        const requireAddress = currentTotal > 0;
        let error = '';

        if (fieldId === 'customer-name' && !value) {
            error = 'Full name is required.';
        }

        if (fieldId === 'customer-email') {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!value) error = 'Email address is required.';
            else if (!emailPattern.test(value)) error = 'Please enter a valid email address.';
        }

        if (fieldId === 'customer-phone') {
            const phonePattern = /^[0-9+\-\s()]{7,20}$/;
            if (!value) error = 'Phone number is required.';
            else if (!phonePattern.test(value)) error = 'Please enter a valid phone number.';
        }

        if (requireAddress) {
            if (fieldId === 'customer-city' && !value) {
                error = 'City is required.';
            }

            if (fieldId === 'customer-state' && !value) {
                error = 'State or province is required.';
            }

            if (fieldId === 'customer-postal' && !value) {
                error = 'Postal code is required.';
            }

            if (fieldId === 'customer-country') {
                const countryPattern = /^[A-Za-z]{2}$/;
                if (!value) error = 'Country code is required.';
                else if (!countryPattern.test(value)) error = 'Use a 2-letter country code (e.g., IN, US, UK).';
            }
        }

        setFieldError(fieldId, error);
        return !error;
    }

    function getCustomerDetails() {
        return {
            name: document.getElementById('customer-name').value.trim(),
            email: document.getElementById('customer-email').value.trim(),
            phone: document.getElementById('customer-phone').value.trim(),
            city: document.getElementById('customer-city').value.trim(),
            state: document.getElementById('customer-state').value.trim(),
            postal_code: document.getElementById('customer-postal').value.trim(),
            country: document.getElementById('customer-country').value.trim().toUpperCase(),
        };
    }

    async function fetchIntent(options = {}) {
        const { requireCustomer = true, showLoading = true } = options;
        const { name, email, phone, city, state, postal_code, country } = getCustomerDetails();

        if (isExpired) {
            handleExpiry('Your hold has already expired.');
            return;
        }

        if (paymentInProgress) {
            return;
        }

        if (requireCustomer && !validateCustomerDetails(true)) {
            refreshPayButtonState();
            return;
        }

        if (showLoading) {
            document.getElementById('pay-btn-text').classList.add('hidden');
            document.getElementById('pay-spinner').classList.remove('hidden');
        }

        try {
            const payload = { promo_code: promoCode };
            if (validateCustomerDetails(false)) {
                payload.name = name;
                payload.email = email;
                payload.phone = phone;
                payload.city = city;
                payload.state = state;
                payload.postal_code = postal_code;
                payload.country = country;
            }

            const res = await fetch(intentUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await res.json();

            if (data.error) {
                if (data.expired) {
                    handleExpiry(data.error || 'Your ticket hold has expired.', data.redirect_to);
                    return;
                }
                showError(data.error);
                return;
            }
            if (!res.ok) {
                showError(data.message || 'Unable to prepare payment details right now.');
                return;
            }
            if (data.needs_customer) {
                if (requireCustomer) {
                    showError(data.message || 'Please enter your contact details to continue.');
                }
                setPaymentPlaceholder('No payment method required for free tickets. Enter your contact details to confirm.');
                return;
            }
            if (data.free && data.redirect) {
                showProcessingOverlay();
                window.location.href = data.redirect;
                return;
            }

            discountAmount = data.discount || 0;
            updateSummaryUI(subtotal, discountAmount, data.portal_fee, data.service_fee, data.amount);

            if (!intentReady || (data.client_secret && data.client_secret !== currentClientSecret)) {
                await initStripe(data);
            }
            setPaymentPlaceholder('');
            return data;
        } finally {
            if (showLoading) {
                document.getElementById('pay-btn-text').classList.remove('hidden');
                document.getElementById('pay-spinner').classList.add('hidden');
            }
            refreshPayButtonState();
        }
    }

    function refreshPayButtonState() {
        const payBtn = document.getElementById('pay-btn');
        const termsCheckbox = document.getElementById('terms-checkbox');
        if (!payBtn) return;
        if (lockPayButton) {
            payBtn.disabled = true;
            return;
        }
        const termsAccepted = !!termsCheckbox?.checked;
        const valid = validateCustomerDetails(false);
        payBtn.disabled = isExpired || paymentInProgress || !termsAccepted || !valid;
    }

    let detailsTimer;
    ['customer-name', 'customer-email', 'customer-phone', 'customer-city', 'customer-state', 'customer-postal', 'customer-country'].forEach((id) => {
        const field = document.getElementById(id);
        if (!field) return;

        field.addEventListener('input', () => {
            validateField(id);
            refreshPayButtonState();
        });

        field.addEventListener('blur', () => {
            validateField(id);
            refreshPayButtonState();
            if (!validateCustomerDetails(false) || isExpired || paymentInProgress) return;
            clearTimeout(detailsTimer);
            detailsTimer = setTimeout(() => fetchIntent({ requireCustomer: true, showLoading: false }), 300);
        });
    });

    document.getElementById('terms-checkbox')?.addEventListener('change', (event) => {
        if (event.target.checked) {
            setTermsError('');
        }
        refreshPayButtonState();
    });

    document.getElementById('apply-promo')?.addEventListener('click', async () => {
        if (isExpired) {
            handleExpiry('Your hold has expired. Promo code can no longer be applied.');
            return;
        }

        const code = document.getElementById('promo-input').value.trim().toUpperCase();
        if (!code) return;

        const res = await fetch('{{ route('promo.validate') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ code, subtotal, event_id: {{ $reservation->event_id }} }),
        });
        const data = await res.json();
        const resultEl = document.getElementById('promo-result');

        if (data.valid) {
            promoCode = code;
            resultEl.textContent = 'Applied: ' + data.message;
            resultEl.className = 'mt-3 text-sm text-emerald-600';
            await fetchIntent({ requireCustomer: false, showLoading: false });
        } else {
            resultEl.textContent = 'Payment discount unavailable: ' + data.message;
            resultEl.className = 'mt-3 text-sm text-rose-600';
        }
    });

    document.getElementById('mobile-continue-btn')?.addEventListener('click', () => {
        document.getElementById('contact-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    document.getElementById('pay-btn')?.addEventListener('click', async () => {
        if (lockPayButton) return;
        lockPayButton = true;
        refreshPayButtonState();

        if (isExpired) {
            handleExpiry('Payment is disabled because your hold has expired.');
            lockPayButton = false;
            refreshPayButtonState();
            return;
        }

        if (!document.getElementById('terms-checkbox')?.checked) {
            setTermsError('Please accept Terms of Service and Privacy Policy before checkout.');
            lockPayButton = false;
            refreshPayButtonState();
            return;
        }
        setTermsError('');

        if (!validateCustomerDetails(true)) {
            lockPayButton = false;
            refreshPayButtonState();
            return;
        }
        const intentData = await fetchIntent({ requireCustomer: true, showLoading: false });
        if (!intentData || !intentReady) {
            lockPayButton = false;
            refreshPayButtonState();
            return;
        }

        paymentInProgress = true;
        clearTimeout(detailsTimer);
        refreshPayButtonState();

        document.getElementById('pay-btn-text').classList.add('hidden');
        document.getElementById('pay-spinner').classList.remove('hidden');
        hideError();
        showProcessingOverlay();

        try {
            const customerDetails = getCustomerDetails();
            const { error, paymentIntent } = await stripe.confirmPayment({
                elements,
                confirmParams: {
                    return_url: '{{ route('checkout.success', $reservation->token) }}',
                    receipt_email: customerDetails.email,
                    payment_method_data: {
                        billing_details: {
                            name: customerDetails.name,
                            email: customerDetails.email,
                            phone: customerDetails.phone,
                            address: {
                                city: customerDetails.city,
                                state: customerDetails.state,
                                postal_code: customerDetails.postal_code,
                                country: customerDetails.country,
                            },
                        },
                    },
                },
                redirect: 'if_required',
            });

            document.getElementById('pay-btn-text').classList.remove('hidden');
            document.getElementById('pay-spinner').classList.add('hidden');
            paymentInProgress = false;
            refreshPayButtonState();

            if (error) {
                showError(error.message || 'Payment could not be processed. Please try again.');
                hideProcessingOverlay();
                lockPayButton = false;
                refreshPayButtonState();
                return;
            }

            if (paymentIntent && ['succeeded', 'processing'].includes(paymentIntent.status)) {
                window.location.href = "{{ route('checkout.success', $reservation->token) }}";
                return;
            }

            window.location.href = "{{ route('checkout.success', $reservation->token) }}";
        } catch (e) {
            document.getElementById('pay-btn-text').classList.remove('hidden');
            document.getElementById('pay-spinner').classList.add('hidden');
            paymentInProgress = false;
            lockPayButton = false;
            refreshPayButtonState();
            hideProcessingOverlay();
            showError('We could not confirm your payment right now. Please try again.');
        }
    });

    function showError(msg) {
        const el = document.getElementById('payment-message');
        if (!el) return;
        el.textContent = msg;
        el.classList.remove('hidden');
    }

    function hideError() {
        const el = document.getElementById('payment-message');
        if (!el) return;
        el.textContent = '';
        el.classList.add('hidden');
    }

    function setPaymentPlaceholder(message) {
        const placeholder = document.getElementById('payment-placeholder');
        const element = document.getElementById('payment-element');
        if (!placeholder || !element) return;

        if (message) {
            placeholder.textContent = message;
            placeholder.classList.remove('hidden');
            element.classList.add('hidden');
        } else {
            placeholder.textContent = '';
            placeholder.classList.add('hidden');
            element.classList.remove('hidden');
        }
    }

    function showProcessingOverlay() {
        document.getElementById('payment-processing-overlay')?.classList.remove('hidden');
    }

    function hideProcessingOverlay() {
        document.getElementById('payment-processing-overlay')?.classList.add('hidden');
    }

    function normalizeRedirect(redirectTo) {
        if (!redirectTo) return eventUrl;
        return redirectTo === eventPageUrl ? eventUrl : redirectTo;
    }

    function setCheckoutDisabled(disabled) {
        const payBtn = document.getElementById('pay-btn');
        const applyPromoBtn = document.getElementById('apply-promo');
        const continueBtn = document.getElementById('mobile-continue-btn');
        const editableIds = ['customer-name', 'customer-email', 'customer-phone', 'customer-city', 'customer-state', 'customer-postal', 'customer-country', 'promo-input', 'terms-checkbox'];

        if (payBtn) payBtn.disabled = disabled;
        if (applyPromoBtn) applyPromoBtn.disabled = disabled;
        if (continueBtn) continueBtn.disabled = disabled;
        editableIds.forEach((id) => {
            const input = document.getElementById(id);
            if (input) input.disabled = disabled;
        });
    }

    async function releaseHoldSilently() {
        try {
            await fetch(releaseUrl, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            });
        } catch (e) {
            // Best-effort release; backend also releases on expiry checks.
        }
    }

    function handleExpiry(message, redirectTo = eventUrl) {
        if (isExpired) return;
        isExpired = true;
        clearInterval(tick);
        setCheckoutDisabled(true);

        const alertEl = document.getElementById('hold-expired-alert');
        const msgEl = document.getElementById('hold-expired-message');
        const returnLink = document.getElementById('hold-expired-return-link');
        const fallbackMsg = 'Your hold has expired. Tickets will be released and payment will not be processed.';

        const finalRedirect = normalizeRedirect(redirectTo);

        updateCountdownDisplays(0);
        if (msgEl) msgEl.textContent = message || fallbackMsg;
        if (returnLink) returnLink.href = finalRedirect;
        if (alertEl) alertEl.classList.remove('hidden');

        showError('Your hold expired. Payment is disabled and no payment will be taken.');
        releaseHoldSilently();
        setTimeout(() => { window.location.href = finalRedirect; }, 4000);
    }

    async function syncReservationStatus() {
        if (isExpired) return;
        try {
            const res = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();

            if (data.status === 'expired') {
                handleExpiry(data.message || 'Your hold has expired.', data.redirect_to);
                return;
            }

            if (typeof data.expires === 'number') {
                secs = Math.max(0, data.expires);
                updateCountdownDisplays(secs);
            }
        } catch (e) {
            showError('We could not verify your reservation status right now. Please check your connection and continue checkout promptly.');
        }
    }

    function updateSummaryUI(sub, disc, portalFee, serviceFee, total) {
        const resolvedTotal = Number.isFinite(total) ? total : sub;

        document.querySelectorAll('[data-summary="subtotal"]').forEach((el) => {
            el.textContent = currencySymbol + sub.toFixed(2);
        });
        document.querySelectorAll('[data-summary="portal-fee"]').forEach((el) => {
            el.textContent = currencySymbol + (portalFee || 0).toFixed(2);
        });
        document.querySelectorAll('[data-summary="service-fee"]').forEach((el) => {
            el.textContent = currencySymbol + (serviceFee || 0).toFixed(2);
        });
        document.querySelectorAll('[data-summary="total"]').forEach((el) => {
            el.textContent = currencySymbol + resolvedTotal.toFixed(2);
        });
        document.querySelectorAll('[data-discount-row]').forEach((row) => {
            row.classList.toggle('hidden', !(disc > 0));
        });
        document.querySelectorAll('[data-discount-value]').forEach((el) => {
            el.textContent = '-' + currencySymbol + disc.toFixed(2);
        });

        const finalTotal = document.getElementById('final-total');
        if (finalTotal) {
            finalTotal.textContent = resolvedTotal.toFixed(2);
        }
    }

    function updateTotals() {
        const portalFee = parseFloat((subtotal * portalFeePct / 100).toFixed(2));
        const serviceFee = parseFloat((subtotal * feePct / 100).toFixed(2));
        const grossTotal = parseFloat((subtotal + portalFee + serviceFee).toFixed(2));
        const total = Math.max(0, parseFloat((grossTotal - discountAmount).toFixed(2)));
        currentTotal = total;
        updateSummaryUI(subtotal, discountAmount, portalFee, serviceFee, total);
    }

    updateTotals();
    fetchIntent({ requireCustomer: false, showLoading: false });
    syncReservationStatus();
    setInterval(syncReservationStatus, 5000);

    window.addEventListener('ticketly:theme-changed', function (event) {
        if (!elements) return;
        const theme = event?.detail?.theme === 'dark' ? 'dark' : 'light';
        elements.update({ appearance: stripeAppearance(theme) });
    });
})();
</script>
@endsection
