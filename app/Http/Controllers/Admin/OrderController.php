<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Organiser;
use App\Models\TicketTier;
use App\Services\RefundService;
use App\Services\ServiceFeeCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct(private readonly RefundService $refundService)
    {}

    public function index(Request $request)
    {
        $query = Booking::with(['event.organiser', 'customer']);

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->event_id);
        }

        if ($request->filled('organiser_id')) {
            $query->whereHas('event', fn($q) => $q->where('organiser_id', $request->organiser_id));
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('customer_email')) {
            $query->where('customer_email', 'like', '%' . $request->customer_email . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%");
            });
        }

        $orders = $query->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $events = Event::orderBy('title')->get(['id', 'title']);
        $organisers = Organiser::orderBy('name')->get(['id', 'name']);

        return view('admin.orders.index', compact('orders', 'events', 'organisers'));
    }

    public function show(int $id)
    {
        $booking = Booking::with(['event.organiser', 'items.ticketTier', 'promoCode', 'customer'])
            ->findOrFail($id);

        return view('admin.orders.show', compact('booking'));
    }

    public function refund(Request $request, int $id)
    {
        $booking = Booking::with('event')->findOrFail($id);

        if (!in_array($booking->status, ['paid', 'partially_refunded'], true)) {
            return back()->withErrors(['refund' => 'Only paid bookings can be refunded.']);
        }

        $validated = $request->validate([
            'refund_amount' => 'nullable|numeric|min:0.01|max:' . $booking->total,
            'refund_reason' => 'required|string|max:500',
        ]);

        $amount = $validated['refund_amount'] ?? null;

        $result = $this->refundService->process($booking, $amount, $validated['refund_reason'], true);
        if (!$result['success']) {
            return back()->withErrors(['refund' => $result['error'] ?? 'Refund failed.']);
        }

        return back()->with('success', 'Refund processed successfully.');
    }

    public function partialCancel(Request $request, int $id)
    {
        $booking = Booking::with(['items.ticketTier', 'promoCode', 'event'])
            ->findOrFail($id);

        if (!in_array($booking->status, ['paid', 'partially_refunded'], true)) {
            return back()->withErrors(['partial_refund' => 'Only paid bookings can be partially refunded.']);
        }

        $validated = $request->validate([
            'booking_item_id' => 'required|integer',
            'refund_quantity' => 'required|integer|min:1',
            'refund_reason'   => 'required|string|max:500',
        ]);

        $item = $booking->items->firstWhere('id', (int) $validated['booking_item_id']);
        if (!$item) {
            return back()->withErrors(['partial_refund' => 'Booking item not found.']);
        }

        $refundQty = (int) $validated['refund_quantity'];
        if ($refundQty > $item->quantity) {
            return back()->withErrors(['partial_refund' => 'Refund quantity exceeds purchased quantity.']);
        }

        $refundSubtotal = round(((float) $item->unit_price) * $refundQty, 2);
        $newSubtotal = max(0.0, round(((float) $booking->subtotal) - $refundSubtotal, 2));

        $discount = 0.0;
        if ($booking->promoCode) {
            $discount = $booking->promoCode->calculateDiscount($newSubtotal);
        }

        $pricing = ServiceFeeCalculator::total($newSubtotal, $discount);
        $newTotal = (float) $pricing['total'];
        $refundAmount = max(0.0, round(((float) $booking->total) - $newTotal, 2));

        if ($refundAmount <= 0) {
            return back()->withErrors(['partial_refund' => 'Refund amount would be 0. Adjust quantity.']);
        }

        $result = $this->refundService->process($booking, $refundAmount, $validated['refund_reason'], false);
        if (!$result['success']) {
            return back()->withErrors(['partial_refund' => $result['error'] ?? 'Refund failed.']);
        }

        DB::transaction(function () use ($booking, $item, $refundQty, $newSubtotal, $discount, $pricing) {
            $remainingQty = $item->quantity - $refundQty;

            if ($remainingQty <= 0) {
                $item->delete();
            } else {
                $item->update([
                    'quantity' => $remainingQty,
                    'subtotal' => round(((float) $item->unit_price) * $remainingQty, 2),
                ]);
            }

            TicketTier::where('id', $item->ticket_tier_id)
                ->increment('available_quantity', $refundQty);

            $booking->update([
                'subtotal' => $newSubtotal,
                'discount_amount' => $discount,
                'portal_fee' => $pricing['portal_fee'],
                'service_fee' => $pricing['service_fee'],
                'total' => $pricing['total'],
            ]);
        });

        return back()->with('success', 'Partial refund processed and ticket quantities updated.');
    }
}
