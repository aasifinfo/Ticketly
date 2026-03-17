@extends('layouts.organiser')
@section('title', 'Create Event')
@section('page-title', 'Create New Event')
@section('page-subtitle', 'Fill in the details for your new event')
@section('body-class', 'event-create-page')

@section('head')
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<style>
  .event-create-page {
    background-image: none !important;
  }

  .event-create-shell {
    max-width: 64rem;
    margin: 0;
    width: 100%;
  }

  .event-create-form {
    display: grid;
    gap: 1rem;
  }

  .event-card {
    background: var(--event-card-bg);
    border: 1px solid var(--event-card-border);
    border-radius: 1rem;
    box-shadow: var(--event-card-shadow);
    padding: 1rem;
  }

  .event-card__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
  }

  .event-card__title-wrap {
    display: flex;
    align-items: flex-start;
    gap: 0.7rem;
  }

  .event-card__icon {
    width: 1.15rem;
    height: 1.15rem;
    color: #7c3aed;
    flex-shrink: 0;
    margin-top: 0.12rem;
  }

  .event-card__icon svg {
    width: 100%;
    height: 100%;
  }

  .event-card__title {
    font-size: 1.02rem;
    font-weight: 800;
    line-height: 1.2;
    letter-spacing: -0.02em;
    color: var(--event-heading);
  }

  .event-card__subtitle {
    margin-top: 0.18rem;
    font-size: 0.74rem;
    color: var(--event-muted);
  }

  .event-grid {
    display: grid;
    gap: 0.85rem;
  }

  .event-grid--2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .event-grid--3 {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .event-field {
    display: grid;
    gap: 0.4rem;
    min-width: 0;
  }

  .event-field--span-2 {
    grid-column: span 2;
  }

  .event-label {
    font-size: 0.72rem;
    font-weight: 700;
    line-height: 1.2;
    color: var(--event-label);
  }

  .event-input,
  .event-select,
  .event-textarea {
    width: 100%;
    border: 1px solid var(--event-input-border);
    border-radius: 0.45rem;
    background: var(--event-input-bg);
    color: var(--event-heading);
    font-size: 0.8rem;
    line-height: 1.5;
    padding: 0.62rem 0.75rem;
    outline: none;
    transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
  }

  .event-input::placeholder,
  .event-textarea::placeholder {
    color: var(--event-placeholder);
  }

  .event-input:focus,
  .event-select:focus,
  .event-textarea:focus {
    border-color: rgba(124, 58, 237, 0.45);
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.12);
  }

  .event-textarea {
    min-height: 4.75rem;
    resize: vertical;
  }

  .event-upload {
    border: 1px solid var(--event-card-border);
    border-radius: 0.85rem;
    overflow: hidden;
    background: var(--event-upload-bg);
    margin-bottom: 1rem;
  }

  .event-upload__band {
    height: 1.9rem;
    background: linear-gradient(90deg, #6d28d9 0%, #c026d3 55%, #ec4899 100%);
  }

  .event-upload__body {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem;
  }

  .event-upload__title {
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--event-heading);
    margin-bottom: 0.3rem;
  }

  .event-upload__copy {
    font-size: 0.73rem;
    color: var(--event-muted);
    max-width: 23rem;
    line-height: 1.6;
  }

  .event-upload__action {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.62rem 0.95rem;
    border: 1px solid rgba(124, 58, 237, 0.35);
    border-radius: 0.55rem;
    background: #ffffff;
    color: #7c3aed;
    font-size: 0.72rem;
    font-weight: 700;
    white-space: nowrap;
    cursor: pointer;
  }

  .event-upload__action input {
    display: none;
  }

  .event-upload__meta {
    margin-top: 0.2rem;
    font-size: 0.65rem;
    color: var(--event-placeholder);
  }

  .event-hint {
    font-size: 0.7rem;
    color: var(--event-muted);
    text-align: right;
    margin: -0.35rem 0 0.85rem;
  }

  .event-inline-note {
    font-size: 0.68rem;
    color: var(--event-muted);
  }

  .event-section-divider {
    height: 1px;
    background: var(--event-divider);
    margin: 0.2rem 0 0.55rem;
  }

  .event-lineup-list {
    display: grid;
    gap: 0.7rem;
  }

  .event-lineup-row {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(0, 1.5fr) minmax(0, 1fr) 2.6rem;
    gap: 0.65rem;
    align-items: end;
  }

  .event-icon-btn,
  .event-add-btn,
  .event-secondary-btn,
  .event-primary-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    border-radius: 0.55rem;
    font-size: 0.75rem;
    font-weight: 700;
    transition: all 0.2s ease;
  }

  .event-icon-btn {
    width: 2.6rem;
    height: 2.55rem;
    border: 1px solid var(--event-input-border);
    background: var(--event-input-bg);
    color: var(--event-muted);
  }

  .event-add-btn,
  .event-secondary-btn {
    border: 1px solid var(--event-input-border);
    background: var(--event-input-bg);
    color: var(--event-heading);
    padding: 0.62rem 0.95rem;
  }

  .event-primary-btn {
    border: 0;
    background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
    color: #ffffff;
    padding: 0.7rem 1rem;
    box-shadow: 0 12px 24px rgba(124, 58, 237, 0.22);
  }

  .event-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.7rem;
    padding-top: 0.2rem;
  }

  .event-error-card {
    background: rgba(239, 68, 68, 0.08);
    border: 1px solid rgba(239, 68, 68, 0.18);
    border-radius: 1rem;
    padding: 0.9rem 1rem;
    display: grid;
    gap: 0.35rem;
  }

  .event-error-card div {
    color: #dc2626;
    font-size: 0.8rem;
  }

  .event-status-wrap {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.85rem;
    margin-bottom: 0.85rem;
  }

  .event-status-wrap .event-field {
    width: 100%;
  }

  .event-status-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.7rem;
    border-radius: 999px;
    background: #f3f4f6;
    color: #6b7280;
    font-size: 0.68rem;
    font-weight: 700;
    white-space: nowrap;
  }

  .event-create-page .ck.ck-editor {
    width: 100%;
  }

  .event-create-page .ck.ck-toolbar {
    border: 1px solid var(--event-input-border);
    border-bottom: 0;
    border-radius: 0.75rem 0.75rem 0 0;
    background: var(--event-input-bg);
  }

  .event-create-page .ck.ck-editor__main > .ck-editor__editable {
    min-height: 11rem;
    border: 1px solid var(--event-input-border);
    border-radius: 0 0 0.75rem 0.75rem;
    background: var(--event-input-bg);
    color: var(--event-heading);
    box-shadow: none;
  }

  .event-create-page .ck.ck-editor__main > .ck-editor__editable:focus {
    border-color: rgba(124, 58, 237, 0.45);
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.12);
  }

  @media (max-width: 767px) {
    .event-create-shell {
      max-width: 100%;
    }

    .event-grid--2,
    .event-grid--3,
    .event-lineup-row {
      grid-template-columns: 1fr;
    }

    .event-field--span-2 {
      grid-column: auto;
    }

    .event-upload__body,
    .event-status-wrap,
    .event-actions,
    .event-card__head {
      flex-direction: column;
      align-items: stretch;
    }

    .event-hint {
      text-align: left;
      margin-top: 0;
    }

    .event-actions > * {
      width: 100%;
    }

    .event-icon-btn {
      width: 100%;
    }
  }

  @media (max-width: 479px) {
    .event-card {
      padding: 0.85rem;
    }

    .event-card__title {
      font-size: 0.94rem;
    }

    .event-upload__body {
      padding: 0.85rem;
    }

    .event-upload__action,
    .event-add-btn,
    .event-secondary-btn,
    .event-primary-btn {
      width: 100%;
    }
  }

  :root[data-theme='light'] .event-create-page {
    background: #ffffff !important;
    --event-card-bg: #ffffff;
    --event-card-border: #d8dde7;
    --event-card-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    --event-heading: #111827;
    --event-muted: #7b8192;
    --event-label: #111827;
    --event-divider: #e5e7eb;
    --event-input-bg: #ffffff;
    --event-input-border: #d7dce5;
    --event-placeholder: #9ca3af;
    --event-upload-bg: #ffffff;
  }

  :root[data-theme='dark'] .event-create-page {
    background: #060b14 !important;
    --event-card-bg: #101827;
    --event-card-border: #243043;
    --event-card-shadow: none;
    --event-heading: #f8fafc;
    --event-muted: #94a3b8;
    --event-label: #e2e8f0;
    --event-divider: #243043;
    --event-input-bg: #0f172a;
    --event-input-border: #334155;
    --event-placeholder: #64748b;
    --event-upload-bg: #0f172a;
  }

  :root[data-theme='dark'] .event-upload__action,
  :root[data-theme='dark'] .event-status-chip {
    background: #0f172a;
  }
