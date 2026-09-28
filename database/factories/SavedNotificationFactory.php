<?php

namespace Database\Factories;

use App\Models\GitAccount;
use App\Models\SavedNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedNotification>
 */
class SavedNotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $threadId = (string) fake()->unique()->numberBetween(1000, 99999999);

        return [
            'git_account_id' => GitAccount::factory(),
            'thread_id' => $threadId,
            'notification' => ['id' => $threadId, 'title' => fake()->sentence(), 'type' => 'Issue', 'unread' => false],
        ];
    }
}
