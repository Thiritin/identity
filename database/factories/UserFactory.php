<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->safeEmail,
            'email_verified_at' => $this->faker->dateTime(),
            'password' => Hash::make($this->faker->password),
        ];
    }

    public function admin(): static
    {
        return $this->afterMaking(fn (User $user) => $user->is_admin = true)
            ->afterCreating(fn (User $user) => $user->forceFill(['is_admin' => true])->save());
    }

    public function developer(): static
    {
        return $this->afterMaking(fn (User $user) => $user->is_developer = true)
            ->afterCreating(fn (User $user) => $user->forceFill(['is_developer' => true])->save());
    }

    public function suspended(): static
    {
        return $this->state(fn () => [
            'suspended_at' => now(),
        ]);
    }
}
