<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $query = Event::with(['ticketTiers', 'organiser'])
            ->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('approval_status')
                  ->orWhere('approval_status', 'approved');
            })
            ->where('starts_at', '>', now());

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('city')) {
            $query->where('city', 'like', '%' . $request->city . '%');
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%")
                  ->orWhere('short_description', 'like', "%{$s}%")
                  ->orWhere('venue_name', 'like', "%{$s}%")
                  ->orWhere('city', 'like', "%{$s}%");
            });
        }
        if ($request->filled('date')) {
            $query->whereDate('starts_at', $request->date); // backward compatibility
        }
        if ($request->filled('date_from')) {
            $query->whereDate('starts_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('starts_at', '<=', $request->date_to);
        }
        if ($request->filled('price') && $request->price === 'free') {
            $query->whereHas('ticketTiers', fn($q) => $q->where('price', 0)->where('is_active', true));
        }
        if ($request->filled('price_min') || $request->filled('price_max')) {
            $priceMin = is_numeric($request->price_min) ? (float) $request->price_min : 0;
            $priceMax = is_numeric($request->price_max) ? (float) $request->price_max : null;

            $query->whereHas('ticketTiers', function ($q) use ($priceMin, $priceMax) {
                $q->where('is_active', true)
                    ->where('price', '>=', $priceMin);

                if ($priceMax !== null) {
                    $q->where('price', '<=', $priceMax);
                }
            });
        }

        $query->orderBy('starts_at');

        $events = $query->paginate(12)->withQueryString();

        $categoryCounts = Event::where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('approval_status')
                  ->orWhere('approval_status', 'approved');
            })
            ->where('starts_at', '>', now())
            ->selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->orderByDesc('count')
            ->pluck('count', 'category');
        $categories = collect(Event::CATEGORIES)
            ->mapWithKeys(fn($cat) => [$cat => (int) ($categoryCounts[$cat] ?? 0)]);

        $cities = Event::where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('approval_status')
                  ->orWhere('approval_status', 'approved');
            })
            ->where('starts_at', '>', now())
            ->selectRaw('city, COUNT(*) as count')
            ->groupBy('city')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'city');

        return view('events.index', compact('events', 'categories', 'cities'));
    }

    public function show(string $slug)
    {
        $event = Event::with(['ticketTiers' => fn($q) => $q->where('is_active', true)->orderBy('price'), 'organiser'])
            ->where('slug', $slug)
            ->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('approval_status')
                  ->orWhere('approval_status', 'approved');
            })
            ->firstOrFail();

        // Related events (same category, different event)
        $relatedEvents = Event::with('ticketTiers')
            ->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('approval_status')
                  ->orWhere('approval_status', 'approved');
            })
            ->where('starts_at', '>', now())
            ->where('category', $event->category)
            ->where('id', '!=', $event->id)
            ->limit(3)
            ->get();

        return view('events.show', compact('event', 'relatedEvents'));
    }
}
