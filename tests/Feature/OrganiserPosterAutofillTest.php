<?php

namespace Tests\Feature;

use App\Models\Organiser;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrganiserPosterAutofillTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        config([
            'services.poster_ai.provider' => 'groq',
            'services.poster_ai.groq.api_key' => 'test-groq-key',
            'services.poster_ai.groq.model' => 'meta-llama/llama-4-scout-17b-16e-instruct',
            'services.poster_ai.groq.url' => 'https://api.groq.com/openai/v1/chat/completions',
            'services.poster_ai.groq.timeout' => 10,
        ]);
    }

    public function test_organiser_can_parse_poster_and_receive_normalized_autofill_json(): void
    {
        $organiser = $this->makeOrganiser();
        $startAt = now()->addDays(30)->setTime(19, 30);

        Http::fake([
            'https://api.groq.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'event_title' => 'Summer Beats Rooftop Party',
                                'short_description' => 'Sunset DJ sessions with cocktails and skyline views.',
                                'full_description' => 'Dance into the evening with a vibrant rooftop crowd, curated DJ sets, and a polished social atmosphere built around the poster theme.',
                                'start_datetime' => $startAt->format('Y-m-d\TH:i'),
                                'end_datetime' => '',
                                'venue_name' => 'Skyline Terrace',
                                'city' => '',
                                'address' => 'Skyline Terrace, 99 River Lane, London EC1A 1BB',
                                'postcode' => '',
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this
            ->withSession($this->organiserSession($organiser))
            ->post(route('organiser.events.poster.parse'), [
                'poster' => UploadedFile::fake()->image('summer-beats-rooftop-party.png', 1200, 1600),
            ]);

        $response->assertOk();

        $payload = $response->json();

        $this->assertSame([
            'event_title',
            'short_description',
            'full_description',
            'start_datetime',
            'end_datetime',
            'venue_name',
            'city',
            'address',
            'postcode',
        ], array_keys($payload));
        $this->assertSame('Summer Beats Rooftop Party', $payload['event_title']);
        $this->assertSame($startAt->format('Y-m-d\TH:i'), $payload['start_datetime']);
        $this->assertSame($startAt->copy()->addHours(3)->format('Y-m-d\TH:i'), $payload['end_datetime']);
        $this->assertSame('Skyline Terrace', $payload['venue_name']);
        $this->assertSame('London', $payload['city']);
        $this->assertSame('Skyline Terrace, 99 River Lane, London EC1A 1BB', $payload['address']);
        $this->assertSame('EC1A 1BB', $payload['postcode']);
    }

    public function test_parser_falls_back_to_generated_values_when_ai_response_is_invalid(): void
    {
        $organiser = $this->makeOrganiser();

        Http::fake([
            'https://api.groq.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'not valid json',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this
            ->withSession($this->organiserSession($organiser))
            ->post(route('organiser.events.poster.parse'), [
                'poster' => UploadedFile::fake()->image('kirtan-poster.png', 1200, 1600),
            ]);

        $response->assertOk();

        $payload = $response->json();
        $startAt = Carbon::createFromFormat('Y-m-d\TH:i', $payload['start_datetime']);
        $endAt = Carbon::createFromFormat('Y-m-d\TH:i', $payload['end_datetime']);

        $this->assertSame('Ahmedabad', $payload['city']);
        $this->assertSame('Riverfront Arena', $payload['venue_name']);
        $this->assertSame('380001', $payload['postcode']);
        $this->assertNotSame('', $payload['event_title']);
        $this->assertNotSame('', $payload['short_description']);
        $this->assertNotSame('', $payload['full_description']);
        $this->assertTrue($startAt->greaterThan(now()));
        $this->assertTrue($endAt->greaterThan($startAt));
    }

    public function test_poster_parse_endpoint_requires_organiser_authentication_for_json_requests(): void
    {
        $response = $this
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('organiser.events.poster.parse'), [
                'poster' => UploadedFile::fake()->image('poster.png', 1200, 1600),
            ]);

        $response->assertStatus(401);
        $response->assertJson([
            'error' => 'Unauthenticated.',
        ]);
    }

    private function makeOrganiser(array $overrides = []): Organiser
    {
        return Organiser::create(array_merge([
            'name' => 'Poster Organiser',
            'company_name' => 'Poster Co',
            'email' => 'poster.organiser@example.com',
            'password' => Hash::make('password123'),
            'phone' => '07123456789',
            'is_approved' => true,
        ], $overrides));
    }

    private function organiserSession(Organiser $organiser): array
    {
        return [
            'organiser_id' => $organiser->id,
            'organiser_last_active' => now()->timestamp,
        ];
    }
}
