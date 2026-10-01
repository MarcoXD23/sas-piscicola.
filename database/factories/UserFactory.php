<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
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
            'username' => fake()->unique()->userName(),
            'document_number' => (string) fake()->unique()->numerify('10########'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => User::ROLE_WORKER,
        ];
    }

    /**
     * Indicate that the user is an owner / jefe de finca.
     */
    public function owner(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_OWNER,
        ]);
    }

    public function jefe(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_JEFE,
        ]);
    }

    public function jefeFinca(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_JEFE_FINCA,
        ]);
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_ADMIN,
        ]);
    }

    /**
     * Indicate that the user is a worker.
     */
    public function worker(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_WORKER,
        ]);
    }

    public function trabajador(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_TRABAJADOR,
        ]);
    }

    /**
     * Indicate that the user is a guard.
     */
    public function guard(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_GUARD,
        ]);
    }

    public function fijo(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_type' => User::TYPE_FIJO,
        ]);
    }

    public function temporal(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_type' => User::TYPE_TEMPORAL,
        ]);
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
}
