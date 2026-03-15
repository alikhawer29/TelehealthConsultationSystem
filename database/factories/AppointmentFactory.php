<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $appointmentDate = fake()->dateTimeBetween('now', '+30 days');
        $startTime = fake()->time('H:i:s');
        $endTime = Carbon::parse($startTime)->addHour()->format('H:i:s');

        return [
            'booking_id' => fake()->unique()->numerify('BK######'),
            'user_id' => User::factory(),
            'service_type' => fake()->randomElement(['doctor', 'service', 'lab']),
            'session_type' => fake()->randomElement(['online', 'offline']),
            'bookable_type' => 'App\Models\User',
            'bookable_id' => User::factory(),
            'status' => 'scheduled',
            'appointment_status' => 'upcoming',
            'request_date' => $appointmentDate->format('Y-m-d'),
            'request_start_time' => $startTime,
            'request_end_time' => $endTime,
            'appointment_date' => $appointmentDate->format('Y-m-d'),
            'appointment_start_time' => $startTime,
            'appointment_end_time' => $endTime,
            'session_code' => fake()->unique()->numerify('SC######'),
            'is_live' => false,
            'amount' => fake()->randomFloat(2, 50, 500),
            'payment_status' => 'pending',
            'provider' => null,
            'reason' => fake()->sentence(),
            'report_status' => 'pending',
            'notes' => fake()->paragraph(),
            'payment_type' => fake()->randomElement(['card', 'cash', 'insurance']),
        ];
    }

    /**
     * Indicate that the appointment has a specific provider.
     */
    public function withProvider($providerId = null)
    {
        return $this->state(function (array $attributes) use ($providerId) {
            return [
                'provider' => $providerId ?? User::factory()->create(['role' => 'nurse'])->id,
            ];
        });
    }

    /**
     * Indicate that the appointment is for a specific patient.
     */
    public function forPatient($patientId)
    {
        return $this->state(function (array $attributes) use ($patientId) {
            return [
                'user_id' => $patientId,
            ];
        });
    }

    /**
     * Indicate that the appointment is with a specific doctor.
     */
    public function withDoctor($doctorId)
    {
        return $this->state(function (array $attributes) use ($doctorId) {
            return [
                'bookable_type' => 'App\Models\User',
                'bookable_id' => $doctorId,
            ];
        });
    }
}
