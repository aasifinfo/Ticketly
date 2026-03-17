<?php

namespace App\Http\Controllers\Organiser;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\PromoCode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PromoCodeController extends Controller
{
    public function index(Request $request)
    {
        $organiser = $request->attributes->get('organiser');

        // Public AJAX promo validation (no organiser session needed)
        if (!$organiser) {
            return $this->validate($request);
        }

        $promos = PromoCode::withTrashed()
            ->where('organiser_id', $organiser->id)
            ->with('event')
            ->orderByDesc('created_at')
            ->paginate(20);

        $events = Event::where('organiser_id', $organiser->id)->orderBy('title')->get();

        return view('organiser.promos.index', compact('organiser', 'promos', 'events'));
    }

    public function create(Request $request)
    {
        $organiser = $request->attributes->get('organiser');
        $events    = Event::where('organiser_id', $organiser->id)->orderBy('title')->get();

        return view('organiser.promos.create', compact('organiser', 'events'));
    }

    public function store(Request $request)
    {
        $organiser = $request->attributes->get('organiser');

        $validated = $this->validatePromo($request);
        $validated = $this->sanitizePromoPayload($validated, $organiser->id);
        $validated['organiser_id'] = $organiser->id;

        PromoCode::create($validated);

        return redirect()->route('organiser.promos.index')
            ->with('success', 'Promo code ' . $validated['code'] . ' created.');
    }

    public function show(Request $request, int $id)
    {
        $organiser = $request->attributes->get('organiser');
        $promo     = PromoCode::withTrashed()
            ->where('id', $id)
            ->where('organiser_id', $organiser->id)
            ->with(['event', 'bookings'])
            ->firstOrFail();

        return view('organiser.promos.show', compact('organiser', 'promo'));
    }

    public function edit(Request $request, int $id)
    {
        $organiser = $request->attributes->get('organiser');
        $promo     = PromoCode::where('id', $id)
            ->where('organiser_id', $organiser->id)
            ->firstOrFail();
        $events = Event::where('organiser_id', $organiser->id)->orderBy('title')->get();

        if ($promo->trashed()) {
            return redirect()->route('organiser.promos.show', $promo->id)
                ->withErrors(['promo' => 'Deleted promo codes cannot be edited.']);
        }

        return view('organiser.promos.edit', compact('organiser', 'promo', 'events'));
    }

    public function update(Request $request, int $id)
    {
        $organiser = $request->attributes->get('organiser');
        $promo     = PromoCode::where('id', $id)
            ->where('organiser_id', $organiser->id)
            ->firstOrFail();

        if ($promo->trashed()) {
            return redirect()->route('organiser.promos.show', $promo->id)
                ->withErrors(['promo' => 'Deleted promo codes cannot be edited.']);
        }

        $validated = $this->validatePromo($request, $promo->id);
        $validated = $this->sanitizePromoPayload($validated, $organiser->id);

        $promo->update($validated);

        return redirect()->route('organiser.promos.edit', $promo->id)
            ->with('success', 'Promo code updated.');
    }

    public function deactivate(Request $request, int $id)
    {
        $organiser = $request->attributes->get('organiser');
        $promo     = PromoCode::where('id', $id)
            ->where('organiser_id', $organiser->id)
            ->firstOrFail();

        if ($promo->trashed()) {
            return back()->withErrors(['promo' => 'This promo code is already deleted.']);
        }

        $promo->update(['is_active' => false]);

        return back()->with('success', 'Promo code deactivated.');
    }

    public function activate(Request $request, int $id)
    {
        $organiser = $request->attributes->get('organiser');
        $promo     = PromoCode::where('id', $id)
            ->where('organiser_id', $organiser->id)
            ->firstOrFail();

        if ($promo->trashed()) {
            return back()->withErrors(['promo' => 'Deleted promo codes cannot be activated.']);
        }

        $promo->update(['is_active' => true]);

        return back()->with('success', 'Promo code activated.');
    }

    public function destroy(Request $request, int $id)
    {
        $organiser = $request->attributes->get('organiser');
        $promo     = PromoCode::withTrashed()
            ->where('id', $id)
            ->where('organiser_id', $organiser->id)
            ->firstOrFail();

        if ($this->isUsedPromo($promo)) {
            if (!$promo->trashed()) {
                $promo->delete();
            }

            $promo->is_active = false;
            $promo->save();

            return redirect()->route('organiser.promos.index')
                ->with('success', 'Used promo code was soft-deleted to preserve booking history.');
        }

        $promo->forceDelete();

        return redirect()->route('organiser.promos.index')
            ->with('success', 'Promo code deleted permanently.');
    }

    public function validate(Request $request)
    {
        $request->validate([
            'code'     => 'required|string',
            'subtotal' => 'required|numeric|min:0',
            'event_id' => 'nullable|integer',
        ]);

        $promo = PromoCode::where('code', strtoupper($request->code))
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->first();

        if ($promo && $request->filled('event_id')) {
            $event = Event::select('id', 'organiser_id')->find($request->event_id);
            $isApplicable = false;

            if ($event) {
                if ($promo->event_id) {
                    $isApplicable = (int) $promo->event_id === (int) $event->id;
                } elseif ($promo->organiser_id) {
                    $isApplicable = (int) $promo->organiser_id === (int) $event->organiser_id;
                }
            }

            if (!$isApplicable) {
                $promo = null;
            }
        }

        if (!$promo || !$promo->isValid()) {
            return response()->json([
                'valid'   => false,
                'message' => 'This promo code is invalid, expired, or has reached its usage limit.',
            ]);
        }

        $discount = $promo->calculateDiscount((float) $request->subtotal);

        return response()->json([
            'valid'    => true,
            'code'     => $promo->code,
            'type'     => $promo->type,
            'value'    => $promo->value,
            'discount' => $discount,
            'message'  => $promo->type === 'percentage'
                ? number_format($promo->value, 0) . '% discount applied - saving ' . number_format($discount, 2)
                : number_format($discount, 2) . ' discount applied',
        ]);
    }

    private function validatePromo(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'code'         => ['required', 'string', 'alpha_num', 'max:30', Rule::unique('promo_codes', 'code')->ignore($ignoreId)],
            'type'         => 'required|in:percentage,fixed',
            'value'        => 'required|numeric|min:0.01',
            'max_discount' => 'nullable|numeric|min:0.01',
            'max_uses'     => 'nullable|integer|min:1',
            'expires_at'   => 'nullable|date|after:today',
            'event_id'     => 'nullable|exists:events,id',
            'is_active'    => 'boolean',
        ]);
    }

    private function sanitizePromoPayload(array $validated, int $organiserId): array
    {
        if (!empty($validated['event_id'])) {
            $event = Event::where('id', $validated['event_id'])
                ->where('organiser_id', $organiserId)
                ->first();

            if (!$event) {
                unset($validated['event_id']);
            }
        }

        $validated['code'] = strtoupper($validated['code']);

        return $validated;
    }

    private function isUsedPromo(PromoCode $promo): bool
    {
        return (int) $promo->used_count > 0 || $promo->bookings()->exists();
    }
}
