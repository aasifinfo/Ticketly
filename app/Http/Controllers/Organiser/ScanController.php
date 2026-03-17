<?php

namespace App\Http\Controllers\Organiser;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    public function index(Request $request)
    {
        $organiser = $request->attributes->get('organiser');

        return view('organiser.scan.index', compact('organiser'));
    }

    public function validateScan(Request $request)
    {
        $data = $request->validate([
            'booking_reference' => 'required|string',
        ]);

        $query = Booking::with('event');
        $reference = strtoupper(trim($data['booking_reference']));
        $query->where('reference', $reference);

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
            $eventStart = $booking->event->starts_at;
            return response()->json([
                'status' => 'event_expired',
                'message' => 'This event has already expired.',
                'event_date' => $eventDate->toDateString(),
                'event_start_time' => $eventStart?->toIso8601String(),
                'event_start_time_ts' => $eventStart ? $eventStart->getTimestamp() * 1000 : null,
                'ticket_id' => $booking->id,
                'booking_reference' => $booking->reference,
            ]);
        }

        if ($eventDate && $eventDate->gt($today)) {
            $eventStart = $booking->event->starts_at;
            return response()->json([
                'status' => 'event_future',
                'message' => 'This event is scheduled for a future date.',
                'event_date' => $eventDate->toDateString(),
                'event_start_time' => $eventStart?->toIso8601String(),
                'event_start_time_ts' => $eventStart ? $eventStart->getTimestamp() * 1000 : null,
                'ticket_id' => $booking->id,
                'booking_reference' => $booking->reference,
            ]);
        }

        $updated = Booking::where('id', $booking->id)
            ->whereNull('scanned_at')
            ->update(['scanned_at' => now()]);

        if ($updated === 0) {
            $booking->refresh();
            $scannedAt = $booking->scanned_at ? \Illuminate\Support\Carbon::parse($booking->scanned_at) : null;
            $eventStart = $booking->event->starts_at;
            return response()->json([
                'status' => 'already_scanned',
                'message' => 'Ticket already scanned.',
                'ticket_id' => $booking->id,
                'booking_reference' => $booking->reference,
                'event_start_time' => $eventStart?->toIso8601String(),
                'event_start_time_ts' => $eventStart ? $eventStart->getTimestamp() * 1000 : null,
                'scanned_at' => $scannedAt?->toIso8601String(),
                'scanned_at_ts' => $scannedAt ? $scannedAt->getTimestamp() * 1000 : null,
            ], 409);
        }

        $eventStart = $booking->event->starts_at;
        $scannedAt = now();

        return response()->json([
            'status' => 'validated',
            'message' => 'Ticket validated.',
            'ticket_id' => $booking->id,
            'booking_reference' => $booking->reference,
            'event_date' => $eventDate?->toDateString(),
            'event_start_time' => $eventStart?->toIso8601String(),
            'event_start_time_ts' => $eventStart ? $eventStart->getTimestamp() * 1000 : null,
            'scanned_at' => $scannedAt->toIso8601String(),
            'scanned_at_ts' => $scannedAt->getTimestamp() * 1000,
        ]);
    }
}
