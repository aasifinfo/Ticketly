<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organiser;
use App\Models\TicketTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OrganiserTicketTierValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    public function test_organiser_cannot_create_tier_with_min_per_order_below_one(): void
    {
        $organiser = $this->makeOrganiser();
        $event = $this->makeEvent($organiser);

        $response = $this
            ->withSession($this->organiserSession($organiser))
            ->from(route('organiser.tiers.create', $event->id))
            ->post(route('organiser.tiers.store', $event->id), $this->makeTierPayload([
                'min_per_order' => 0,
            ]));

        $response->assertRedirect(route('organiser.tiers.create', $event->id));
        $response->assertSessionHasErrors(['min_per_order']);
        $response->assertSessionHas('errors', function ($errors) {
            return $errors->first('min_per_order') === 'Minimum per order must be at least 1.';
        });

        $this->assertDatabaseCount('ticket_tiers', 0);
    }

    public function test_organiser_cannot_create_or_update_tier_with_max_per_order_below_ten(): void
    {
        $organiser = $this->makeOrganiser();
        $event = $this->makeEvent($organiser);

        $createResponse = $this
            ->withSession($this->organiserSession($organiser))
            ->from(route('organiser.tiers.create', $event->id))
            ->post(route('organiser.tiers.store', $event->id), $this->makeTierPayload([
                'max_per_order' => 9,
            ]));

        $createResponse->assertRedirect(route('organiser.tiers.create', $event->id));
        $createResponse->assertSessionHasErrors(['max_per_order']);
        $createResponse->assertSessionHas('errors', function ($errors) {
            return $errors->first('max_per_order') === 'Max per order must be at least 10.';
        });

        $this->assertDatabaseCount('ticket_tiers', 0);

        $tier = TicketTier::create([
            'event_id' => $event->id,
            'name' => 'General Admission',
            'description' => 'Standard entry',
            'price' => 25,
            'total_quantity' => 100,
            'available_quantity' => 100,
            'min_per_order' => 1,
            'max_per_order' => 10,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $updateResponse = $this
            ->withSession($this->organiserSession($organiser))
            ->from(route('organiser.tiers.edit', [$event->id, $tier->id]))
            ->put(route('organiser.tiers.update', [$event->id, $tier->id]), $this->makeTierPayload([
                'name' => 'Updated Admission',
                'max_per_order' => 9,
            ]));

        $updateResponse->assertRedirect(route('organiser.tiers.edit', [$event->id, $tier->id]));
        $updateResponse->assertSessionHasErrors(['max_per_order']);
        $updateResponse->assertSessionHas('errors', function ($errors) {
            return $errors->first('max_per_order') === 'Max per order must be at least 10.';
        });

        $tier->refresh();

        $this->assertSame('General Admission', $tier->name);
        $this->assertSame(10, $tier->max_per_order);
    }

    private function makeOrganiser(array $overrides = []): Organiser
    {
        return Organiser::create(array_merge([
            'name' => 'Tier Organiser',
            'company_name' => 'Tier Co',
            'email' => 'tier.organiser@example.com',
            'password' => Hash::make('password123'),
            'phone' => '07123456789',
            'is_approved' => true,
        ], $overrides));
    }

    private function makeEvent(Organiser $organiser, array $overrides = []): Event
    {
        return Event::create(array_merge([
            'organiser_id' => $organiser->id,
            'title' => 'Ticket Tier Event',
            'slug' => 'ticket-tier-event',
            'short_description' => 'Short description',
            'description' => 'Event description',
            'category' => 'Music',
            'starts_at' => now()->addDays(7),
            'ends_at' => now()->addDays(7)->addHours(4),
            'ticket_validation_starts_at' => now()->addDays(7)->subHour(),
            'ticket_validation_ends_at' => now()->addDays(7)->addHours(4),
            'venue_name' => 'Main Hall',
            'venue_address' => '123 Event Street',
            'city' => 'London',
            'country' => 'GB',
            'postcode' => 'E1 6AN',
            'parking_info' => 'Parking nearby',
            'refund_policy' => 'Refunds available up to 48 hours before the event.',
            'status' => 'draft',
            'approval_status' => 'approved',
            'total_capacity' => 0,
        ], $overrides));
    }

    private function makeTierPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'VIP',
            'description' => 'Premium access',
            'price' => 50,
            'total_quantity' => 100,
            'min_per_order' => 1,
            'max_per_order' => 10,
            'is_active' => 1,
        ], $overrides);
    }

    private function organiserSession(Organiser $organiser): array
    {
        return [
            'organiser_id' => $organiser->id,
            'organiser_last_active' => now()->timestamp,
        ];
    }
}
