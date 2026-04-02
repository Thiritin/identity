<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OauthSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'session_id' => Str::uuid()->toString(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'last_client_id' => 'portal',
            'authenticated_at' => now(),
            'last_seen_at' => now(),
        ];
    }
}
