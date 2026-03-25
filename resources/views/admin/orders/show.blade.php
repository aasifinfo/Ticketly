@extends('layouts.admin')

@section('title', 'Order Details')
@section('page-title', 'Order')
@section('page-subtitle', $booking->reference)

@section('content')
<div class="grid gap-6">
  <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <div class="text-lg font-bold text-white">{{ $booking->reference }}</div>
        <div class="text-sm text-gray-400">{{ $booking->customer_name }} · {{ $booking->customer_email }}</div>
        <div class="text-xs text-gray-500">{{ $booking->event?->title }}</div>
        <div class="mt-2 flex flex-wrap gap-3 text-xs">
          @if($booking->event)
            <a class="text-emerald-400 hover:text-emerald-300" href="{{ route('admin.events.show', $booking->event->id) }}">View Event</a>
          @endif
          @if($booking->event?->organiser)
            <a class="text-sky-400 hover:text-sky-300" href="{{ route('admin.organisers.show', $booking->event->organiser->id) }}">View Organiser</a>
          @endif
        </div>
      </div>
      <div class="text-right">
        <div class="text-lg font-bold text-white">{{ ticketly_money($booking->total) }}</div>
        <div class="text-xs text-gray-400">{{ $booking->status_badge['label'] }}</div>
      </div>
    </div>
  </div>

  <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5">
    <h2 class="text-sm font-semibold text-white mb-4">Ticket Items</h2>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="text-gray-400 text-xs uppercase">
          <tr>
            <th class="text-left py-3">Tier</th>
            <th class="text-left py-3">Quantity</th>
            <th class="text-left py-3">Price</th>
            <th class="text-left py-3">Subtotal</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-800">
          @foreach($booking->items as $item)
          <tr>
            <td class="py-3 text-white font-semibold">{{ $item->ticketTier?->name }}</td>
            <td class="py-3 text-gray-300">{{ $item->quantity }}</td>
            <td class="py-3 text-gray-300">{{ ticketly_money($item->unit_price) }}</td>
            @php
              $lineSubtotal = (float) $item->unit_price * (int) $item->quantity;
            @endphp
            <td class="py-3 text-gray-300">{{ ticketly_money($lineSubtotal) }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  @php
    $refundTotal = (float) ($booking->refund_amount ?? 0);
    $hasRefund = $refundTotal > 0 || $booking->refunded_at || $booking->refund_reason;
    $originalTotal = $booking->isPartiallyRefunded()
      ? ($refundTotal + (float) $booking->total)
      : (float) $booking->total;
    $remainingTotal = max($originalTotal - $refundTotal, 0);
    $refundStatus = $booking->isFullyRefunded()
      ? 'Full refund'
      : ($booking->isPartiallyRefunded() ? 'Partial refund' : ucfirst($booking->status));
  @endphp
  <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5">
      <h2 class="text-sm font-semibold text-white mb-4">Charges</h2>
      <div class="space-y-2 text-sm">
        <div class="flex items-center justify-between text-gray-300">
          <span>Subtotal</span>
          <span>{{ ticketly_money($booking->subtotal ?? 0) }}</span>
        </div>
        <div class="flex items-center justify-between text-gray-300">
          <span>Portal Fee</span>
          <span>{{ ticketly_money($booking->portal_fee ?? 0) }}</span>
        </div>
        <div class="flex items-center justify-between text-gray-300">
          <span>Service Fee </span>
          <span>{{ ticketly_money($booking->service_fee ?? 0) }}</span>
        </div>
        @if(($booking->discount_amount ?? 0) > 0)
        <div class="flex items-center justify-between text-gray-300">
          <span>Discount</span>
          <span>-{{ ticketly_money($booking->discount_amount) }}</span>
        </div>
        @endif
        <div class="border-t border-gray-800 pt-2 flex items-center justify-between text-white font-semibold">
          <span>Total</span>
          <span>{{ ticketly_money($booking->total ?? 0) }}</span>
        </div>
      </div>
    </div>

    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5">
      <h2 class="text-sm font-semibold text-white mb-4">Refund History</h2>
      @if($hasRefund)
        <div class="space-y-2 text-sm">
          <div class="flex items-center justify-between text-gray-300">
            <span>Status</span>
            <span>{{ $refundStatus }}</span>
          </div>
          <div class="flex items-center justify-between text-gray-300">
            <span>Original Total</span>
            <span>{{ ticketly_money($originalTotal) }}</span>
          </div>
          <div class="flex items-center justify-between text-gray-300">
            <span>Total Refunded</span>
            <span>{{ ticketly_money($refundTotal) }}</span>
          </div>
          @if($refundTotal > 0)
          <div class="flex items-center justify-between text-gray-300">
            <span>Remaining</span>
            <span>{{ ticketly_money($remainingTotal) }}</span>
          </div>
          @endif
          @if($booking->refunded_at)
          <div class="flex items-center justify-between text-gray-300">
            <span>Refunded At</span>
            <span>{{ ticketly_format_datetime($booking->refunded_at) }}</span>
          </div>
          @endif
          @if($booking->refund_reason)
          <div class="text-xs text-gray-400 pt-2">
            <span class="block uppercase mb-1 text-[11px] tracking-wide">Reason</span>
            <span class="text-gray-300">{{ $booking->refund_reason }}</span>
          </div>
          @endif
        </div>
      @else
        <p class="text-sm text-gray-400">No refunds recorded for this order.</p>
      @endif
    </div>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5">
      <h2 class="text-sm font-semibold text-white mb-4">Refund Entire Amount</h2>
      <form method="POST" action="{{ route('admin.orders.refund', $booking->id) }}" class="space-y-3" data-confirm="Process this refund?">
        @csrf
        <div>
          <label class="text-xs text-gray-400 uppercase">Refund Amount</label>
          <input type="number" step="0.01" name="refund_amount" value="{{ old('refund_amount', $booking->total) }}" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2 text-sm text-white">
        </div>
        <div>
          <label class="text-xs text-gray-400 uppercase">Reason</label>
          <textarea name="refund_reason" rows="3" maxlength="500" aria-describedby="refund-reason-full-error" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2 text-sm text-white" required></textarea>
          <p id="refund-reason-full-error" class="refund-reason-error mt-2 hidden text-sm text-rose-400"></p>
        </div>
        <button class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold">Process Refund</button>
      </form>
    </div>

    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5">
      <h2 class="text-sm font-semibold text-white mb-4">Partial Ticket Cancellation</h2>
      <form method="POST" action="{{ route('admin.orders.partial-cancel', $booking->id) }}" class="space-y-3" data-confirm="Process this partial refund?">
        @csrf
        <div>
          <label class="text-xs text-gray-400 uppercase">Ticket Tier</label>
          <select name="booking_item_id" id="booking-item-select" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2 text-sm text-white" required>
            @foreach($booking->items as $item)
              <option value="{{ $item->id }}" data-qty="{{ $item->quantity }}">{{ $item->ticketTier?->name }} ({{ $item->quantity }} tickets)</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="text-xs text-gray-400 uppercase">Quantity</label>
          <input type="number" name="refund_quantity" id="refund-qty-input" min="1" value="{{ old('refund_quantity') }}" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2 text-sm text-white" required>
        </div>
        <div>
          <label class="text-xs text-gray-400 uppercase">Reason</label>
          <textarea name="refund_reason" rows="3" maxlength="500" aria-describedby="refund-reason-partial-error" class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-2 text-sm text-white" required></textarea>
          <p id="refund-reason-partial-error" class="refund-reason-error mt-2 hidden text-sm text-rose-400"></p>
        </div>
        <button class="px-4 py-2 rounded-xl bg-amber-600 text-white text-sm font-semibold">Process Partial Refund</button>
      </form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  (function () {
    const select = document.getElementById('booking-item-select');
    const qtyInput = document.getElementById('refund-qty-input');
    if (!select || !qtyInput) return;
    const setQty = () => {
      const opt = select.options[select.selectedIndex];
      const qty = opt ? parseInt(opt.getAttribute('data-qty') || '1', 10) : 1;
      if (!qtyInput.value) qtyInput.value = qty;
      qtyInput.max = qty;
    };
    select.addEventListener('change', () => {
      qtyInput.value = '';
      setQty();
    });
    setQty();
  })();

  (function () {
    const limitMessage = 'Refund reason maximum limit reached.';

    document.querySelectorAll('textarea[name="refund_reason"][maxlength]').forEach((field) => {
      const errorEl = field.parentElement?.querySelector('.refund-reason-error');
      if (!errorEl) return;

      const toggleLimitMessage = () => {
        const maxLength = Number(field.getAttribute('maxlength') || 0);
        const showMessage = maxLength > 0 && field.value.length >= maxLength;

        errorEl.textContent = showMessage ? limitMessage : '';
        errorEl.classList.toggle('hidden', !showMessage);
      };

      field.addEventListener('input', toggleLimitMessage);
      field.addEventListener('blur', toggleLimitMessage);
      toggleLimitMessage();
    });
  })();
</script>
@endsection
