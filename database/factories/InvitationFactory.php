<?php

namespace Database\Factories;

use App\Enums\LabelRole;
use App\Models\Invitation;
use App\Models\Label;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invitation>
 */
class InvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label_id' => Label::factory(),
            'email' => fake()->unique()->safeEmail(),
            'role' => LabelRole::Viewer,
            'token_hash' => Invitation::hashToken(Str::random(64)),
            'expires_at' => now()->addDays(Invitation::ValidityInDays),
            'accepted_at' => null,
        ];
    }

    /**
     * Use the given plain token for the invitation link.
     */
    public function withToken(string $token): static
    {
        return $this->state(fn (array $attributes) => [
            'token_hash' => Invitation::hashToken($token),
        ]);
    }

    /**
     * Indicate that the invitation has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subMinute(),
        ]);
    }

    /**
     * Indicate that the invitation has already been accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'accepted_at' => now(),
        ]);
    }
}
