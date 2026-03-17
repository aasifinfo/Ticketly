<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class ScanValidationController extends Controller
{
    public function validateScan(Request $request)
    {
        $data = $request->validate([
            'ticket_id' => 'nullable|integer',
            'booking_reference' => 'nullable|string',
        ]);

        if (empty($data['ticket_id']) && empty($data['booking_reference'])) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Provide ticket_id or booking_reference.',
            ], 422);
        }

        $query = Booking::with('event');
        if (!empty($data['booking_reference'])) {
            $reference = strtoupper(trim($data['booking_reference']));
            $query->where('reference', $reference);
        } else {
            $query->where('id', $data['ticket_id']);
        }

        $booking = $query->first();

        if (!$booking || !$booking->event) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Ticket not found.',
            ], 404);
        }

        $eventDate = $booking->event->starts_at?->copy()->startOfDay();
        $today     = now()->startOfDay();

        if ($eventDate && $eventDate->lt($today)) {
            return response()->json([
                'status' => 'event_expired',
                'message' => 'This event has already expired.',
                'event_date' => $eventDate->toDateString(),
                'ticket_id' => $booking->id,
                'booking_reference' => $booking->reference,
            ]);
        }

        if ($eventDate && $eventDate->gt($today)) {
            return response()->json([
                'status' => 'event_future',
                'message' => 'This event is scheduled for a future date.',
                'event_date' => $eventDate->toDateString(),
                'ticket_id' => $booking->id,
                'booking_reference' => $booking->reference,
            ]);
        }

        $updated = Booking::where('id', $booking->id)
            ->whereNull('scanned_at')
            ->update(['scanned_at' => now()]);

        if ($updated === 0) {
            $booking->refresh();
            return response()->json([
                'status' => 'already_scanned',
                'message' => 'Ticket already scanned.',
                'ticket_id' => $booking->id,
                'booking_reference' => $booking->reference,
                'scanned_at' => $booking->scanned_at,
            ], 409);
        }

        return response()->json([
            'status' => 'validated',
            'message' => 'Ticket validated.',
            'ticket_id' => $booking->id,
            'booking_reference' => $booking->reference,
            'event_date' => $eventDate?->toDateString(),
            'scanned_at' => now()->toDateTimeString(),
        ]);
    }
}