</style>
@endsection

@section('content')
@php
    $lineupNames = old('lineup_names', ['']);
    $lineupRoles = old('lineup_roles', ['']);
    $lineupTimes = old('lineup_times', ['']);
    $lineupCount = max(count($lineupNames), count($lineupRoles), count($lineupTimes), 1);
@endphp

<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
  <a href="{{ route('organiser.events.index') }}" class="group inline-flex items-center gap-2 text-sm font-semibold text-gray-400 hover:text-white transition-colors">
      <span class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-gray-700 bg-transparent text-gray-400 transition-colors group-hover:border-gray-500">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 6 9 12l6 6"></path>
        </svg>
      </span>
      <span>Back to events</span>
    </a>
  
</div>

<div class="event-create-shell">
<form action="{{ route('organiser.events.store') }}" method="POST" enctype="multipart/form-data" class="event-create-form">
@csrf

@if($errors->any())
<div class="event-error-card">
  @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
</div>
@endif

<section class="event-card">
  <div class="event-card__head">
    <div class="event-card__title-wrap">
      <span class="event-card__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M12 8.25h.01M12 12h.01M12 15.75h.01M10 4.75h4a5.25 5.25 0 0 1 5.25 5.25v4A5.25 5.25 0 0 1 14 19.25h-4A5.25 5.25 0 0 1 4.75 14v-4A5.25 5.25 0 0 1 10 4.75Z"/></svg>
      </span>
      <div>
        <h2 class="event-card__title">Basic Information</h2>
        <p class="event-card__subtitle">Enter the main details about your event</p>
      </div>
    </div>
  </div>

  <div class="event-upload">
    <div class="event-upload__band"></div>
    <div class="event-upload__body">
      <div>
        <div class="event-upload__title">Do you have an event poster?</div>
        <div class="event-upload__copy">Upload your poster or banner image here. It will be used as your event banner and visual listing image.</div>
      </div>
      <div>
        <label class="event-upload__action">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 16V8m0 0-3 3m3-3 3 3M6.75 16.75v.5A1.75 1.75 0 0 0 8.5 19h7a1.75 1.75 0 0 0 1.75-1.75v-.5"/></svg>
          <span>Upload poster</span>
          <input type="file" name="banner" accept="image/*">
        </label>
        <div class="event-upload__meta">PNG, JPG, WebP · Max 4MB</div>
      </div>
    </div>
  </div>

  <div class="event-hint">No poster? No problem, just fill in the fields below.</div>

  <div class="event-grid">
    <div class="event-field">
      <label class="event-label">Event Title *</label>
      <input type="text" name="title" value="{{ old('title') }}" required maxlength="255" class="event-input" placeholder="e.g. Summer Music Festival 2025">
    </div>

    <div class="event-field">
      <label class="event-label">Short Description</label>
      <input type="text" name="short_description" value="{{ old('short_description') }}" maxlength="500" class="event-input" placeholder="One-line summary shown in listings">
    </div>

    <div class="event-field">
      <label class="event-label">Category *</label>
      <select name="category" class="event-select">
        @foreach(['Music','Sports','Arts & Culture','Food & Drink','Technology','Comedy','Theatre','Festival','Workshop','Conference','Other'] as $cat)
        <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
        @endforeach
      </select>
    </div>

  <div class="event-status-wrap">
    <input type="hidden" name="status" value="draft">

