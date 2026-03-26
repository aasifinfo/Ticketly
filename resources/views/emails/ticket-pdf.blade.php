@php
  $portalFeePercentage = ticketly_format_percentage(ticketly_setting('portal_fee_percentage', config('ticketly.portal_fee_percentage', 10)));
  $serviceFeePercentage = ticketly_format_percentage(ticketly_setting('service_fee_percentage', config('ticketly.service_fee_percentage', 5)));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Ticket - {{ $booking->reference }}</title>
<style>
  @font-face {
    font-family: 'Nirmala';
    src: url('file://{{ str_replace('\\', '/', public_path('fonts/Nirmala.ttf')) }}') format('truetype');
    font-weight: normal;
    font-style: normal;
  }
  @font-face {
    font-family: 'Nirmala';
    src: url('file://{{ str_replace('\\', '/', public_path('fonts/NirmalaB.ttf')) }}') format('truetype');
    font-weight: 700;
    font-style: normal;
  }
  @font-face {
    font-family: 'Nirmala';
    src: url('file://{{ str_replace('\\', '/', public_path('fonts/NirmalaB.ttf')) }}') format('truetype');
    font-weight: 700;
    font-style: normal;
  }
  @page { size: A4; margin: 10mm; }
  * { box-sizing: border-box; }
  body {
    margin: 0;
    padding: 0;
    background: #f3f5f7;
    color: #1f2937;
    font-family: Nirmala, DejaVu Sans, Arial, sans-serif;
    font-size: 12px;
    line-height: 1.5;
  }
  .sheet {
    width: 100%;
    background: #ffffff;
    border: 1px solid #dbe2ea;
    border-radius: 12px;
    overflow: hidden;
  }
  .header {
    background: #0f172a;
    color: #ffffff;
    padding: 18px 22px;
    border-bottom: 4px solid #2563eb;
  }
  .brand {
    font-size: 11px;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #93c5fd;
    margin-bottom: 6px;
    font-weight: 700;
  }
  .event-title {
    margin: 0;
    font-size: 24px;
    line-height: 1.25;
    font-weight: 700;
    word-break: break-word;
  }
  .event-sub {
    margin-top: 6px;
    font-size: 12px;
    color: #cbd5e1;
  }

  .content {
    padding: 18px 22px 14px;
  }

  .status-row {
    margin-bottom: 14px;
    text-align: center;
  }
  .status-chip {
    display: inline-block;
    padding: 7px 12px;
    border-radius: 999px;
    background: #ecfdf5;
    border: 1px solid #10b981;
    color: #047857;
    font-weight: 700;
    font-size: 11px;
    letter-spacing: 0.02em;
  }

  .ref-box {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 10px;
    padding: 14px;
    text-align: center;
    margin-bottom: 14px;
  }
  .ref-label {
    color: #1d4ed8;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    margin-bottom: 4px;
  }
  .ref-value {
    color: #0f172a;
    font-size: 26px;
    font-weight: 900;
    letter-spacing: 0.14em;
    margin: 0;
  }
  .ref-help {
    font-size: 11px;
    color: #334155;
    margin-top: 4px;
  }
  .qr-wrap {
    margin: 0 auto 14px;
    width: 170px;
    text-align: center;
  }
  .qr-img {
    width: 170px;
    height: 170px;
    display: block;
    margin: 0 auto;
    border: 1px solid #dbe2ea;
    border-radius: 8px;
    background: #ffffff;
    padding: 6px;
  }
  .qr-label {
    margin-top: 6px;
    font-size: 10px;
    color: #475569;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
  }

  .section {
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    margin-bottom: 12px;
    overflow: hidden;
  }
  .section-title {
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
    padding: 10px 12px;
    font-size: 13px;
    font-weight: 800;
    color: #0f172a;
  }
  .section-body {
    padding: 10px 12px;
  }

  table { width: 100%; border-collapse: collapse; }

  .kv td {
    padding: 6px 0;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: top;
  }
  .kv tr:last-child td { border-bottom: 0; }
  .kv .k {
    width: 120px;
    color: #64748b;
    font-weight: 600;
  }
  .kv .v {
    color: #0f172a;
    font-weight: 700;
    word-break: break-word;
  }

  .tickets th {
    text-align: left;
    font-size: 11px;
    color: #475569;
    border-bottom: 1px solid #e2e8f0;
    padding: 7px 0;
  }
  .tickets th:nth-child(2),
  .tickets td:nth-child(2) { text-align: center; width: 64px; }
  .tickets th:last-child,
  .tickets td:last-child { text-align: right; width: 110px; }
  .tickets td {
    padding: 8px 0;
    border-bottom: 1px solid #f1f5f9;
    color: #0f172a;
  }
  .tickets tr:last-child td { border-bottom: 0; }

  .totals {
    margin-top: 8px;
    border-top: 1px dashed #cbd5e1;
    padding-top: 8px;
  }
  .totals td {
    padding: 3px 0;
    color: #475569;
  }
  .totals td:last-child { text-align: right; }
  .totals .discount td { color: #059669; }
  .totals .grand td {
    border-top: 1px solid #e2e8f0;
    padding-top: 7px;
    font-size: 14px;
    color: #0f172a;
    font-weight: 700;
    font-family: Nirmala, DejaVu Sans, Arial, sans-serif;
  }
  .totals .grand td:last-child { color: #1d4ed8; }
  .currency-symbol {
    font-weight: 400;
    font-family: Nirmala, DejaVu Sans, Arial, sans-serif;
  }

  .footer {
    border-top: 1px solid #e5e7eb;
    background: #f8fafc;
    padding: 10px 12px;
    text-align: center;
    color: #64748b;
    font-size: 10px;
    line-height: 1.6;
  }
</style>
</head>
<body>
  <div class="sheet">
    <div class="header">
      <div class="brand">Ticketly E-Ticket</div>
      <h1 class="event-title">{{ $booking->event->title }}</h1>
      <!-- <div class="event-sub">{{ $booking->event->starts_at->format('l, d F Y') }} | {{ $booking->event->starts_at->format('g:ia') }} - {{ $booking->event->ends_at->format('g:ia') }}</div> -->
    </div>

    <div class="content">
      <div class="status-row">
        <span class="status-chip">Booking Confirmed</span>
      </div>

      <div class="ref-box">
        <div class="ref-label">Booking Reference</div>
        <p class="ref-value">{{ $booking->reference }}</p>
        <div class="ref-help">Show this PDF or booking reference at entry</div>
      </div>

      

      <div class="section">
        <div class="section-title">Event Details</div>
        <div class="section-body">
          <table class="kv">
            <tr>
              <td class="k">Event</td>
              <td class="v">{{ $booking->event->title }}</td>
            </tr>
            <tr>
              <td class="k">Date</td>
              <td class="v">{{ ticketly_format_date($booking->event->starts_at) }}</td>
            </tr>
            <tr>
              <td class="k">Time</td>
              <td class="v">{{ ticketly_format_time($booking->event->starts_at) }} - {{ ticketly_format_time($booking->event->ends_at) }}</td>
            </tr>
            <tr>
              <td class="k">Venue</td>
              <td class="v">{{ $booking->event->venue_name }}, {{ $booking->event->venue_address }}, {{ $booking->event->city }}</td>
            </tr>
          </table>
        </div>
      </div>

      <div class="section">
        <div class="section-title">Ticket Summary</div>
        <div class="section-body">
          <table class="tickets">
            <thead>
              <tr>
                <th>Ticket Type</th>
                <th>Qty</th>
                <th>Subtotal</th>
              </tr>
            </thead>
            <tbody>
              @foreach($booking->items as $item)
              <tr>
                <td>{{ $item->ticketTier->name }}</td>
                <td>{{ $item->quantity }}</td>
                <td>@if($item->unit_price == 0)Free @else {{ ticketly_money($item->subtotal) }}@endif</td>
              </tr>
              @endforeach
            </tbody>
          </table>

          <table class="totals">
            <tr>
              <td>Subtotal</td>
              <td>{{ ticketly_money($booking->subtotal) }}</td>
            </tr>
            <tr>
              <td>Portal Fee ({{ $portalFeePercentage }}%)</td>
              <td>{{ ticketly_money($booking->portal_fee ?? 0) }}</td>
            </tr>
            <tr>
              <td>Service Fee ({{ $serviceFeePercentage }}%)</td>
              <td>{{ ticketly_money($booking->service_fee ?? 0) }}</td>
            </tr>
            @if($booking->discount_amount > 0)
            <tr class="discount">
              <td>Discount{{ $booking->promoCode ? ' (' . $booking->promoCode->code . ')' : '' }}</td>
              <td>-{{ ticketly_money($booking->discount_amount) }}</td>
            </tr>
            @endif
            <tr class="grand">
              <td>Total Paid</td>
              <td><span class="currency-symbol">{{ ticketly_currency_symbol() }}</span>{{ number_format((float) $booking->total, 2) }}</td>
            </tr>
          </table>
        </div>
      </div>

      <div class="section">
        <div class="section-title">Attendee Details</div>
        <div class="section-body">
          <table class="kv">
            <tr>
              <td class="k">Name</td>
              <td class="v">{{ $booking->customer_name }}</td>
            </tr>
            <tr>
              <td class="k">Email</td>
              <td class="v">{{ $booking->customer_email }}</td>
            </tr>
          </table>
        </div>
      </div>
    </div>

    <div class="qr-wrap">
        <img class="qr-img"
             src="{{ $qrImageSrc }}"
             alt="QR code for booking reference {{ $booking->reference }}">
        <div class="qr-label">Scan at Entry Gate</div>
      </div>

    <div class="footer">
      Ticketly | Reference: {{ $booking->reference }} | support@ticketly.com<br>
      This ticket is valid only for the event and date shown above.
    </div>
  </div>
</body>
</html>
