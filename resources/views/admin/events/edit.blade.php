@extends('layouts.admin')

@section('title', 'Edit Event')
@section('page-title', 'Edit Event')
@section('page-subtitle', $event->title)

@section('content')
<div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
  <form method="POST" action="{{ route('admin.events.update', $event->id) }}" enctype="multipart/form-data" class="space-y-5">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <div>
        <label class="text-xs uppercase tracking-wider text-gray-400">Title</label>
        <input type="text" name="title" value="{{ old('title', $event->title) }}" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2 text-sm text-white" required>
      </div>
      <div>
        <label class="text-xs uppercase tracking-wider text-gray-400">Category</label>
        <select name="category" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2 text-sm text-white" required>
          @foreach(\App\Models\Event::CATEGORIES as $cat)
            <option value="{{ $cat }}" @selected(old('category', $event->category) === $cat)>{{ $cat }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="text-xs uppercase tracking-wider text-gray-400">Start</label>
        <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $event->starts_at?->format('Y-m-d\\TH:i')) }}" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2 text-sm text-white" required>
      </div>
      <div>
        <label class="text-xs uppercase tracking-wider text-gray-400">End</label>
        <input type="datetime-local" name="ends_at" value="{{ old('ends_at', $event->ends_at?->format('Y-m-d\\TH:i')) }}" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2 text-sm text-white" required>
      </div>
      <div>
        <label class="text-xs uppercase tracking-wider text-gray-400">Venue Name</label>
        <input type="text" name="venue_name" value="{{ old('venue_name', $event->venue_name) }}" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2 text-sm text-white" required>
      </div>
      
      <div>
        <label class="text-xs uppercase tracking-wider text-gray-400">City</label>
        <input type="text" name="city" value="{{ old('city', $event->city) }}" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2 text-sm text-white" required>
      </div>
      <div class="md:col-span-2">
        <label class="text-xs uppercase tracking-wider text-gray-400">Address</label>
        <input type="text" name="venue_address" value="{{ old('venue_address', $event->venue_address) }}" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2 text-sm text-white" required>
      </div>
      
      <div>
        <label class="text-xs uppercase tracking-wider text-gray-400">Postcode</label>
        <input type="text" name="postcode" value="{{ old('postcode', $event->postcode) }}" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2 text-sm text-white">
      </div>
      
     
      <!-- <div class="md:col-span-2">
        <label class="text-xs uppercase tracking-wider text-gray-400">Status</label>
        <select name="status" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2 text-sm text-white">
          <option value="draft" @selected(old('status', $event->status) === 'draft')>Draft</option>
          <option value="published" @selected(old('status', $event->status) === 'published')>Published</option>
          <option value="cancelled" @selected(old('status', $event->status) === 'cancelled')>Cancelled</option>
        </select>
      </div> -->
      <div class="md:col-span-2">
        <label class="text-xs uppercase tracking-wider text-gray-400">Short Description</label>
        <textarea name="short_description" rows="3" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2 text-sm text-white">{{ old('short_description', $event->short_description) }}</textarea>
      </div>
      <div class="md:col-span-2">
        <label class="text-xs uppercase tracking-wider text-gray-400">Description</label>
        @php($descriptionValue = old('description') ?? strip_tags($event->description ?? ''))
        <textarea name="description" rows="6" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2 text-sm text-white">{{ $descriptionValue }}</textarea>
        
      </div>
      <div class="md:col-span-2">
        <label class="text-xs uppercase tracking-wider text-gray-400">Parking Info</label>
        @php($parkingValue = old('parking_info') ?? trim(html_entity_decode(strip_tags($event->parking_info ?? ''))))
        <textarea name="parking_info" rows="3" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2 text-sm text-white">{{ $parkingValue }}</textarea>
      </div>
      <div class="md:col-span-2">
        <label class="text-xs uppercase tracking-wider text-gray-400">Refund Policy</label>
        @php($refundValue = old('refund_policy') ?? trim(html_entity_decode(strip_tags($event->refund_policy ?? ''))))
        <textarea name="refund_policy" rows="3" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2 text-sm text-white">{{ $refundValue }}</textarea>
      </div>
      <div class="md:col-span-2">
        <label class="text-xs uppercase tracking-wider text-gray-400">Banner</label>
        <input type="file" name="banner" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2 text-sm text-white">
        @if($event->banner_url)
          <div class="mt-3">
            <img src="{{ $event->banner_url }}" alt="Current banner" class="max-h-48 rounded-xl border border-gray-800">
          </div>
        @endif
      </div>
    </div>

    <div class="flex items-center gap-3">
      <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $event->is_featured))>
      <span class="text-sm text-gray-400">Featured event</span>
    </div>

    <div class="flex justify-end gap-3">
      <a href="{{ route('admin.events.show', $event->id) }}" class="px-4 py-2 rounded-xl border border-gray-700 text-gray-300 text-sm">Cancel</a>
      <button class="px-5 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold">Save Changes</button>
    </div>
  </form>
</div>
@endsection