<div class="event-field">
  <label class="event-label">Status</label>
  <select class="event-select" disabled>
    <option value="draft" selected>Draft</option>
  </select>
</div>
    <div class="event-field">
      <label class="event-label">Feature on Homepage</label>
      <label class="inline-flex items-center gap-2 text-sm text-gray-400">
        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-500 text-violet-600 focus:ring-violet-500">
        <span>Mark as featured event</span>
      </label>
    </div>
    <!-- <span class="event-status-chip">Current setup for new event creation</span> -->
  </div>
  
    <div class="event-field">
      <label class="event-label">Full Description</label>
      <textarea id="description" name="description" rows="6" class="event-textarea">{{ old('description') }}</textarea>
    </div>
  </div>
</section>

<section class="event-card">
  <div class="event-card__head">
    <div class="event-card__title-wrap">
      <span class="event-card__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M7.75 4.75v3.5m8.5-3.5v3.5M5 8.25h14M6.5 19.25h11a1.75 1.75 0 0 0 1.75-1.75v-9a1.75 1.75 0 0 0-1.75-1.75h-11A1.75 1.75 0 0 0 4.75 8.5v9A1.75 1.75 0 0 0 6.5 19.25Z"/></svg>
      </span>
      <div>
        <h2 class="event-card__title">Date &amp; Time</h2>
        <p class="event-card__subtitle">Choose when your event starts and ends</p>
      </div>
    </div>
  </div>

  <div class="event-grid event-grid--2">
    <div class="event-field">
      <label class="event-label">Start Date &amp; Time *</label>
      <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" required class="event-input">
    </div>
    <div class="event-field">
      <label class="event-label">End Date &amp; Time *</label>
      <input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}" required class="event-input">
    </div>
  </div>
