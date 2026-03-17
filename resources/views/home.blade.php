@extends('layouts.app')
@section('title', 'Ticketly - Discover & Book Events')

@section('content')

{{-- HERO --}}
<section class="relative overflow-hidden" style="background:linear-gradient(135deg,#0a0a1a 0%,#1a0a2e 40%,#0d1a3a 100%)" aria-label="Hero">
  {{-- Ambient orbs --}}
  <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
    <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full opacity-20" style="background:radial-gradient(circle,#6366f1,transparent)"></div>
    <div class="absolute top-0 right-0 w-80 h-80 rounded-full opacity-15" style="background:radial-gradient(circle,#ec4899,transparent)"></div>
    <div class="absolute bottom-0 left-1/2 w-64 h-64 rounded-full opacity-10" style="background:radial-gradient(circle,#8b5cf6,transparent)"></div>
  </div>

  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 md:py-32">
    <div class="text-center max-w-4xl mx-auto">
      <div class="inline-flex items-center gap-2 bg-white/10 border border-white/20 rounded-full px-4 py-2 text-sm text-indigo-300 font-medium mb-6 backdrop-blur-sm">
        <span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse" aria-hidden="true"></span>
        {{ $stats['upcoming_events'] }} upcoming events near you
      </div>
      <h1 class="text-4xl sm:text-5xl md:text-7xl font-black text-white mb-6 leading-tight">
        Find Your Next
        <span class="block" style="background:linear-gradient(90deg,#818cf8,#c084fc,#f472b6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">
          Unforgettable Night
        </span>
      </h1>
      <p class="text-lg md:text-xl text-gray-400 mb-10 max-w-2xl mx-auto leading-relaxed">Discover concerts, festivals, comedy nights and more. Book in seconds with our secure, mobile-first checkout.</p>

      {{-- Search --}}
      <form action="{{ route('events.index') }}" method="GET" role="search" class="max-w-2xl mx-auto">
        <div class="flex gap-2 bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-2">
          <label for="hero-search" class="sr-only">Search events</label>
          <input id="hero-search" type="text" name="search" placeholder="Search events, artists, venues..."
                 class="flex-1 bg-transparent text-black placeholder-gray-400 px-4 py-3 text-sm focus:outline-none min-w-0"
                 aria-label="Search events">
          <button type="submit" class="flex-shrink-0 font-bold text-white px-6 py-3 rounded-xl text-sm transition-all focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-transparent"
                  style="background:linear-gradient(135deg,#6366f1,#8b5cf6)">
            Search
          </button>
        </div>
      </form>

      {{-- Category pills --}}
      <nav class="flex flex-wrap gap-2 justify-center mt-6" aria-label="Event categories">
        @foreach($categories->take(6) as $cat => $count)
        <a href="{{ route('events.index', ['category' => $cat]) }}"
           class="px-4 py-2 rounded-full text-sm font-medium text-gray-300 border border-white/10 hover:border-indigo-500/50 hover:text-white hover:bg-indigo-600/10 transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500"
           aria-label="Browse {{ $cat }} events ({{ $count }})">{{ $cat }} <span class="text-gray-500">({{ $count }})</span></a>
        @endforeach
      </nav>
    </div>
  </div>
</section>

{{-- ALL EVENTS GRID --}}
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16" aria-labelledby="all-events-heading">
  <div class="flex items-center justify-between mb-8">
    <div>
      <h2 id="all-events-heading" class="text-2xl md:text-3xl font-extrabold text-white">All Events</h2>
    </div>
    <a href="{{ route('events.index') }}" class="text-sm font-semibold text-indigo-400 hover:text-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded">See all -&gt;</a>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
    @forelse($events as $event)
      @php
        $lowestPrice = $event->ticketTiers->where('is_active', true)->min('price');
        $isFree = $lowestPrice === null || $lowestPrice == 0;
      @endphp

      <article class="group bg-gray-900 border border-gray-800 rounded-[1.35rem] overflow-hidden hover:border-indigo-500/40 transition-all duration-300 hover:-translate-y-1 flex flex-col"
               aria-label="{{ $event->title }}">
        <a href="{{ route('events.show', $event->slug) }}" class="block relative h-48 overflow-hidden" aria-label="View {{ $event->title }}">
          @if($event->banner_url)
            <img src="{{ $event->banner_url }}" alt="Banner image for {{ $event->title }}"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
          @else
            <div class="w-full h-full flex items-center justify-center" style="background:linear-gradient(135deg,#4f46e5,#7c3aed,#db2777)">
              <span class="text-5xl" aria-hidden="true">🎟</span>
            </div>
          @endif
          <div class="absolute inset-0 bg-gradient-to-t from-gray-900/85 via-gray-900/15 to-transparent" aria-hidden="true"></div>

          @if($event->is_featured)
            <span class="absolute top-3 left-3 inline-flex items-center gap-1 rounded-full bg-amber-400 px-3 py-1 text-xs font-extrabold text-amber-950">
              <span aria-hidden="true">★</span>
              <span>Featured</span>
            </span>
          @endif

          <div class="absolute top-3 right-3">
            @if($isFree)
              <span class="bg-emerald-600 text-white text-xs font-extrabold px-2.5 py-1 rounded-full">Free</span>
            @else
              <span class="text-white text-xs font-extrabold px-2.5 py-1 rounded-full" style="background:linear-gradient(135deg,#6366f1,#8b5cf6)">From {{ ticketly_money($lowestPrice) }}</span>
            @endif
          </div>
        </a>

        <div class="p-5 flex-1 flex flex-col">
          <h3 class="font-extrabold text-white text-base mb-2 line-clamp-2 group-hover:text-indigo-300 transition-colors">
            <a href="{{ route('events.show', $event->slug) }}" class="focus:outline-none focus:underline">{{ $event->title }}</a>
          </h3>

          <p class="text-gray-400 text-sm mb-1.5">
            <time datetime="{{ $event->starts_at->toISOString() }}">{{ $event->starts_at->format('l M j, Y') }}</time>
            <span> &middot; {{ $event->starts_at->format('g:ia') }}</span>
          </p>

          <p class="text-gray-500 text-sm mb-4 line-clamp-1">{{ $event->venue_name }}, {{ $event->city }}</p>

          <div class="mt-auto">
            <a href="{{ route('events.show', $event->slug) }}"
               class="block text-center text-sm font-bold text-white py-3 rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500"
               style="background:linear-gradient(135deg,#6366f1,#8b5cf6)"
               aria-label="Book tickets for {{ $event->title }}">
              View Details
            </a>
          </div>
        </div>
      </article>
    @empty
      <div class="col-span-full text-center py-20">
        <p class="text-gray-500 text-lg">No upcoming events. Check back soon!</p>
      </div>
    @endforelse
  </div>
</section>

{{-- HOW IT WORKS --}}
<section class="border-t border-gray-800 py-20" style="background:#0d0d1f" aria-labelledby="how-it-works-heading">
  <div class="max-w-5xl mx-auto px-4 text-center">
    <h2 id="how-it-works-heading" class="text-2xl md:text-3xl font-extrabold text-white mb-3">How Ticketly Works</h2>
    <p class="text-gray-400 mb-12">Three steps to your next great experience</p>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      @foreach([
        ['🔍','Find Your Event','Browse hundreds of events by category, city, or keyword.'],
        ['🎟','Book Securely','Secure your spot in 60 seconds with our Stripe-powered checkout.'],
        ['✉️','Get Your Ticket','Receive an instant email confirmation with your printable ticket.'],
      ] as [$icon,$title,$desc])
      <div class="bg-gray-900/50 border border-gray-800 rounded-2xl p-8">
        <div class="text-5xl mb-4" aria-hidden="true">{{ $icon }}</div>
        <h3 class="font-extrabold text-white text-lg mb-2">{{ $title }}</h3>
        <p class="text-gray-400 text-sm leading-relaxed">{{ $desc }}</p>
      </div>
      @endforeach
    </div>
  </div>
</section>

@endsection
