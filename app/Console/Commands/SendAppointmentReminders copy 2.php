<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Admin;
use App\Models\Appointment;
use Illuminate\Support\Carbon;
use App\Models\ReminderSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;



class SendAppointmentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminder:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send appointment reminders based on reminder settings';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $now = Carbon::now();
        Log::info('⏰ Reminder Check Cron Ran At: ' . $now->toDateTimeString());
        $this->info('⏰ Reminder Check Cron Ran At: ' . $now->toDateTimeString());

        $settings = ReminderSetting::all();

        foreach ($settings as $setting) {
            $user = $this->getUserFromSetting($setting);

            if (!$user) {
                Log::warning("⚠️ No user found for setting ID {$setting->id}");
                continue;
            }

            $appointments = Appointment::where('appointment_status', 'upcoming')
                ->where('status', 'scheduled')
                ->whereDate('appointment_date', $now->toDateString())
                ->get();

            foreach ($appointments as $appointment) {
                $reminderTime = $this->calculateReminderTime($appointment, $setting);
                Log::info("🔔 Reminder time {$reminderTime} , for appointment ID {$appointment->id}");
                if ($reminderTime && $now->format('Y-m-d H:i') === $reminderTime->format('Y-m-d H:i')) {
                    Log::info("🔔 Reminder sent to {$user->role} (ID: {$user->id}) for appointment ID {$appointment->id} at {$reminderTime->toDateTimeString()}");

                    $this->sendNotification($user, $appointment);
                } else {
                    Log::debug("⏳ Not yet time for reminder to {$user->role} (ID: {$user->id}) - Next at: " . ($reminderTime?->toDateTimeString() ?? 'N/A'));
                }
            }
        }

        $this->info('Reminders checked and sent if due.');
    }

    private function getUserFromSetting($setting)
    {
        if ($setting->user_type === 'admin') {
            return Admin::find(1);
        }

        return User::where('id', $setting->reference_id)
            ->where('role', $setting->user_type)
            ->first();
    }

    private function calculateReminderTime($appointment, $setting)
    {
        $appointmentTime = Carbon::parse("{$appointment->appointment_date} {$appointment->appointment_start_time}");

        return match ($setting->reminder_time) {
            'at_time'   => $appointmentTime,
            '5_min'     => $appointmentTime->copy()->subMinutes(5),
            '10_min'    => $appointmentTime->copy()->subMinutes(10),
            '15_min'    => $appointmentTime->copy()->subMinutes(15),
            '30_min'    => $appointmentTime->copy()->subMinutes(30),
            '1_hour'    => $appointmentTime->copy()->subHour(),
            '1_day'     => $appointmentTime->copy()->subDay(),
            'custom'    => $appointmentTime->copy()->subMinutes($setting->custom_time),
            default     => null
        };
    }

    private function sendNotification($user, $appointment)
    {
        // 🟢 Greeting based on role
        $roleGreeting = match ($user->role) {
            'user', 'patient'     => 'Dear Patient',
            'doctor'              => 'Dear Doctor',
            'nurse', 'physician'  => 'Dear Health Care Professional',
            'admin'               => 'Dear Admin',
            default               => 'Dear User',
        };

        // 🟢 Names
        $patientName = $appointment->family_member->name
            ?? trim(($appointment->user->first_name ?? '') . ' ' . ($appointment->user->last_name ?? ''));

        if (!empty($appointment->service_provider)) {
            $doctorName  = trim(($appointment->service_provider->first_name ?? '') . ' ' . ($appointment->service_provider->last_name ?? ''));
            $designation = ucfirst($appointment->service_provider->role ?? '');
        } else {
            $doctorName  = trim(($appointment->bookable->first_name ?? '') . ' ' . ($appointment->bookable->last_name ?? ''));
            $designation = ucfirst($appointment->bookable->role ?? '');
        }

        // 🟢 Appointment time
        $appointmentTime = $appointment->appointment_start_time;

        $providerText = '';
        if (!empty($doctorName) || !empty($designation)) {
            $providerText = " with {$doctorName} {$designation}";
        }

        $usernameText = $user->role === 'user' ? $patientName : (($user->role === 'doctor' || $user->role === 'nurse' || $user->role === 'physician') ? $doctorName : '');


        // 🟢 Final body
        $body = "{$roleGreeting} {$usernameText}, you have an upcoming appointment{$providerText} scheduled at {$appointmentTime}.";


        // 🟢 Send notification
        $user->notify(new \App\Notifications\GenericNotification([
            'title' => 'Appointment Reminder',
            'body'  => $body,
            'sound' => 'customSound',
            'id'    => $appointment->id,
            'data'  => [
                'route' => [
                    'name'   => 'appointment.details',
                    'params' => ['id' => $appointment->id],
                ],
            ],
        ]));
    }
}