</section>

<section class="event-card">
  <div class="event-card__head">
    <div class="event-card__title-wrap">
      <span class="event-card__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M12 20s6.25-5.07 6.25-10A6.25 6.25 0 1 0 5.75 10c0 4.93 6.25 10 6.25 10Zm0-7.25A2.75 2.75 0 1 0 12 7.25a2.75 2.75 0 0 0 0 5.5Z"/></svg>
      </span>
      <div>
        <h2 class="event-card__title">Location</h2>
        <p class="event-card__subtitle">Where will your event take place?</p>
      </div>
    </div>
  </div>

  <div class="event-grid event-grid--2">
    <div class="event-field">
      <label class="event-label">Venue Name *</label>
      <input type="text" name="venue_name" value="{{ old('venue_name') }}" required class="event-input" placeholder="O2 Arena">
    </div>
    <div class="event-field">
      <label class="event-label">City *</label>
      <input type="text" name="city" value="{{ old('city') }}" required class="event-input" placeholder="London">
    </div>
    <div class="event-field event-field--span-2">
      <label class="event-label">Address *</label>
      <input type="text" name="venue_address" value="{{ old('venue_address') }}" required class="event-input" placeholder="Peninsula Square">
    </div>
    <div class="event-field">
      <label class="event-label">Postcode</label>
      <input type="text" name="postcode" value="{{ old('postcode') }}" class="event-input" placeholder="SE10 0DX">
    </div>
  </div>
</section>

