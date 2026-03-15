<?php

namespace Database\Factories;

use App\Models\ReminderSetting;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReminderSetting>
 */
class ReminderSettingFactory extends Factory
{
    protected $model = ReminderSetting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $userType = fake()->randomElement(['user', 'doctor', 'nurse', 'physician', 'admin']);
        $reminderTime = fake()->randomElement(['5_min', '10_min', '15_min', '30_min', '1_hour', '1_day', 'custom']);
        
        // Generate reference_id based on user_type
        $referenceId = $userType === 'admin' 
            ? Admin::factory()->create()->id 
            : User::factory()->create(['role' => $userType])->id;

        return [
            'user_type' => $userType,
            'reminder_time' => $reminderTime,
            'custom_time' => $reminderTime === 'custom' ? fake()->numberBetween(1, 120) : null,
            'reference_id' => $referenceId,
        ];
    }

    /**
     * Indicate that the reminder setting is for a specific user type.
     */
    public function forUserType($userType)
    {
        return $this->state(function (array $attributes) use ($userType) {
            $referenceId = $userType === 'admin' 
                ? Admin::factory()->create()->id 
                : User::factory()->create(['role' => $userType])->id;

            return [
                'user_type' => $userType,
                'reference_id' => $referenceId,
            ];
        });
    }

    /**
     * Indicate that the reminder setting is for a specific reference ID.
     */
    public function forReference($referenceId)
    {
        return $this->state(function (array $attributes) use ($referenceId) {
            return [
                'reference_id' => $referenceId,
            ];
        });
    }

    /**
     * Indicate that the reminder setting uses custom time.
     */
    public function withCustomTime($minutes = null)
    {
        return $this->state(function (array $attributes) use ($minutes) {
            return [
                'reminder_time' => 'custom',
                'custom_time' => $minutes ?? fake()->numberBetween(1, 120),
            ];
        });
    }
}
