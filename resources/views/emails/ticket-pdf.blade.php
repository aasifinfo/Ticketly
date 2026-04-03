@php
  $portalFeePercentage = ticketly_format_percentage(ticketly_setting('portal_fee_percentage', config('ticketly.portal_fee_percentage', 10)));
  $serviceFeePercentage = ticketly_format_percentage(ticketly_setting('service_fee_percentage', config('ticketly.service_fee_percentage', 5)));
  $eventStartsAt = $booking->event->starts_at;
  $eventEndsAt = $booking->event->ends_at;
  $isMultiDayEvent = $eventEndsAt && ! $eventStartsAt->isSameDay($eventEndsAt);
  $eventStartDisplay = ticketly_format_compact_datetime($eventStartsAt);
  $eventEndDisplay = ticketly_format_compact_datetime($eventEndsAt);
  $promoDiscountLabel = 'Discount';
  if ($booking->promoCode) {
    $promoValue = $booking->promoCode->type === 'percentage'
      ? ticketly_format_percentage($booking->promoCode->value) . '%'
      : ticketly_money($booking->promoCode->value);
    $promoDiscountLabel .= ' (' . $booking->promoCode->code . ' - ' . $promoValue . ')';
  }
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Ticket - {{ $booking->reference }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

<style>

/* PAGE (PDF) */
@page {
  size: A4;
  margin: 5mm;
}

body {
  font-size: 10px;
  font-family: 'Inter', 'Helvetica Neue', Arial, sans-serif;
}

.sheet,
.sheet * {
  font-family: inherit;
}

.sheet {
  height: 1000px;
  overflow: hidden;
}

@media print {
  body {
    zoom: 0.82;
  }
}
/* HEADER */
.header {
  background: #0f172a;
  color: #fff;
  padding: 16px;
}

.event-title {
  font-size: 20px;
  font-weight: 600;
}

/* CONTENT */
.content {
  padding: 16px;
}

/* STATUS */
.status-chip {
  display: inline-block;
  padding: 6px 12px;
  border-radius: 20px;
  background: #ecfdf5;
  border: 1px solid #10b981;
  font-size: 12px;
  margin-bottom: 12px;
}

/* REF BOX */
.ref-box {
  text-align: center;
  padding: 12px;
  background: #eff6ff;
  border-radius: 8px;
  margin-bottom: 12px;
}

.ref-value {
  font-size: 22px;
  font-weight: bold;
  letter-spacing: 2px;
}

/* SECTION */
.section {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  margin-bottom: 12px;
}

.section-title {
  background: #f8fafc;
  padding: 10px;
  font-weight: 600;
}

.section-body {
  padding: 10px;
}

/* TABLE */
table {
  width: 100%;
  border-collapse: collapse;
}

.kv td {
  padding: 6px 0;
}

.k {
  width: 120px;
  color: #64748b;
}

.v {
  font-weight: 500;
}

.tickets {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed; /* 🔥 IMPORTANT */
}

.tickets th,
.tickets td {
  padding: 6px 4px;
  font-size: 11px;
  border-bottom: 1px solid #eee;
}

/* COLUMN WIDTH FIX */
.tickets th:nth-child(1),
.tickets td:nth-child(1) {
  width: 55%;
  text-align: left;
}

.tickets th:nth-child(2),
.tickets td:nth-child(2) {
  width: 15%;
  text-align: center;
}

.tickets th:nth-child(3),
.tickets td:nth-child(3) {
  width: 30%;
  text-align: right;
}

/* TOTAL */
.totals td {
  padding: 5px 0;
}

.grand td {
  font-weight: bold;
  font-size: 14px;
}

/* QR */
.qr-wrap {
  text-align: center;
  margin: 16px 0;
}

.qr-img {
  width: 150px;
  height: 150px;
}

/* FOOTER */
.footer {
  text-align: center;
  font-size: 11px;
  padding: 10px;
  background: #f8fafc;
}

/* ---------------- MOBILE RESPONSIVE ---------------- */
@media (max-width: 600px) {

  .sheet {
    border-radius: 0;
  }

  .header {
    padding: 12px;
  }

  .event-title {
    font-size: 16px;
  }

  .content {
    padding: 12px;
  }

  .k {
    width: 90px;
    font-size: 12px;
  }

  .v {
    font-size: 12px;
  }

  .tickets th,
  .tickets td {
    font-size: 12px;
  }

  .qr-img {
    width: 120px;
    height: 120px;
  }

}

/* ---------------- PDF FIX ---------------- */
@media print {

  body {
    font-size: 11px;
  }

  .sheet {
    max-height: 1120px;
    overflow: hidden;
  }

  .qr-img {
    width: 130px;
    height: 130px;
  }

  .section {
    page-break-inside: avoid;
  }

}

</style>
</head>

<body>

<div class="sheet">

  <div class="header">
    <div class="brand">Ticketly E-Ticket</div>
    <div class="event-title">{{ $booking->event->title }}</div>
  </div>

  <div class="content">

    <div class="status-row">
      <span class="status-chip">Booking Confirmed</span>
    </div>

    <div class="ref-box">
      <div>Booking Reference</div>
      <div class="ref-value">{{ $booking->reference }}</div>
    </div>

    <!-- EVENT -->
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
            <td class="v">{{ ticketly_format_date($eventStartsAt) }}</td>
          </tr>
          <tr>
            <td class="k">Time</td>
            <td class="v">{{ ticketly_format_time($eventStartsAt) }} - {{ ticketly_format_time($eventEndsAt) }}</td>
          </tr>
          <tr>
            <td class="k">Venue</td>
            <td class="v">
              {{ $booking->event->venue_name }},
              {{ \Illuminate\Support\Str::limit($booking->event->venue_address, 60) }}
            </td>
          </tr>
        </table>
      </div>
    </div>

    <!-- TICKETS -->
    <div class="section">
      <div class="section-title">Ticket Summary</div>
      <div class="section-body">
        <table class="tickets">
          <thead>
            <tr>
              <th>Type</th>
              <th>Qty</th>
              <th>Amount</th>
            </tr>
          </thead>
          <tbody>
            @foreach($booking->items as $item)
            <tr>
              <td>{{ $item->ticketTier->name }}</td>
              <td>{{ $item->quantity }}</td>
              <td>{{ ticketly_money($item->subtotal) }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>

        <table class="totals">
          <tr>
            <td>Subtotal</td>
            <td align="right">{{ ticketly_money($booking->subtotal) }}</td>
          </tr>
          <tr>
            <td>Portal Fee ({{ $portalFeePercentage }}%)</td>
            <td align="right">{{ ticketly_money($booking->portal_fee ?? 0) }}</td>
          </tr>
          <tr>
            <td>Service Fee ({{ $serviceFeePercentage }}%)</td>
            <td align="right">{{ ticketly_money($booking->service_fee ?? 0) }}</td>
          </tr>
          @if(($booking->discount_amount ?? 0) > 0)
          <tr>
            <td>{{ $promoDiscountLabel }}</td>
            <td align="right">-{{ ticketly_money($booking->discount_amount) }}</td>
          </tr>
          @endif
          <tr>
            <td>Total Paid</td>
            <td align="right"><b>{{ ticketly_money($booking->total) }}</b></td>
          </tr>
        </table>
      </div>
    </div>

    <!-- ATTENDEE -->
    <div class="section">
      <div class="section-title">Attendee</div>
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

    <!-- QR -->
    <div class="qr-wrap">
      <img class="qr-img" src="{{ $qrImageSrc }}">
      <div>Scan at Entry</div>
    </div>

  </div>

  <div class="footer">
    Ticketly | {{ $booking->reference }}
  </div>

</div>

</body>
</html>
