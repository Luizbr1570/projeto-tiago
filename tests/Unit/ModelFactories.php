<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id'    => Company::factory(),
            'phone'         => fake()->phoneNumber(),
            'city'          => fake()->city(),
            'status'        => fake()->randomElement(['novo', 'em_conversa', 'pediu_preco', 'encaminhado', 'perdido', 'recuperacao']),
            'source'        => fake()->randomElement(['WhatsApp', 'Instagram', 'Google']),
            'first_contact' => now(),
        ];
    }
}

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name'       => fake()->words(3, true),
            'category'   => fake()->word(),
            'avg_price'  => fake()->randomFloat(2, 100, 10000),
        ];
    }
}

class ConversationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id'    => Company::factory(),
            'lead_id'       => \App\Models\Lead::factory(),
            'sender'        => fake()->randomElement(['lead', 'bot', 'human']),
            'message'       => fake()->sentence(),
            'response_time' => fake()->numberBetween(100, 5000),
        ];
    }
}

class FollowupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'lead_id'    => \App\Models\Lead::factory(),
            'status'     => 'pending',
            'recovered'  => false,
            'sent_at'    => null,
        ];
    }
}

class ChatSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id'           => Company::factory(),
            'lead_id'              => \App\Models\Lead::factory(),
            'started_at'           => now(),
            'ended_at'             => null,
            'transferred_to_human' => false,
        ];
    }
}
