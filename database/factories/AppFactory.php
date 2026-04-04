<?php

namespace Database\Factories;

use App\Models\App;
use App\Models\AppCategory;
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
            'public' => false,
            'pinned' => false,
            'skip_consent' => false,
            'approved' => true,
            'first_party' => true,
            'priority' => $this->faker->numberBetween(1, 1000),
            'url' => $this->faker->url(),
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

    public function public(): static
    {
        return $this->state(fn () => ['public' => true]);
    }

    public function pinned(): static
    {
        return $this->state(fn () => ['pinned' => true]);
    }

    public function skipConsent(): static
    {
        return $this->state(fn () => ['skip_consent' => true]);
    }

    public function unapproved(): static
    {
        return $this->state(fn () => ['approved' => false]);
    }

    public function firstParty(): static
    {
        return $this->state(fn () => ['first_party' => true]);
    }

    public function thirdParty(): static
    {
        return $this->state(fn () => [
            'first_party' => false,
            'developer_name' => $this->faker->company(),
            'privacy_policy_url' => $this->faker->url(),
            'terms_of_service_url' => $this->faker->url(),
        ]);
    }

    public function withCategory(?AppCategory $category = null): static
    {
        return $this->state(fn () => [
            'category_id' => $category?->id ?? AppCategory::factory(),
        ]);
    }
}
