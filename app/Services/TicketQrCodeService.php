<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Http;

class TicketQrCodeService
{
    public function payloadForBooking(Booking $booking): string
    {
        return route('events.show', [
            'slug' => $booking->event->slug,
            'ticket_uuid' => $booking->ticket_uuid,
            'booking_reference' => $booking->reference,
        ]);
    }

    public function sourceForBooking(Booking $booking, int $size = 220): string
    {
        $payload = $this->payloadForBooking($booking);

        return $this->dataUriForPayload($payload, $size)
            ?? $this->imageUrlForPayload($payload, $size);
    }

    public function imageUrlForPayload(string $payload, int $size = 220): string
    {
        $dimension = max(50, $size) . 'x' . max(50, $size);

        return 'https://api.qrserver.com/v1/create-qr-code/?' . http_build_query([
            'size' => $dimension,
            'data' => $payload,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function dataUriForPayload(string $payload, int $size = 220): ?string
    {
        try {
            $response = Http::accept('image/png')
                ->timeout(10)
                ->retry(2, 200)
                ->get($this->imageUrlForPayload($payload, $size));
        } catch (\Throwable) {
            return null;
        }

        $body = $response->body();

        if (!$response->successful() || $body === '') {
            return null;
        }

        $contentType = trim((string) $response->header('Content-Type', 'image/png'));
        if (str_contains($contentType, ';')) {
            $contentType = strtok($contentType, ';') ?: 'image/png';
        }

        return 'data:' . ($contentType ?: 'image/png') . ';base64,' . base64_encode($body);
    }
}
