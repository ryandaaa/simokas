<?php

declare(strict_types=1);

namespace App\Domains\Notification\Database\factories;

use App\Domains\Identity\Models\User;
use App\Domains\Notification\Enums\NotificationType;
use App\Domains\Notification\Models\Notification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => NotificationType::JobTaken,
            'title' => 'Job inspeksi diambil',
            'body' => 'Seorang inspektor mengambil job inspeksi.',
            'link' => null,
            'dedupe_key' => 'test-'.$this->faker->unique()->uuid(),
            'data' => null,
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn (): array => ['read_at' => now()]);
    }

    public function ofType(NotificationType $type): static
    {
        return $this->state(fn (): array => ['type' => $type]);
    }

    public function forUser(User $user): static
    {
        return $this->for($user, 'user');
    }
}
