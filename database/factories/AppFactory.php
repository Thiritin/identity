<?php

namespace Database\Factories;

use App\Models\App;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppFactory extends Factory
{
    protected $model = App::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->company(),
            'description' => $this->faker->sentence(),
            'data' => [
                'client_name' => $this->faker->company(),
                'redirect_uris' => ['https://example.com/callback'],
                'post_logout_redirect_uris' => [],
                'grant_types' => ['authorization_code', 'refresh_token'],
                'response_types' => ['code'],
                'token_endpoint_auth_method' => 'client_secret_post',
                'subject_type' => 'public',
                'scope' => ['openid'],
            ],
        ];
    }
}
