<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        $gender = fake()->randomElement(['male', 'female']);

        return [
            'id' => (string) Str::uuid(),
            'role' => fake()->randomElement(['teacher', 'student']),
            'username' => fake()->unique()->userName(),
            'name' => fake()->name($gender === 'male' ? 'male' : 'female'),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'gender' => $gender,
            'birth_date' => fake()->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'country' => 'Saudi Arabia',
            'city' => 'Riyadh',
            'residence' => fake()->optional()->address(),
            'phone' => '500000000',
            'phone_zone' => '+966',
            'whatsapp_phone' => null,
            'whatsapp_zone' => null,
            'status' => 'active',
            'email_verified_at' => now(),
            'last_login_at' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function teacher(): static
    {
        return $this->state(fn (): array => ['role' => 'teacher']);
    }

    public function student(): static
    {
        return $this->state(fn (): array => ['role' => 'student']);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['status' => 'inactive']);
    }

    public function suspended(): static
    {
        return $this->state(fn (): array => ['status' => 'suspended']);
    }

    public function unverified(): static
    {
        return $this->state(fn (): array => ['email_verified_at' => null]);
    }
}
