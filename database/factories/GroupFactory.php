<?php

namespace Database\Factories;

use App\Enums\GroupTypeEnum;
use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Group::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'logo' => $this->faker->text,
        ];
    }

    public function root(): static
    {
        return $this->state(fn () => [
            'type' => GroupTypeEnum::Root,
            'system_name' => 'board',
            'name' => 'Board of Directors',
        ]);
    }

    public function division(): static
    {
        return $this->state(fn () => [
            'type' => GroupTypeEnum::Division,
        ]);
    }

    public function department(): static
    {
        return $this->state(fn () => [
            'type' => GroupTypeEnum::Department,
        ]);
    }

    public function team(): static
    {
        return $this->state(fn () => [
            'type' => GroupTypeEnum::Team,
        ]);
    }
}
