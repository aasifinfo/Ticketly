@extends('layouts.organiser')
@section('title', 'Scan Ticket')
@section('page-title', 'Scan Ticket')
@section('page-subtitle', 'Validate tickets using camera or booking reference')

@section('page-icon')
<div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600/70 text-indigo-200">
  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7V5a1 1 0 011-1h2M4 17v2a1 1 0 001 1h2M18 3h2a1 1 0 011 1v2M18 21h2a1 1 0 001-1v-2M9 12h6m-6 0a3 3 0 016 0m-6 0a3 3 0 006 0"/>
  </svg>
</div>
@endsection

@section('head')
<style>
  .scan-modal-overlay { background: rgba(2, 6, 23, 0.6); backdrop-filter: blur(10px); }
  .scan-modal-card { background: #0f172a; color: #e2e8f0; border-color: #1e293b; }
  .scan-modal-subtle { color: #94a3b8; }
  .scan-modal-btn { background: #1e293b; color: #e2e8f0; border-color: #334155; }
  .scan-modal-btn:hover { background: #263244; }
  .scan-modal-surface { background: #0b1220; border-color: #1f2937; }

  .result-pill { background: #0f172a; color: #cbd5f5; border-color: #334155; }
  .result-pill.status-success { background: #064e3b; color: #d1fae5; border-color: #10b981; }
  .result-pill.status-warn { background: #78350f; color: #fef3c7; border-color: #f59e0b; }
  .result-pill.status-error { background: #7f1d1d; color: #fee2e2; border-color: #f87171; }

  .result-status { color: #cbd5e1; }
  .result-status.status-success { color: #34d399; }
  .result-status.status-warn { color: #fbbf24; }
  .result-status.status-error { color: #f87171; }

  .scan-result-row { background: #0b1220; border-color: #1f2937; color: #e2e8f0; }
  .scan-result-label { color: #94a3b8; }

  :root[data-theme='light'] .scan-modal-overlay { background: rgba(15, 23, 42, 0.45); }
  :root[data-theme='light'] .scan-modal-card { background: #ffffff; color: #0f172a; border-color: #e2e8f0; }
  :root[data-theme='light'] .scan-modal-subtle { color: #64748b; }
  :root[data-theme='light'] .scan-modal-btn { background: #ffffff; color: #334155; border-color: #e2e8f0; }
  :root[data-theme='light'] .scan-modal-btn:hover { background: #f1f5f9; }
  :root[data-theme='light'] .scan-modal-surface { background: #f8fafc; border-color: #e2e8f0; }

  :root[data-theme='light'] .result-pill { background: #ffffff; color: #475569; border-color: #e2e8f0; }
  :root[data-theme='light'] .result-pill.status-success { background: #ecfdf5; color: #065f46; border-color: #a7f3d0; }
  :root[data-theme='light'] .result-pill.status-warn { background: #fffbeb; color: #92400e; border-color: #fde68a; }
  :root[data-theme='light'] .result-pill.status-error { background: #fef2f2; color: #991b1b; border-color: #fecaca; }

  :root[data-theme='light'] .result-status { color: #475569; }
  :root[data-theme='light'] .result-status.status-success { color: #047857; }
  :root[data-theme='light'] .result-status.status-warn { color: #b45309; }
  :root[data-theme='light'] .result-status.status-error { color: #b91c1c; }

  :root[data-theme='light'] .scan-result-row { background: #f8fafc; border-color: #e2e8f0; color: #334155; }
  :root[data-theme='light'] .scan-result-label { color: #64748b; }
</style>
@endsection

@section('content')
<div class="min-h-[calc(100vh-170px)] w-full flex items-center">
  <div class="mx-auto flex max-w-[450px] flex-col items-center justify-center gap-6 text-center">
    <button id="start-scan" type="button" class="group flex h-40 w-40 items-center justify-center rounded-[48px] bg-[#6D28D9] shadow-[0_18px_60px_rgba(109,40,217,0.35)] transition hover:scale-[1.02]">
      <div class="flex h-20 w-20 items-center justify-center rounded-[24px] bg-[#7C3AED]">
        <div class="flex flex-col items-center justify-center text-white">
          <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h3l2-2h4l2 2h3a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
            <circle cx="12" cy="13" r="3" stroke-width="2"/>
          </svg>
          <span class="mt-1 text-[11px] font-semibold">Scan QR</span>
        </div>
      </div>
    </button>

    <div>
      <h2 class="text-2xl font-extrabold text-white">Ready to Scan</h2>
      <p class="mt-2 text-sm text-gray-400">Tap the button to open camera, or enter booking ref below</p>
    </div>

    <form id="manual-form" class="w-full space-y-3">
      <div class="relative">
        <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
          <circle cx="11" cy="11" r="7" stroke-width="2" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 20l-3.5-3.5" />
        </svg>
        <input id="booking-reference" type="text" class="w-full rounded-2xl border border-gray-800 bg-gray-900 px-11 py-3 text-sm text-white placeholder:text-gray-500 focus:border-indigo-400 focus:outline-none" placeholder="TKT-XXXXXXX">
      </div>
      <button id="manual-submit" type="submit" class="w-full py-3.5 font-extrabold text-white rounded-xl text-sm disabled:cursor-not-allowed disabled:opacity-60" style="background:linear-gradient(135deg,#6366f1,#8b5cf6)" disabled>
        Validate Manually
      </button>
    </form>

    <div id="scanner-hint" class="text-xs text-gray-500"></div>
  </div>
</div>

<div id="scanner-modal" class="scan-modal-overlay fixed inset-0 z-50 hidden items-center justify-center px-4">
  <div class="scan-modal-card w-full max-w-lg rounded-3xl border p-5 shadow-[0_25px_80px_rgba(15,23,42,0.35)]">
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-lg font-bold">Scan QR</h3>
        <p class="scan-modal-subtle text-xs">Align the QR code inside the frame</p>
      </div>
      <button id="close-scan" type="button" class="scan-modal-btn rounded-xl border px-3 py-2 text-xs font-semibold shadow-sm">Close</button>
    </div>

    <div class="scan-modal-surface mt-4 overflow-hidden rounded-2xl border">
      <div id="qr-reader" class="min-h-[320px] w-full text-center text-sm text-slate-200">
        <div id="scanner-placeholder" class="py-20">Camera preview will appear here</div>
      </div>
    </div>

    <div class="mt-4 flex flex-wrap gap-3">
      <button id="switch-camera" type="button" class="scan-modal-btn flex-1 rounded-2xl border px-4 py-3 text-sm font-semibold shadow-sm disabled:opacity-60" disabled>Switch Camera</button>
      <button id="stop-scan" type="button" class="flex-1 rounded-2xl bg-rose-500/90 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-rose-500 disabled:opacity-60" disabled>Stop Scan</button>
    </div>
    <p id="camera-helper" class="scan-modal-subtle mt-3 text-xs"></p>
  </div>
</div>

<div id="result-modal" class="scan-modal-overlay fixed inset-0 z-50 hidden items-center justify-center px-4">
  <div class="scan-modal-card w-full max-w-md rounded-3xl border p-6 shadow-[0_25px_80px_rgba(15,23,42,0.35)]">
    <div class="flex items-center justify-between">
      <h3 class="text-lg font-bold">Scan Result</h3>
      <button id="close-result" type="button" class="scan-modal-btn rounded-xl border px-3 py-2 text-xs font-semibold shadow-sm">Close</button>
    </div>
    <div class="mt-4 flex items-center gap-3">
      <span id="result-pill" class="result-pill inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold shadow-sm">Idle</span>
      <p id="result-status" class="result-status text-sm">No scans yet.</p>
    </div>
    <div id="result-details" class="mt-4 space-y-2 text-sm"></div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  function loadScannerLibrary() {
    return new Promise((resolve, reject) => {
      if (window.Html5Qrcode) {
        resolve();
        return;
      }
      const script = document.createElement('script');
      script.src = 'https://cdn.jsdelivr.net/npm/html5-qrcode/minified/html5-qrcode.min.js';
      script.onload = () => resolve();
      script.onerror = () => reject();
      document.head.appendChild(script);
    });
  }
</script>
<script>
  const apiUrl = "{{ route('organiser.scan.validate', [], false) }}";
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const startButton = document.getElementById('start-scan');
  const stopButton = document.getElementById('stop-scan');
  const switchButton = document.getElementById('switch-camera');
  const closeScanButton = document.getElementById('close-scan');
  const closeResultButton = document.getElementById('close-result');
  const manualForm = document.getElementById('manual-form');
  const bookingReferenceInput = document.getElementById('booking-reference');
  const manualSubmit = document.getElementById('manual-submit');
  const scannerPlaceholder = document.getElementById('scanner-placeholder');
  const scannerModal = document.getElementById('scanner-modal');
  const resultModal = document.getElementById('result-modal');
  const resultStatus = document.getElementById('result-status');
  const resultDetails = document.getElementById('result-details');
  const resultPill = document.getElementById('result-pill');
  const cameraHelper = document.getElementById('camera-helper');
  const scannerHint = document.getElementById('scanner-hint');

  let html5QrCode = null;
  let cameras = [];
  let activeCameraIndex = 0;
  let isScanning = false;
  let scannerReady = false;
  let loadingLibrary = false;

  function openModal(el) {
    el.classList.remove('hidden');
    el.classList.add('flex');
  }

  function closeModal(el) {
    el.classList.add('hidden');
    el.classList.remove('flex');
  }

  function setCameraHelper(message) {
    cameraHelper.textContent = message || '';
  }

  function setResult(status, message, details, tone) {
    resultStatus.textContent = message || status || 'Awaiting scan...';
    resultStatus.className = 'result-status text-sm';
    resultDetails.innerHTML = '';
    resultPill.className = 'result-pill inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold shadow-sm';

    if (tone === 'success') {
      resultStatus.classList.add('status-success');
      resultPill.classList.add('status-success');
      resultPill.textContent = 'Validated';
    } else if (tone === 'warn') {
      resultStatus.classList.add('status-warn');
      resultPill.classList.add('status-warn');
      resultPill.textContent = 'Attention';
    } else if (tone === 'error') {
      resultStatus.classList.add('status-error');
      resultPill.classList.add('status-error');
      resultPill.textContent = 'Error';
    } else {
      resultPill.textContent = 'Idle';
    }

    if (details) {
      Object.entries(details).forEach(([label, value]) => {
        if (value === null || value === undefined || value === '') return;
        const row = document.createElement('div');
        row.className = 'scan-result-row flex items-center justify-between gap-4 rounded-xl border px-3 py-2 text-xs uppercase tracking-wide shadow-sm';
        row.innerHTML = `<span class="scan-result-label">${label}</span><span class="scan-result-value normal-case">${value}</span>`;
        resultDetails.appendChild(row);
      });
    }

    openModal(resultModal);
  }

  function extractPayload(rawText) {
    if (!rawText) return null;
    const text = String(rawText).trim();
    if (!text) return null;

    const referenceMatch = text.match(/BOOKING\s*REFERENCE\s*[:#]?\s*([A-Z0-9-]+)/i);
    if (referenceMatch) {
      return { booking_reference: referenceMatch[1].toUpperCase() };
    }

    const urlMatch = text.match(/\/bookings\/([A-Z0-9-]+)/i) || text.match(/\/booking\/([A-Z0-9-]+)/i);
    if (urlMatch) {
      return { booking_reference: urlMatch[1].toUpperCase() };
    }

    const tktMatch = text.match(/(TKT-[A-Z0-9]+)/i);
    if (tktMatch) {
      return { booking_reference: tktMatch[1].toUpperCase() };
    }

    if (/^[A-Z0-9-]{4,}$/i.test(text)) {
      return { booking_reference: text.toUpperCase() };
    }

    return null;
  }

  function formatLocalDateTime(value, timestamp) {
    if (typeof timestamp === 'number' && !Number.isNaN(timestamp)) {
      return new Date(timestamp).toLocaleString();
    }
    if (!value) return null;
    let parsed = new Date(value);
    if (!Number.isNaN(parsed.getTime())) return parsed.toLocaleString();
    const normalized = String(value).replace(' ', 'T');
    parsed = new Date(normalized);
    if (!Number.isNaN(parsed.getTime())) return parsed.toLocaleString();
    parsed = new Date(normalized + 'Z');
    if (!Number.isNaN(parsed.getTime())) return parsed.toLocaleString();
    return value;
  }

  async function validateTicket(payload) {
    try {
      const response = await fetch(apiUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken || ''
        },
        body: JSON.stringify(payload)
      });

      const data = await response.json().catch(() => ({}));
      const status = data.status || 'unknown';
      const tone = status === 'validated'
        ? 'success'
        : (status === 'already_scanned' || status === 'event_future')
          ? 'warn'
          : (status === 'event_expired' || status === 'not_found' || status === 'invalid')
            ? 'error'
            : 'neutral';

      const eventStart = formatLocalDateTime(data.event_start_time, data.event_start_time_ts);
      const scannedAt = formatLocalDateTime(data.scanned_at, data.scanned_at_ts);

      setResult(status, data.message || status, {
        'Booking Reference': data.booking_reference,
        'Event Start Date & Time': eventStart,
        'Scanned At': scannedAt
      }, tone);
    } catch (error) {
      setResult('error', 'Unable to reach validation API.', null, 'error');
    }
  }

  async function ensureScannerLibrary() {
    if (scannerReady) return true;
    if (loadingLibrary) return false;
    loadingLibrary = true;
    try {
      await loadScannerLibrary();
      scannerReady = true;
      loadingLibrary = false;
      return true;
    } catch (error) {
      loadingLibrary = false;
      scannerReady = false;
      if (scannerPlaceholder) {
        scannerPlaceholder.textContent = 'Scanner library failed to load. Please refresh or check your connection.';
      }
      setCameraHelper('Scanner library unavailable.');
      return false;
    }
  }

  async function startScan() {
    openModal(scannerModal);

    const isSecure = location.protocol === 'https:' || location.hostname === 'localhost';
    if (!isSecure) {
      setCameraHelper('Camera requires HTTPS or localhost.');
    }

    const ready = await ensureScannerLibrary();
    if (!ready || typeof Html5Qrcode === 'undefined') {
      setCameraHelper('Scanner unavailable.');
      return;
    }

    if (!html5QrCode) {
      html5QrCode = new Html5Qrcode('qr-reader');
    }

    try {
      setCameraHelper('Requesting camera access...');
      cameras = await Html5Qrcode.getCameras();
      if (!cameras.length) {
        setCameraHelper('No camera found.');
        return;
      }
      switchButton.disabled = cameras.length < 2;
      activeCameraIndex = Math.min(activeCameraIndex, cameras.length - 1);

      const cameraId = cameras[activeCameraIndex].id;
      await html5QrCode.start(
        { deviceId: { exact: cameraId } },
        { fps: 10, qrbox: { width: 260, height: 260 } },
        (decodedText) => {
          const payload = extractPayload(decodedText);
          if (!payload) {
            setResult('unrecognized', 'QR found but no booking reference detected.', null, 'warn');
            return;
          }
          stopScan();
          closeModal(scannerModal);
          validateTicket(payload);
        },
        () => {}
      );

      isScanning = true;
      stopButton.disabled = false;
      setCameraHelper('Scanning...');
    } catch (error) {
      setCameraHelper('Camera error. Check permissions.');
    }
  }

  async function stopScan() {
    if (!html5QrCode || !isScanning) {
      return;
    }
    try {
      await html5QrCode.stop();
      await html5QrCode.clear();
    } catch (error) {
      // ignore
    }
    isScanning = false;
    stopButton.disabled = true;
    setCameraHelper('Camera stopped.');
  }

  async function switchCamera() {
    if (!cameras.length) return;
    activeCameraIndex = (activeCameraIndex + 1) % cameras.length;
    if (isScanning) {
      await stopScan();
      await startScan();
    }
  }

  startButton.addEventListener('click', startScan);
  stopButton.addEventListener('click', stopScan);
  switchButton.addEventListener('click', switchCamera);
  closeScanButton.addEventListener('click', async () => {
    await stopScan();
    closeModal(scannerModal);
  });
  closeResultButton.addEventListener('click', () => closeModal(resultModal));

  scannerModal.addEventListener('click', async (event) => {
    if (event.target === scannerModal) {
      await stopScan();
      closeModal(scannerModal);
    }
  });

  resultModal.addEventListener('click', (event) => {
    if (event.target === resultModal) {
      closeModal(resultModal);
    }
  });

  manualForm.addEventListener('submit', (event) => {
    event.preventDefault();
    const reference = bookingReferenceInput.value.trim();
    if (!reference) {
      setResult('invalid', 'Enter a booking reference.', null, 'error');
      return;
    }
    validateTicket({ booking_reference: reference });
  });

  function toggleManualSubmit() {
    const reference = bookingReferenceInput.value.trim();
    manualSubmit.disabled = !reference;
  }

  bookingReferenceInput.addEventListener('input', toggleManualSubmit);
  toggleManualSubmit();

  scannerHint.textContent = '';
</script>
@endsection