<section class="event-card">
  <div class="event-card__head">
    <div class="event-card__title-wrap">
      <span class="event-card__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M5.75 8.5h12.5M5.75 12h12.5M5.75 15.5h7.5M6.5 19.25h11a1.75 1.75 0 0 0 1.75-1.75v-11A1.75 1.75 0 0 0 17.5 4.75h-11A1.75 1.75 0 0 0 4.75 6.5v11A1.75 1.75 0 0 0 6.5 19.25Z"/></svg>
      </span>
      <div>
        <h2 class="event-card__title">Performer Lineup</h2>
        <p class="event-card__subtitle">Optional lineup information for speakers, artists, or DJs</p>
      </div>
    </div>
    <button type="button" onclick="addLineupRow()" class="event-add-btn">Add Performer</button>
  </div>

  <div id="lineup-rows" class="event-lineup-list">
    @for($i = 0; $i < $lineupCount; $i++)
    <div class="event-lineup-row lineup-row">
      <div class="event-field">
        <label class="event-label">Performer Name</label>
        <input type="text" name="lineup_names[]" value="{{ $lineupNames[$i] ?? '' }}" placeholder="Performer name" class="event-input">
      </div>
      <div class="event-field">
        <label class="event-label">Role / Band</label>
        <input type="text" name="lineup_roles[]" value="{{ $lineupRoles[$i] ?? '' }}" placeholder="Role / DJ / Band" class="event-input">
      </div>
      <div class="event-field">
        <label class="event-label">Time</label>
        <input type="text" name="lineup_times[]" value="{{ $lineupTimes[$i] ?? '' }}" placeholder="e.g. 20:00" class="event-input">
      </div>
      <button type="button" onclick="this.closest('.lineup-row').remove()" class="event-icon-btn" aria-label="Remove performer">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M6 6l12 12M18 6L6 18"/></svg>
      </button>
    </div>
    @endfor
  </div>
</section>

<section class="event-card">
  <div class="event-card__head">
    <div class="event-card__title-wrap">
      <span class="event-card__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M4.75 7.75h14.5M4.75 12h14.5M4.75 16.25h14.5"/></svg>
      </span>
      <div>
        <h2 class="event-card__title">Additional Details</h2>
        <p class="event-card__subtitle">Optional information to enhance your event listing</p>
      </div>
    </div>
  </div>

  <div class="event-grid">
    <div class="event-field">
      <label class="event-label">Parking / Transport Info <span class="event-inline-note">(optional)</span></label>
      <textarea name="parking_info" rows="3" maxlength="2000" class="event-textarea" placeholder="Nearest tube: North Greenwich. Parking available at...">{{ old('parking_info') }}</textarea>
    </div>

    <div class="event-field">
      <label class="event-label">Refund Policy <span class="event-inline-note">(optional)</span></label>
      <textarea name="refund_policy" rows="3" maxlength="2000" class="event-textarea" placeholder="e.g. Tickets are non-refundable but are transferable up to 48 hours before the event.">{{ old('refund_policy') }}</textarea>
    </div>
  </div>
</section>

<div class="event-actions">
  <a href="{{ route('organiser.events.index') }}" class="event-secondary-btn">Cancel</a>
  <button type="submit" class="event-primary-btn">
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5.75 12.75 10 17l8.25-9.25"/></svg>
    <span>Create Event</span>
  </button>
</div>

</form>
</div>
@endsection

@section('scripts')
<script>
ClassicEditor.create(document.querySelector('#description'), {
    toolbar: ['heading','|','bold','italic','link','bulletedList','numberedList','|','blockQuote','undo','redo'],
}).catch(console.error);

function addLineupRow() {
    const row = document.createElement('div');
    row.className = 'event-lineup-row lineup-row';
    row.innerHTML = `
        <div class="event-field">
            <label class="event-label">Performer Name</label>
            <input type="text" name="lineup_names[]" placeholder="Performer name" class="event-input">
        </div>
        <div class="event-field">
            <label class="event-label">Role / Band</label>
            <input type="text" name="lineup_roles[]" placeholder="Role / DJ / Band" class="event-input">
        </div>
        <div class="event-field">
            <label class="event-label">Time</label>
            <input type="text" name="lineup_times[]" placeholder="e.g. 20:00" class="event-input">
        </div>
        <button type="button" onclick="this.closest('.lineup-row').remove()" class="event-icon-btn" aria-label="Remove performer">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    `;
    document.getElementById('lineup-rows').appendChild(row);
}
</script>
@endsection
