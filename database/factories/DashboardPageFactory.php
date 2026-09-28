<?php

namespace Database\Factories;

use App\Models\DashboardPage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DashboardPage>
 */
class DashboardPageFactory extends Factory
{
    /**
     * Define the model's default state. Teams come from the CreateTeam action (there's no Team factory), so pass team_id.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(2, true));

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'is_default' => false,
            'position' => 1,
            'layout' => DashboardPage::emptyLayout(),
        ];
    }

    public function default(): static
    {
        return $this->state(['name' => 'Dashboard', 'slug' => 'dashboard', 'is_default' => true, 'position' => 0, 'layout' => null]);
    }
}
