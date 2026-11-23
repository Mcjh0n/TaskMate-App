<?php

namespace Database\Factories;

use App\Enums\PaymentEventType;
use App\Models\Agreement;
use App\Models\PaymentEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PaymentEvent> */
class PaymentEventFactory extends Factory
{
    protected $model = PaymentEvent::class;

    public function definition(): array
    {
        return [
            'agreement_id' => Agreement::factory(),
            'actor_id' => User::factory()->verified(),
            'event' => PaymentEventType::MarkedPaid,
            'amount' => fake()->randomFloat(2, 100, 10000),
            'paid_on' => today()->toDateString(),
            'method' => 'Synthetic direct payment method',
            'note' => 'Synthetic payment event.',
            'created_at' => now(),
        ];
    }
}
