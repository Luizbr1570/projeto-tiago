<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'slug' => Str::slug(fake()->unique()->company() . '-' . fake()->unique()->numberBetween(100, 999)),
            'plan' => fake()->randomElement(['free', 'pro']),
            'active' => true,
        ];
    }
}
