@extends('layouts.app')
@section('title', 'Create Organiser Account')
@section('content')
<div class="min-h-screen flex items-center justify-center px-4 py-16">
  <div class="w-full max-w-lg">
    <!-- Logo -->
    <div class="text-center mb-8">
      <a href="{{ route('home') }}" class="inline-flex items-center gap-2">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:linear-gradient(135deg,#6366f1,#8b5cf6)">
          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
        </div>
        <span class="text-xl font-extrabold text-white">Ticketly</span>
      </a>
      <h1 class="text-2xl font-extrabold text-white mt-4">Become an Organiser</h1>
      <p class="text-gray-400 text-sm mt-1">Create your account – approvals are reviewed within 24 hours</p>
    </div>

    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-8">
      <form action="{{ route('organiser.register') }}" method="POST" class="space-y-4">
        @csrf

        @if($errors->any())
        <div class="bg-red-900/40 border border-red-700/50 rounded-xl p-4">
          @foreach($errors->all() as $e)
          <div class="text-red-300 text-sm">• {{ $e }}</div>
          @endforeach
        </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Your Full Name *</label>
            <input type="text" name="name" value="{{ old('name') }}" required
                   class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Jane Smith">
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Company / Brand Name *</label>
            <input type="text" name="company_name" value="{{ old('company_name') }}" required
                   class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Live Events Ltd">
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Email Address *</label>
          <input type="email" name="email" value="{{ old('email') }}" required
                 class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="you@company.com">
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Phone Number</label>
          <input type="tel" name="phone" value="{{ old('phone') }}"
                 class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="+44 7700 900000">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Password *</label>
            <input type="password" name="password" required minlength="8"
                   class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Min. 8 characters">
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Confirm Password *</label>
            <input type="password" name="password_confirmation" required
                   class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Repeat password">
          </div>
        </div>

        <button type="submit" class="w-full py-3.5 font-extrabold text-white rounded-xl transition-all text-sm" style="background:linear-gradient(135deg,#6366f1,#8b5cf6)">
          Create Account →
        </button>

        <p class="text-center text-xs text-gray-500">Already have an account? <a href="{{ route('organiser.login') }}" class="text-indigo-400 hover:text-indigo-300">Sign in</a></p>
      </form>
    </div>
  </div>
</div>
@endsection
