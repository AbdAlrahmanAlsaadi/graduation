<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'internal_id' => fake()->unique()->bothify('INT-####'),
            'status' => 'active',
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function withRole(string $roleName): static
    {
        return $this->afterCreating(function ($user) use ($roleName) {
            $user->assignRole($roleName);
        });
    }

    public function companyAdmin(): static
    {
        return $this->withRole('company_admin');
    }

    public function projectManager(): static
    {
        return $this->withRole('project_manager');
    }

    public function assistantEngineer(): static
    {
        return $this->withRole('assistant');
    }

    public function owner(): static
    {
        return $this->withRole('project_owner');
    }
}
