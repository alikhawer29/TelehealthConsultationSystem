<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Admin;
use App\Models\Appointment;
use App\Models\ReminderSetting;
use App\Console\Commands\SendAppointmentReminders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class SendAppointmentRemindersTest extends TestCase
{
    use RefreshDatabase;

    protected $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new SendAppointmentReminders();
        Notification::fake();
    }

    /** @test */
    public function it_finds_correct_admin_user_from_reminder_setting()
    {
        // Create specific admin
        $admin = Admin::factory()->create(['id' => 5]);

        // Create reminder setting with specific admin reference
        $setting = ReminderSetting::factory()->create([
            'user_type' => 'admin',
            'reference_id' => 5,
            'reminder_time' => '10_min'
        ]);

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('getUserFromSetting');
        $method->setAccessible(true);

        $result = $method->invoke($this->command, $setting);

        $this->assertNotNull($result);
        $this->assertEquals(5, $result->id);
        $this->assertEquals('admin', $result->role);
    }

    /** @test */
    public function it_returns_null_for_non_existent_admin()
    {
        // Create reminder setting with non-existent admin reference
        $setting = ReminderSetting::factory()->create([
            'user_type' => 'admin',
            'reference_id' => 999,
            'reminder_time' => '10_min'
        ]);

        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('getUserFromSetting');
        $method->setAccessible(true);

        $result = $method->invoke($this->command, $setting);

        $this->assertNull($result);
    }

    /** @test */
    public function it_validates_user_role_matches_setting_type()
    {
        // Create user with doctor role
        $user = User::factory()->create(['role' => 'doctor']);

        // Create reminder setting expecting nurse role (mismatch)
        $setting = ReminderSetting::factory()->create([
            'user_type' => 'nurse',
            'reference_id' => $user->id,
            'reminder_time' => '10_min'
        ]);

        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('getUserFromSetting');
        $method->setAccessible(true);

        $result = $method->invoke($this->command, $setting);

        // Should return null for role mismatch
        $this->assertNull($result);
    }

    /** @test */
    public function it_validates_doctor_should_receive_notification_for_their_appointment()
    {
        $doctor = User::factory()->create(['role' => 'doctor']);
        $patient = User::factory()->create(['role' => 'user']);

        $appointment = Appointment::factory()->create([
            'user_id' => $patient->id,
            'bookable_type' => 'App\Models\User',
            'bookable_id' => $doctor->id,
            'appointment_status' => 'upcoming',
            'status' => 'scheduled'
        ]);

        $setting = ReminderSetting::factory()->create([
            'user_type' => 'doctor',
            'reference_id' => $doctor->id
        ]);

        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('shouldUserReceiveNotificationForAppointment');
        $method->setAccessible(true);

        $result = $method->invoke($this->command, $doctor, $appointment, $setting);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_validates_doctor_should_not_receive_notification_for_other_doctors_appointment()
    {
        $doctor1 = User::factory()->create(['role' => 'doctor']);
        $doctor2 = User::factory()->create(['role' => 'doctor']);
        $patient = User::factory()->create(['role' => 'user']);

        $appointment = Appointment::factory()->create([
            'user_id' => $patient->id,
            'bookable_type' => 'App\Models\User',
            'bookable_id' => $doctor2->id, // Appointment is with doctor2
            'appointment_status' => 'upcoming',
            'status' => 'scheduled'
        ]);

        $setting = ReminderSetting::factory()->create([
            'user_type' => 'doctor',
            'reference_id' => $doctor1->id
        ]);

        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('shouldUserReceiveNotificationForAppointment');
        $method->setAccessible(true);

        $result = $method->invoke($this->command, $doctor1, $appointment, $setting);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_validates_patient_should_receive_notification_for_their_appointment()
    {
        $patient = User::factory()->create(['role' => 'user']);
        $doctor = User::factory()->create(['role' => 'doctor']);

        $appointment = Appointment::factory()->create([
            'user_id' => $patient->id,
            'bookable_type' => 'App\Models\User',
            'bookable_id' => $doctor->id,
            'appointment_status' => 'upcoming',
            'status' => 'scheduled'
        ]);

        $setting = ReminderSetting::factory()->create([
            'user_type' => 'user',
            'reference_id' => $patient->id
        ]);

        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('shouldUserReceiveNotificationForAppointment');
        $method->setAccessible(true);

        $result = $method->invoke($this->command, $patient, $appointment, $setting);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_validates_patient_should_not_receive_notification_for_other_patients_appointment()
    {
        $patient1 = User::factory()->create(['role' => 'user']);
        $patient2 = User::factory()->create(['role' => 'user']);
        $doctor = User::factory()->create(['role' => 'doctor']);

        $appointment = Appointment::factory()->create([
            'user_id' => $patient2->id, // Appointment belongs to patient2
            'bookable_type' => 'App\Models\User',
            'bookable_id' => $doctor->id,
            'appointment_status' => 'upcoming',
            'status' => 'scheduled'
        ]);

        $setting = ReminderSetting::factory()->create([
            'user_type' => 'user',
            'reference_id' => $patient1->id
        ]);

        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('shouldUserReceiveNotificationForAppointment');
        $method->setAccessible(true);

        $result = $method->invoke($this->command, $patient1, $appointment, $setting);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_validates_nurse_should_receive_notification_for_their_appointment()
    {
        $nurse = User::factory()->create(['role' => 'nurse']);
        $patient = User::factory()->create(['role' => 'user']);

        $appointment = Appointment::factory()->create([
            'user_id' => $patient->id,
            'provider' => $nurse->id, // Nurse is the provider
            'appointment_status' => 'upcoming',
            'status' => 'scheduled'
        ]);

        $setting = ReminderSetting::factory()->create([
            'user_type' => 'nurse',
            'reference_id' => $nurse->id
        ]);

        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('shouldUserReceiveNotificationForAppointment');
        $method->setAccessible(true);

        $result = $method->invoke($this->command, $nurse, $appointment, $setting);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_validates_admin_should_receive_notification_for_all_appointments()
    {
        $admin = Admin::factory()->create();
        $patient = User::factory()->create(['role' => 'user']);
        $doctor = User::factory()->create(['role' => 'doctor']);

        $appointment = Appointment::factory()->create([
            'user_id' => $patient->id,
            'bookable_type' => 'App\Models\User',
            'bookable_id' => $doctor->id,
            'appointment_status' => 'upcoming',
            'status' => 'scheduled'
        ]);

        $setting = ReminderSetting::factory()->create([
            'user_type' => 'admin',
            'reference_id' => $admin->id
        ]);

        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('shouldUserReceiveNotificationForAppointment');
        $method->setAccessible(true);

        $result = $method->invoke($this->command, $admin, $appointment, $setting);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_handles_scheduled_appointment_dates_correctly()
    {
        $appointment = Appointment::factory()->create([
            'status' => 'scheduled',
            'appointment_date' => '2025-01-15',
            'appointment_start_time' => '10:00:00',
            'request_date' => '2025-01-10',
            'request_start_time' => '09:00:00'
        ]);

        $setting = ReminderSetting::factory()->create(['reminder_time' => '30_min']);

        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('calculateReminderTime');
        $method->setAccessible(true);

        $result = $method->invoke($this->command, $appointment, $setting);

        // Should use appointment_date and appointment_start_time for scheduled appointments
        $expected = Carbon::parse('2025-01-15 10:00:00')->subMinutes(30);
        $this->assertEquals($expected->format('Y-m-d H:i:s'), $result->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_handles_request_appointment_dates_correctly()
    {
        $appointment = Appointment::factory()->create([
            'status' => 'request',
            'appointment_date' => '2025-01-15',
            'appointment_start_time' => '10:00:00',
            'request_date' => '2025-01-10',
            'request_start_time' => '09:00:00'
        ]);

        $setting = ReminderSetting::factory()->create(['reminder_time' => '30_min']);

        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('calculateReminderTime');
        $method->setAccessible(true);

        $result = $method->invoke($this->command, $appointment, $setting);

        // Should use request_date and request_start_time for request appointments
        $expected = Carbon::parse('2025-01-10 09:00:00')->subMinutes(30);
        $this->assertEquals($expected->format('Y-m-d H:i:s'), $result->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_validates_doctor_appointments_by_service_type()
    {
        $doctor = User::factory()->create(['role' => 'doctor']);
        $patient = User::factory()->create(['role' => 'user']);

        // Doctor appointment (service_type = 'doctor')
        $doctorAppointment = Appointment::factory()->create([
            'user_id' => $patient->id,
            'service_type' => 'doctor',
            'bookable_type' => 'App\Models\User',
            'bookable_id' => $doctor->id,
            'appointment_status' => 'upcoming',
            'status' => 'scheduled'
        ]);

        // Non-doctor appointment (service_type = 'lab')
        $labAppointment = Appointment::factory()->create([
            'user_id' => $patient->id,
            'service_type' => 'lab',
            'provider' => $doctor->id, // Doctor incorrectly set as provider for lab service
            'appointment_status' => 'upcoming',
            'status' => 'scheduled'
        ]);

        $setting = ReminderSetting::factory()->create([
            'user_type' => 'doctor',
            'reference_id' => $doctor->id
        ]);

        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('shouldUserReceiveNotificationForAppointment');
        $method->setAccessible(true);

        // Doctor should receive notification for doctor appointments
        $result1 = $method->invoke($this->command, $doctor, $doctorAppointment, $setting);
        $this->assertTrue($result1);

        // Doctor should NOT receive notification for non-doctor appointments
        $result2 = $method->invoke($this->command, $doctor, $labAppointment, $setting);
        $this->assertFalse($result2);
    }

    /** @test */
    public function it_validates_nurse_appointments_by_service_type()
    {
        $nurse = User::factory()->create(['role' => 'nurse']);
        $patient = User::factory()->create(['role' => 'user']);

        // Nurse appointment (service_type = 'lab', provider = nurse)
        $nurseAppointment = Appointment::factory()->create([
            'user_id' => $patient->id,
            'service_type' => 'lab',
            'provider' => $nurse->id,
            'appointment_status' => 'upcoming',
            'status' => 'scheduled'
        ]);

        // Doctor appointment (service_type = 'doctor')
        $doctorAppointment = Appointment::factory()->create([
            'user_id' => $patient->id,
            'service_type' => 'doctor',
            'bookable_type' => 'App\Models\User',
            'bookable_id' => 999, // Different doctor
            'appointment_status' => 'upcoming',
            'status' => 'scheduled'
        ]);

        $setting = ReminderSetting::factory()->create([
            'user_type' => 'nurse',
            'reference_id' => $nurse->id
        ]);

        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('shouldUserReceiveNotificationForAppointment');
        $method->setAccessible(true);

        // Nurse should receive notification for non-doctor appointments where they are provider
        $result1 = $method->invoke($this->command, $nurse, $nurseAppointment, $setting);
        $this->assertTrue($result1);

        // Nurse should NOT receive notification for doctor appointments
        $result2 = $method->invoke($this->command, $nurse, $doctorAppointment, $setting);
        $this->assertFalse($result2);
    }
}
