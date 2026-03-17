<?php

namespace App\Services;

use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfTicketService
{
    public function generate(Booking $booking): string
    {
        $booking->loadMissing(['event', 'items.ticketTier', 'promoCode']);

        return Pdf::loadView('emails.ticket-pdf', compact('booking'))
            ->setPaper('a4', 'portrait')
            ->output();
    }

    public function getMimeType(): string
    {
        return 'application/pdf';
    }

    public function getExtension(): string
    {
        return 'pdf';
    }
}
