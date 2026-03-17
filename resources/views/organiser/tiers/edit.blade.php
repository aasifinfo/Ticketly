@extends('layouts.organiser')
@section('title', 'Edit Tier')
@section('page-title', 'Edit Ticket Tier')
@section('page-subtitle', $event->title . ' – ' . $tier->name)

@section('content')
<div class="max-w-xl w-full">
<div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 sm:p-8">
  @if($errors->any())
  <div class="bg-red-900/40 border border-red-700/50 rounded-xl p-4 mb-5">
    @foreach($errors->all() as $e)<div class="text-red-300 text-sm">• {{ $e }}</div>@endforeach
  </div>
  @endif
  <form action="{{ route('organiser.tiers.update', [$event->id, $tier->id]) }}" method="POST" class="space-y-4">
    @csrf @method('PUT')
    <div><label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Tier Name *</label>
    <input type="text" name="name" value="{{ old('name', $tier->name) }}" required class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"></div>
    <div><label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Description</label>
    <textarea name="description" rows="2" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none">{{ old('description', $tier->description) }}</textarea></div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div><label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Price ({{ ticketly_currency_symbol() }}) *</label>
      <input type="number" name="price" value="{{ old('price', $tier->price) }}" step="0.01" min="0" required class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"></div>
      <div>
        <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Total Quantity *</label>
        <input type="number" name="total_quantity" value="{{ old('total_quantity', $tier->total_quantity) }}" min="{{ $tier->sold_quantity }}" required class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
        <p class="text-xs text-gray-600 mt-1">{{ $tier->sold_quantity }} already sold – cannot go below this.</p>
      </div>
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div><label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Min Per Order</label>
      <input type="number" name="min_per_order" value="{{ old('min_per_order', $tier->min_per_order) }}" min="1" max="20" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"></div>
      <div><label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Max Per Order</label>
      <input type="number" name="max_per_order" value="{{ old('max_per_order', $tier->max_per_order) }}" min="1" max="20" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"></div>
    </div>
    <div class="flex items-center gap-3">
      <input type="hidden" name="is_active" value="0">
      <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $tier->is_active) ? 'checked' : '' }} class="w-4 h-4 rounded border-gray-600 bg-gray-700 text-indigo-600 focus:ring-indigo-500">
      <label for="is_active" class="text-sm text-gray-300">Active (visible for purchase)</label>
    </div>
    <div class="flex flex-col gap-3 pt-2 sm:flex-row">
      <button type="submit" class="px-6 py-3 text-sm font-extrabold text-white rounded-xl" style="background:linear-gradient(135deg,#6366f1,#8b5cf6)">Save Changes</button>
      <a href="{{ route('organiser.tiers.index', $event->id) }}" class="px-5 py-3 text-sm font-semibold text-gray-400 bg-gray-800 hover:bg-gray-700 rounded-xl transition-colors">Cancel</a>
    </div>
  </form>
</div>
</div>
@endsection
