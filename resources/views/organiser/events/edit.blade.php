@extends('layouts.organiser')
@section('title', 'Edit Event')
@section('page-title', 'Edit Event')
@section('page-subtitle', $event->title)
@section('body-class', 'event-create-page')

@section('head')
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

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

  .event-overview-card {
    display: grid;
    gap: 1rem;
  }

  .event-overview-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
  }

  .event-overview-meta {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    flex-wrap: wrap;
  }

  .event-overview-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.65rem;
    flex-wrap: wrap;
  }

  .event-live-badge,
  .event-cancelled-note {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 2rem;
    padding: 0.42rem 0.85rem;
    border-radius: 999px;
    font-size: 0.74rem;
    font-weight: 800;
    line-height: 1;
    text-transform: capitalize;
  }

  .event-live-badge {
    background: rgba(124, 58, 237, 0.12);
    border: 1px solid rgba(124, 58, 237, 0.24);
    color: #7c3aed;
  }

  .event-cancelled-note {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.18);
    color: #dc2626;
  }

  .event-status-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 2.5rem;
    padding: 0.68rem 1rem;
    border-radius: 0.65rem;
    border: 1px solid var(--event-input-border);
    background: var(--event-input-bg);
    color: var(--event-heading);
    font-size: 0.78rem;
    font-weight: 800;
    line-height: 1;
    text-decoration: none;
    white-space: nowrap;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .event-status-btn:hover {
    transform: translateY(-1px);
    border-color: rgba(124, 58, 237, 0.32);
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
  }

  .event-status-btn--success {
    border-color: rgba(16, 185, 129, 0.24);
    background: rgba(16, 185, 129, 0.12);
    color: #059669;
  }

  .event-status-btn--warning {
    border-color: rgba(245, 158, 11, 0.24);
    background: rgba(245, 158, 11, 0.12);
    color: #d97706;
  }

  .event-status-btn--danger {
    border-color: rgba(239, 68, 68, 0.22);
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
  }

  .event-preview-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
  }

  .event-preview-thumb {
    width: 8.5rem;
    height: 5.8rem;
    overflow: hidden;
    border-radius: 0.85rem;
    background: #e5e7eb;
    flex-shrink: 0;
  }

  .event-preview-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }

  .event-preview-caption {
    max-width: 30rem;
    font-size: 0.78rem;
    line-height: 1.6;
    color: var(--event-muted);
  }

  .event-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 60;
    background: rgba(15, 23, 42, 0.58);
    padding: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .event-modal {
    width: min(100%, 34rem);
    border-radius: 1rem;
    border: 1px solid var(--event-card-border);
    background: var(--event-card-bg);
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.24);
    padding: 1.15rem;
  }

  .event-modal h3 {
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--event-heading);
    margin-bottom: 0.45rem;
  }

  .event-modal p {
    font-size: 0.8rem;
    line-height: 1.6;
    color: var(--event-muted);
    margin-bottom: 1rem;
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

  .event-datetime-wrap {
    position: relative;
  }

  .event-input--datetime {
    padding-right: 2.65rem;
  }

  .event-input--datetime::-webkit-calendar-picker-indicator {
    opacity: 0;
    position: absolute;
    right: 0;
    top: 0;
    width: 2.75rem;
    height: 100%;
    margin: 0;
    cursor: pointer;
  }

  .event-datetime-trigger {
    position: absolute;
    top: 50%;
    right: 0.8rem;
    transform: translateY(-50%);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.1rem;
    height: 1.1rem;
    padding: 0;
    border: 0;
    background: transparent;
    color: var(--event-datetime-trigger);
    cursor: pointer;
  }

  .event-datetime-trigger svg {
    width: 100%;
    height: 100%;
  }

  .flatpickr-calendar {
    border-radius: 0.9rem;
    border: 1px solid #d7dce5;
    box-shadow: 0 22px 55px rgba(15, 23, 42, 0.18);
    overflow: hidden;
  }

  .flatpickr-months,
  .flatpickr-weekdays {
    background: #f8fafc;
  }

  .flatpickr-current-month {
    padding-top: 0.2rem;
  }

  .flatpickr-current-month .flatpickr-monthDropdown-months,
  .flatpickr-current-month input.cur-year {
    font-weight: 700;
  }

  .flatpickr-day.selected,
  .flatpickr-day.startRange,
  .flatpickr-day.endRange,
  .flatpickr-day.selected:hover,
  .flatpickr-day.startRange:hover,
  .flatpickr-day.endRange:hover {
    background: #7c3aed;
    border-color: #7c3aed;
  }

  .flatpickr-time {
    border-top: 1px solid #e5e7eb;
  }

  .flatpickr-time input:hover,
  .flatpickr-time .flatpickr-am-pm:hover,
  .flatpickr-time input:focus,
  .flatpickr-time .flatpickr-am-pm:focus,
  .flatpickr-current-month .flatpickr-monthDropdown-months:hover,
  .flatpickr-current-month input.cur-year:hover {
    background: rgba(124, 58, 237, 0.08);
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
    .event-overview-top,
    .event-status-wrap,
    .event-actions,
    .event-card__head {
      flex-direction: column;
      align-items: stretch;
    }

    .event-overview-actions {
      justify-content: stretch;
    }

    .event-hint {
      text-align: left;
      margin-top: 0;
    }

    .event-actions > *,
    .event-overview-actions > * {
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
    --event-datetime-trigger: #111827;
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
    --event-datetime-trigger: #f8fafc;
    --event-upload-bg: #0f172a;
  }

  :root[data-theme='dark'] .event-upload__action,
  :root[data-theme='dark'] .event-status-chip {
    background: #0f172a;
  }

  :root[data-theme='dark'] .event-status-btn {
    background: #0f172a;
  }


</style>
@endsection

@section('content')
@php
    $lineup = old('lineup_names')
        ? collect(old('lineup_names', []))->map(function ($name, $i) {
            return [
                'name' => $name,
                'role' => old('lineup_roles.' . $i, ''),
                'time' => old('lineup_times.' . $i, ''),
            ];
        })->values()->all()
        : ($event->performer_lineup ?? [['name' => '', 'role' => '', 'time' => '']]);
    $canPreview = $event->status === 'published' && $event->approval_status === 'approved';
    $previewUrl = $canPreview
        ? route('events.show', $event->slug)
        : route('organiser.events.show', $event->id);
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

<div class="event-card event-overview-card">
  <div class="event-overview-top">
    <div class="event-overview-meta">
      <span class="event-live-badge">{{ strtolower($event->status_badge['label']) }}</span>
      @if($event->isCancelled())
      <span class="event-cancelled-note">Cancelled {{ $event->cancelled_at?->format('d M Y') }}</span>
      @endif
    </div>

    <div class="event-overview-actions">
      @if(!$event->isCancelled())
        @if($event->status !== 'published')
        <form action="{{ route('organiser.events.status', $event->id) }}" method="POST">
          @csrf
          <input type="hidden" name="status" value="published">
          <button type="submit" class="event-status-btn event-status-btn--success">Publish</button>
        </form>
        @endif
        @if($event->status === 'published')
        <form action="{{ route('organiser.events.status', $event->id) }}" method="POST">
          @csrf
          <input type="hidden" name="status" value="draft">
          <button type="submit" class="event-status-btn event-status-btn--warning">Move to Draft</button>
        </form>
        @endif
        <button type="button" onclick="document.getElementById('cancel-modal').classList.remove('hidden')" class="event-status-btn event-status-btn--danger">Cancel Event</button>
      @endif
      <a href="{{ route('organiser.tiers.index', $event->id) }}" class="event-status-btn">Manage Tiers</a>
      <a href="{{ $previewUrl }}" target="_blank" class="event-status-btn">Preview</a>
    </div>
  </div>

  @if($event->banner_url)
  <div class="event-preview-row">
    <div class="event-preview-thumb">
      <img src="{{ $event->banner_url }}" alt="{{ $event->title }}">
    </div>
    <div class="event-preview-caption">Current banner image for this event. Upload a new one below if you want to replace it.</div>
  </div>
  @endif
</div>

<form action="{{ route('organiser.events.update', $event->id) }}" method="POST" enctype="multipart/form-data" class="event-create-form">
@csrf
@method('PUT')

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
        <div class="event-upload__title">Update your event poster</div>
        <div class="event-upload__copy">Upload a new poster or banner image here. It will replace the current event banner used across your listings.</div>
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

  <div class="event-hint">Keep your current poster by leaving the upload field untouched.</div>

  <div class="event-status-wrap">
    <div class="event-field">
      <label class="event-label">Status</label>
      <select name="status" class="event-select" {{ $event->isCancelled() ? 'disabled' : '' }}>
        <option value="draft" {{ old('status', $event->status) === 'draft' ? 'selected' : '' }}>Draft</option>
        <option value="published" {{ old('status', $event->status) === 'published' ? 'selected' : '' }}>Published</option>
      </select>
    </div>
    <div class="event-field">
      <label class="event-label">Feature on Homepage</label>
      <label class="inline-flex items-center gap-2 text-sm text-gray-400">
        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $event->is_featured) ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-500 text-violet-600 focus:ring-violet-500">
        <span>Mark as featured event</span>
      </label>
    </div>
    <span class="event-status-chip">{{ $event->status_badge['label'] }}</span>
  </div>

  <div class="event-grid">
    <div class="event-field">
      <label class="event-label">Event Title *</label>
      <input type="text" name="title" value="{{ old('title', $event->title) }}" required class="event-input">
    </div>

    <div class="event-field">
      <label class="event-label">Short Description</label>
      <input type="text" name="short_description" value="{{ old('short_description', $event->short_description) }}" maxlength="500" class="event-input">
    </div>

    <div class="event-field">
      <label class="event-label">Category *</label>
      <select name="category" class="event-select">
        @foreach(\App\Models\Event::CATEGORIES as $cat)
        <option value="{{ $cat }}" {{ old('category', $event->category) === $cat ? 'selected' : '' }}>{{ $cat }}</option>
        @endforeach
      </select>
    </div>

    <div class="event-field">
      <label class="event-label">Full Description</label>
      <textarea id="description" name="description" rows="6" class="event-textarea">{{ old('description', $event->description) }}</textarea>
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
      <div class="event-datetime-wrap">
        <input type="text" name="starts_at" value="{{ old('starts_at', $event->starts_at->format('Y-m-d\TH:i')) }}" required class="event-input event-input--datetime js-datetime-input" placeholder="Select start date and time" autocomplete="off">
        <button type="button" class="event-datetime-trigger" onclick="openDateTimePicker(this)" aria-label="Open start date and time picker">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7.75 4.75v3.5m8.5-3.5v3.5M5 8.25h14M6.5 19.25h11a1.75 1.75 0 0 0 1.75-1.75v-9A1.75 1.75 0 0 0 17.5 6.75h-11A1.75 1.75 0 0 0 4.75 8.5v9A1.75 1.75 0 0 0 6.5 19.25Z"/></svg>
        </button>
      </div>
    </div>
    <div class="event-field">
      <label class="event-label">End Date &amp; Time *</label>
      <div class="event-datetime-wrap">
        <input type="text" name="ends_at" value="{{ old('ends_at', $event->ends_at->format('Y-m-d\TH:i')) }}" required class="event-input event-input--datetime js-datetime-input" placeholder="Select end date and time" autocomplete="off">
        <button type="button" class="event-datetime-trigger" onclick="openDateTimePicker(this)" aria-label="Open end date and time picker">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7.75 4.75v3.5m8.5-3.5v3.5M5 8.25h14M6.5 19.25h11a1.75 1.75 0 0 0 1.75-1.75v-9A1.75 1.75 0 0 0 17.5 6.75h-11A1.75 1.75 0 0 0 4.75 8.5v9A1.75 1.75 0 0 0 6.5 19.25Z"/></svg>
        </button>
      </div>
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
      <input type="text" name="venue_name" value="{{ old('venue_name', $event->venue_name) }}" required class="event-input">
    </div>
    <div class="event-field">
      <label class="event-label">City *</label>
      <input type="text" name="city" value="{{ old('city', $event->city) }}" required class="event-input">
    </div>
    <div class="event-field event-field--span-2">
      <label class="event-label">Address *</label>
      <input type="text" name="venue_address" value="{{ old('venue_address', $event->venue_address) }}" required class="event-input">
    </div>
    <div class="event-field">
      <label class="event-label">Postcode</label>
      <input type="text" name="postcode" value="{{ old('postcode', $event->postcode) }}" class="event-input">
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
    @foreach($lineup as $performer)
    <div class="event-lineup-row lineup-row">
      <div class="event-field">
        <label class="event-label">Performer Name</label>
        <input type="text" name="lineup_names[]" value="{{ $performer['name'] ?? '' }}" placeholder="Performer name" class="event-input">
      </div>
      <div class="event-field">
        <label class="event-label">Role / Band</label>
        <input type="text" name="lineup_roles[]" value="{{ $performer['role'] ?? '' }}" placeholder="Role / DJ / Band" class="event-input">
      </div>
      <div class="event-field">
        <label class="event-label">Time</label>
        <input type="text" name="lineup_times[]" value="{{ $performer['time'] ?? '' }}" placeholder="e.g. 20:00" class="event-input">
      </div>
      <button type="button" onclick="this.closest('.lineup-row').remove()" class="event-icon-btn" aria-label="Remove performer">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M6 6l12 12M18 6L6 18"/></svg>
      </button>
    </div>
    @endforeach
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
      <textarea name="parking_info" rows="3" class="event-textarea">{{ old('parking_info', $event->parking_info) }}</textarea>
    </div>

    <div class="event-field">
      <label class="event-label">Refund Policy <span class="event-inline-note">(optional)</span></label>
      <textarea name="refund_policy" rows="3" class="event-textarea">{{ old('refund_policy', $event->refund_policy) }}</textarea>
    </div>
  </div>
</section>

<div class="event-actions">
  <a href="{{ route('organiser.events.index') }}" class="event-secondary-btn">Cancel</a>
  <button type="submit" id="submitBtn" class="event-primary-btn">
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5.75 12.75 10 17l8.25-9.25"/></svg>
    <span>Save Changes</span>
  </button>
</div>

</form>

<div id="cancel-modal" class="hidden event-modal-backdrop">
  <div class="event-modal">
    <h3>Cancel This Event?</h3>
    <p>All ticket holders will be notified by email and SMS. All paid bookings will be marked for refund.</p>
    <form action="{{ route('organiser.events.status', $event->id) }}" method="POST" class="event-create-form">
      @csrf
      <input type="hidden" name="status" value="cancelled">
      <div class="event-field">
        <label class="event-label">Reason for Cancellation *</label>
        <textarea name="cancellation_reason" rows="4" required minlength="10" maxlength="1000" class="event-textarea" placeholder="Please explain why this event is being cancelled..."></textarea>
      </div>
      <div class="event-actions">
        <button type="button" onclick="document.getElementById('cancel-modal').classList.add('hidden')" class="event-secondary-btn">Go Back</button>
        <button type="submit" class="event-primary-btn" style="background:linear-gradient(135deg,#ef4444,#dc2626);box-shadow:none;">Confirm Cancellation</button>
      </div>
    </form>
  </div>
</div>

</div>
@endsection

@section('scripts')
<script>
ClassicEditor.create(document.querySelector('#description'), {
    toolbar: ['heading','|','bold','italic','link','bulletedList','numberedList','|','blockQuote','undo','redo'],
}).catch(console.error);

function openDateTimePicker(trigger) {
    const input = trigger.closest('.event-datetime-wrap')?.querySelector('.js-datetime-input');
    if (!input) return;
    if (input._flatpickr) {
        input._flatpickr.open();
        return;
    }
    input.focus({ preventScroll: true });
    input.click();
}

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

const form = document.querySelector('.event-create-form');
const submitBtn = document.getElementById('submitBtn');

form.addEventListener('submit', function () {
    submitBtn.disabled = true;
    submitBtn.style.opacity = '0.6';
    submitBtn.innerHTML = 'Processing...';
});

window.addEventListener('load', function () {
    if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
        submitBtn.innerHTML = 'Update Event';
    }
});

document.querySelectorAll('.js-datetime-input').forEach((input) => {
    flatpickr(input, {
        enableTime: true,
        time_24hr: false,
        dateFormat: 'Y-m-d\\TH:i',
        altInput: true,
        altFormat: 'd-m-Y h:i K',
        altInputClass: 'event-input event-input--datetime',
        minuteIncrement: 5,
        allowInput: true,
        disableMobile: true,
        monthSelectorType: 'dropdown',
        nextArrow: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 6 6 6-6 6"/></svg>',
        prevArrow: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 18-6-6 6-6"/></svg>',
        onReady: function (_, __, instance) {
            if (instance.altInput) {
                instance.altInput.placeholder = '30-12-2026 12:45 AM';
                instance.altInput.autocomplete = 'off';
            }
        },
    });
});
</script>
@endsection
