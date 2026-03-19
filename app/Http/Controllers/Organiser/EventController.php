<?php

namespace App\Http\Controllers\Organiser;

use App\Http\Controllers\Controller;
use App\Jobs\SendEventCancellationNotification;
use App\Models\Booking;
use App\Models\Event;
use App\Models\TicketTier;
use App\Services\RefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function __construct(
        private readonly RefundService $refundService
    ) {}

    public function index(Request $request)
    {
        $organiser = $request->attributes->get('organiser');
        $activeTab = $request->input('tab', $request->input('status', 'all'));
        $paidStatuses = ['paid', 'partially_refunded'];

        $query = Event::where('organiser_id', $organiser->id)
            ->withSum(['bookingItems as sold_tickets' => fn($q) => $q->whereHas('booking', fn($b) => $b->whereIn('status', $paidStatuses))], 'quantity')
            ->withSum(['bookings as total_revenue' => fn($q) => $q->whereIn('status', $paidStatuses)], 'total');

        if (in_array($activeTab, ['published', 'draft'], true)) {
            $query->where('status', $activeTab);
        } elseif ($activeTab === 'past') {
            $query->where(function ($q) {
                $q->where('status', 'cancelled')
                  ->orWhere('ends_at', '<', now());
            });
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $events = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $countsBaseQuery = Event::where('organiser_id', $organiser->id);
        $eventCounts = [
            'all' => (clone $countsBaseQuery)->count(),
            'published' => (clone $countsBaseQuery)->where('status', 'published')->count(),
            'draft' => (clone $countsBaseQuery)->where('status', 'draft')->count(),
            'past' => (clone $countsBaseQuery)->where(function ($q) {
                $q->where('status', 'cancelled')
                  ->orWhere('ends_at', '<', now());
            })->count(),
        ];

        return view('organiser.events.index', compact('organiser', 'events', 'eventCounts', 'activeTab'));
    }

    public function create(Request $request)
    {
        $organiser = $request->attributes->get('organiser');
        return view('organiser.events.create', compact('organiser'));
    }

    public function show(Request $request, int $id)
    {
        $organiser = $request->attributes->get('organiser');
        $paidStatuses = ['paid', 'partially_refunded'];
        $event = Event::where('id', $id)
            ->where('organiser_id', $organiser->id)
            ->with(['ticketTiers' => function ($q) {
                $q->withSum([
                    'bookingItems as paid_sold_quantity' => fn($b) => $b->whereHas('booking', fn($q) => $q->whereIn('status', ['paid', 'partially_refunded'])),
                ], 'quantity');
            }])
            ->withSum(['bookingItems as sold_tickets' => fn($q) => $q->whereHas('booking', fn($b) => $b->whereIn('status', $paidStatuses))], 'quantity')
            ->withSum(['bookings as total_revenue' => fn($q) => $q->whereIn('status', $paidStatuses)], 'total')
            ->firstOrFail();

        return view('organiser.events.show', compact('organiser', 'event'));
    }

    public function store(Request $request)
    {
        $organiser = $request->attributes->get('organiser');
        $validated = $this->validateEvent($request);
        $validated['is_featured'] = $request->boolean('is_featured');

        // Handle banner upload
        if ($request->hasFile('banner')) {
            $validated['banner'] = $this->storeBannerFile($request->file('banner'));
        }

        $validated['organiser_id'] = $organiser->id;
        $validated['slug']         = Event::uniqueSlug($validated['title']);
        $validated['performer_lineup'] = $this->parseLineup($request);

        $event = Event::create($validated);

        return redirect()->route('organiser.tiers.create', $event->id)
            ->with('success', 'Event created! Now add your ticket tiers.');
    }

    public function edit(Request $request, int $id)
    {
        $organiser = $request->attributes->get('organiser');
        $event     = Event::where('id', $id)->where('organiser_id', $organiser->id)->firstOrFail();
        return view('organiser.events.edit', compact('organiser', 'event'));
    }

    public function update(Request $request, int $id)
    {
        $organiser = $request->attributes->get('organiser');
        $event     = Event::where('id', $id)->where('organiser_id', $organiser->id)->firstOrFail();

        $validated = $this->validateEvent($request, $event->id);
        $validated['is_featured'] = $request->boolean('is_featured');

        if ($request->hasFile('banner')) {
            $this->deleteBannerFile($event->banner);
            $validated['banner'] = $this->storeBannerFile($request->file('banner'));
        }

        $validated['performer_lineup'] = $this->parseLineup($request);
        $event->update($validated);

        return redirect()->route('organiser.events.edit', $event->id)
            ->with('success', 'Event updated successfully.');
    }

    public function destroy(Request $request, int $id)
    {
        $organiser = $request->attributes->get('organiser');
        $event     = Event::where('id', $id)->where('organiser_id', $organiser->id)->firstOrFail();

        $paidBookings = Booking::where('event_id', $event->id)->whereIn('status', ['paid', 'partially_refunded'])->count();
        if ($paidBookings > 0) {
            return back()->withErrors(['delete' => 'Cannot delete an event with paid bookings.']);
        }

        $this->deleteBannerFile($event->banner);
        $event->delete();

        return redirect()->route('organiser.events.index')
            ->with('success', 'Event deleted.');
    }

    // ── Status Transitions ────────────────────────────────────────
    public function updateStatus(Request $request, int $id)
    {
        $organiser = $request->attributes->get('organiser');
        $event     = Event::where('id', $id)->where('organiser_id', $organiser->id)->firstOrFail();

        $request->validate([
            'status' => 'required|in:draft,published,cancelled',
        ]);

        $newStatus = $request->status;

        // Prevent transitioning from cancelled
        if ($event->isCancelled()) {
            return back()->withErrors(['status' => 'A cancelled event cannot be changed to another status.']);
        }

        // Handle cancellation flow
        if ($newStatus === 'cancelled') {
            return $this->cancelEvent($request, $event);
        }

        $updatePayload = ['status' => $newStatus];

        if ($newStatus === 'published' && $event->approval_status !== 'approved') {
            $updatePayload = array_merge($updatePayload, [
                'approval_status' => 'pending',
                'approved_at' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
                'approved_by_admin_id' => null,
                'rejected_by_admin_id' => null,
            ]);
        }

        $event->update($updatePayload);

        if ($newStatus === 'published' && ($updatePayload['approval_status'] ?? null) === 'pending') {
            return back()->with('info', 'Event submitted for admin approval.');
        }

        return back()->with('success', 'Event status updated to ' . ucfirst($newStatus) . '.');
    }

    // ── Cancellation ──────────────────────────────────────────────
    private function cancelEvent(Request $request, Event $event)
    {
        $request->validate([
            'cancellation_reason' => 'required|string|min:10|max:1000',
        ]);

        // Snapshot paid bookings before refunds change booking statuses.
        $bookings = Booking::where('event_id', $event->id)
            ->whereIn('status', ['paid', 'partially_refunded'])
            ->get();

        DB::transaction(function () use ($request, $event) {
            $event->update([
                'status'              => 'cancelled',
                'cancelled_at'        => now(),
                'cancellation_reason' => $request->cancellation_reason,
            ]);
        });

        foreach ($bookings as $booking) {
            dispatch(new SendEventCancellationNotification($booking, $request->cancellation_reason));
        }

        $refundReason = 'Event cancelled by organiser: ' . $request->cancellation_reason;
        $refunds      = $this->refundService->processBulkCancellationRefunds($event->id, $refundReason);

        Log::info('[EventController] Event cancelled and bulk actions initiated', [
            'event_id'          => $event->id,
            'notified_bookings' => $bookings->count(),
            'refunds_processed' => $refunds['processed'] ?? 0,
            'refunds_failed'    => $refunds['failed'] ?? 0,
        ]);

        $message = 'Event cancelled. All ticket holders will be notified and refunds have been initiated.';
        if (($refunds['failed'] ?? 0) > 0) {
            $message .= ' Some refunds failed; please check logs/orders for details.';
        }

        return redirect()->route('organiser.events.index')
            ->with('success', $message);
    }

    // Helpers
    private function validateEvent(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'title'               => 'required|string|max:255',
            'short_description'   => 'nullable|string|max:500',
            'description'         => 'nullable|string',
            'banner'              => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'category'            => 'required|string|max:100',
            'starts_at'           => 'required|date|after:now',
            'ends_at'             => 'required|date|after:starts_at',
            'venue_name'          => 'required|string|max:255',
            'venue_address'       => 'required|string|max:255',
            'city'                => 'required|string|max:100',
            'country'             => 'nullable|string|max:100',
            'postcode'            => 'nullable|string|max:20',
            'parking_info'        => 'nullable|string|max:2000',
            'refund_policy'       => 'nullable|string|max:2000',
            'status'              => 'nullable|in:draft,published',
            'is_featured'         => 'nullable|boolean',
        ]);
    }

    private function parseLineup(Request $request): ?array
    {
        $names = $request->input('lineup_names', []);
        $roles = $request->input('lineup_roles', []);
        $times = $request->input('lineup_times', []);

        $lineup = [];
        foreach ($names as $i => $name) {
            if (empty($name)) continue;
            $lineup[] = [
                'name' => $name,
                'role' => $roles[$i] ?? '',
                'time' => $times[$i] ?? '',
            ];
        }
        return empty($lineup) ? null : $lineup;
    }

    private function storeBannerFile(\Illuminate\Http\UploadedFile $file): string
    {
        $directory = $this->getUploadsRoot() . DIRECTORY_SEPARATOR . 'events';
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filename = (string) Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return 'uploads/events/' . $filename;
    }

    private function deleteBannerFile(?string $banner): void
    {
        if (!$banner) {
            return;
        }

        $path = $this->resolveBannerPath($banner);
        if ($path && File::exists($path)) {
            File::delete($path);
        }
    }

    private function resolveBannerPath(string $banner): ?string
    {
        if (str_starts_with($banner, 'http://') || str_starts_with($banner, 'https://')) {
            return null;
        }

        if (str_starts_with($banner, 'uploads/')) {
            $basePath = base_path($banner);
            if (File::exists($basePath)) {
                return $basePath;
            }

            return public_path($banner);
        }

        $fallback = 'uploads/events/' . basename($banner);
        $basePath = base_path($fallback);
        if (File::exists($basePath)) {
            return $basePath;
        }

        return public_path($fallback);
    }

    private function getUploadsRoot(): string
    {
        $baseUploads = base_path('uploads');
        if (File::exists($baseUploads)) {
            return $baseUploads;
        }

        return public_path('uploads');
    }
}
