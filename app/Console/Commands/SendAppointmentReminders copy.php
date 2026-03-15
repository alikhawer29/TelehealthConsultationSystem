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

        // Get all users(admin/nurse/physician/doctor) reminder settings
        $settings = ReminderSetting::all();

        foreach ($settings as $setting) {
            $user = $this->getUserFromSetting($setting);

            if (!$user) {
                Log::warning("⚠️ No user found for setting ID {$setting->id} (Type: {$setting->user_type}, Ref: {$setting->reference_id})");
                continue;
            }

            // Get appointments based on user type and MATCH the user
            $appointments = $this->getAppointmentsForUser($user, $setting, $now);

            foreach ($appointments as $appointment) {
                // Validate that this user should receive notifications for this appointment
                if (!$this->shouldUserReceiveNotificationForAppointment($user, $appointment, $setting)) {
                    Log::warning("⚠️ User {$user->role} (ID: {$user->id}) should not receive notification for appointment ID {$appointment->id}");
                    continue;
                }

                $reminderTime = $this->calculateReminderTime($appointment, $setting);

                Log::info("🔔 Calculated reminder time: {$reminderTime?->format('Y-m-d H:i:s')} for appointment ID {$appointment->id}");
                Log::info("🔔 Current time: {$now->format('Y-m-d H:i:s')}");

                if ($reminderTime && $this->isTimeToSendReminder($reminderTime, $now)) {
                    Log::info("🔔 Sending reminder to {$user->role} (ID: {$user->id}) for appointment ID {$appointment->id}");

                    $this->sendNotification($user, $appointment, $setting);
                } else {
                    Log::debug("⏳ Not yet time for reminder to {$user->role} (ID: {$user->id}) - Reminder scheduled at: " . ($reminderTime?->toDateTimeString() ?? 'N/A'));
                }
            }
        }

        $this->info('Reminders checked and sent if due.');
        return 0;
    }

    /**
     * Get appointments for the specific user based on their role
     */
    private function getAppointmentsForUser($user, $setting, $now)
    {
        $baseQuery = Appointment::where('appointment_status', 'upcoming')
            ->where('status', 'scheduled');

        // For admin: get ALL appointments
        if ($user->role === 'admin' || $setting->user_type === 'admin') {
            Log::debug("🔍 Admin: Getting all appointments");
            return $baseQuery->get();
        }

        // For doctor (doctor): get appointments where they are the bookable
        if ($user->role === 'doctor') {
            Log::debug("🔍 Doctor: {$user->role}: Getting appointments where user is bookable");
            return $baseQuery->where('bookable_type', 'App\Models\User')
                ->where('bookable_id', $user->id)
                ->get();
        }

        // For healthcare professionals (nurse/physician): get appointments where they are the service provider
        if (in_array($user->role, ['nurse', 'physician'])) {
            Log::debug("🔍 Healthcare professional {$user->role}: Getting appointments where user is service provider");
            return $baseQuery->where('provider', $user->id)
                ->get();
        }

        // For regular users/patients: get their own appointments
        if ($user->role === 'user') {
            Log::debug("🔍 Patient: Getting user's own appointments");
            return $baseQuery->where('user_id', $user->id)->get();
        }

        Log::warning("❓ Unknown user role: {$user->role}");
        return collect();
    }

    /**
     * Check if it's time to send the reminder (with 1-minute tolerance)
     */
    private function isTimeToSendReminder($reminderTime, $now)
    {
        return $reminderTime->diffInMinutes($now) <= 1;
    }

    /**
     * Validate that the user should receive notifications for this specific appointment
     */
    private function shouldUserReceiveNotificationForAppointment($user, $appointment, $setting)
    {
        // For admin: they can receive notifications for all appointments
        if ($user->role === 'admin' || $setting->user_type === 'admin') {
            Log::debug("✅ Admin user can receive notifications for all appointments");
            return true;
        }

        // For doctors: they should only get notifications for appointments where they are the bookable
        if ($user->role === 'doctor') {
            $isBookable = $appointment->bookable_type === 'App\Models\User' && $appointment->bookable_id === $user->id;
            Log::debug("🔍 Doctor validation: User {$user->id} is bookable for appointment {$appointment->id}: " . ($isBookable ? 'YES' : 'NO'));
            return $isBookable;
        }

        // For healthcare professionals (nurse/physician): they should only get notifications for appointments where they are the service provider
        if (in_array($user->role, ['nurse', 'physician'])) {
            $isProvider = $appointment->provider === $user->id;
            Log::debug("🔍 Healthcare professional validation: User {$user->id} is provider for appointment {$appointment->id}: " . ($isProvider ? 'YES' : 'NO'));
            return $isProvider;
        }

        // For patients: they should only get notifications for their own appointments or family member appointments
        if ($user->role === 'user') {
            $isOwnAppointment = $appointment->user_id === $user->id;
            Log::debug("🔍 Patient validation: User {$user->id} owns appointment {$appointment->id}: " . ($isOwnAppointment ? 'YES' : 'NO'));
            return $isOwnAppointment;
        }

        Log::warning("❓ Unknown user role for validation: {$user->role}");
        return false;
    }

    private function getUserFromSetting($setting)
    {
        Log::debug("👤 Looking up user for setting: Type: {$setting->user_type}, Ref: {$setting->reference_id}");

        if ($setting->user_type === 'admin') {
            // Use the reference_id to find the specific admin, not hardcoded ID 1
            $admin = Admin::find($setting->reference_id);
            Log::debug("👤 Admin lookup: " . ($admin ? "Found admin ID {$admin->id}" : "Not found with reference_id {$setting->reference_id}"));

            if (!$admin) {
                Log::warning("⚠️ Admin not found with reference_id: {$setting->reference_id}");
            }

            return $admin;
        }

        // For user/nurse/physician/doctor - find in users table
        $user = User::where('id', $setting->reference_id)->first();

        if ($user) {
            Log::debug("👤 User lookup: Found user ID {$user->id} with role {$user->role}");

            // Verify the user role matches the setting type
            if ($user->role !== $setting->user_type) {
                Log::warning("⚠️ User role mismatch: Setting expects {$setting->user_type}, but user has role {$user->role}");
                // Return null for mismatched roles to prevent wrong notifications
                return null;
            }
        } else {
            Log::warning("⚠️ User not found with ID: {$setting->reference_id}");
        }

        return $user;
    }

    private function calculateReminderTime($appointment, $setting)
    {
        $appointmentTime = Carbon::parse("{$appointment->appointment_date} {$appointment->appointment_start_time}");

        $reminderTime = match ($setting->reminder_time) {
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

        Log::debug("⏰ Appointment: {$appointmentTime}, Reminder setting: {$setting->reminder_time}, Calculated: " . ($reminderTime?->format('Y-m-d H:i:s') ?? 'null'));

        return $reminderTime;
    }

    private function sendNotification($user, $appointment, $setting)
    {
        Log::info("📧 Starting notification process for user {$user->id} ({$user->role}) for appointment {$appointment->id}");

        // Log appointment details for debugging
        Log::debug("📋 Appointment details: ID={$appointment->id}, Patient={$appointment->user_id}, Provider={$appointment->provider}, Bookable={$appointment->bookable_id}, Date={$appointment->appointment_date}, Time={$appointment->appointment_start_time}");

        // Get patient name
        $patientName = $appointment->family_member->name
            ?? trim(($appointment->user->first_name ?? '') . ' ' . ($appointment->user->last_name ?? ''));

        Log::debug("👤 Patient name resolved: {$patientName}");

        // Get doctor/service provider name
        if (!empty($appointment->service_provider)) {
            $doctorName  = trim(($appointment->service_provider->first_name ?? '') . ' ' . ($appointment->service_provider->last_name ?? ''));
            $designation = ucfirst($appointment->service_provider->role ?? '');
            Log::debug("👨‍⚕️ Doctor/Provider from service_provider: {$doctorName} ({$designation})");
        } else {
            $doctorName  = trim(($appointment->bookable->first_name ?? '') . ' ' . ($appointment->bookable->last_name ?? ''));
            $designation = ucfirst($appointment->bookable->role ?? '');
            Log::debug("👨‍⚕️ Doctor/Provider from bookable: {$doctorName} ({$designation})");
        }

        $appointmentTime = $appointment->appointment_start_time;

        // Customize message based on user role
        $notificationData = $this->getNotificationContent($user, $appointment, $patientName, $doctorName, $designation, $appointmentTime, $setting);

        Log::debug("📝 Notification content: Title='{$notificationData['title']}', Body='{$notificationData['body']}'");

        $user->notify(new \App\Notifications\GenericNotification([
            'title' => $notificationData['title'],
            'body'  => $notificationData['body'],
            'sound' => 'customSound',
            'id'    => $appointment->id,
            'data'  => [
                'route' => [
                    'name'   => 'appointment.details',
                    'params' => ['id' => $appointment->id],
                ],
                'reminder_type' => $setting->reminder_time,
            ],
        ]));

        Log::info("✅ Notification successfully sent to {$user->role} (ID: {$user->id}) for appointment {$appointment->id}");
        Log::info("📊 Notification Summary: User={$user->id}({$user->role}), Patient={$patientName}, Doctor={$doctorName}, Time={$appointmentTime}, ReminderType={$setting->reminder_time}");
    }

    /**
     * Generate appropriate notification content based on user role
     */
    private function getNotificationContent($user, $appointment, $patientName, $doctorName, $designation, $appointmentTime, $setting)
    {
        $reminderType = $this->getReminderTypeText($setting);

        switch ($user->role) {
            case 'admin':
                return [
                    'title' => 'Appointment Reminder',
                    'body'  => "Appointment reminder: Patient {$patientName} has an appointment with {$doctorName} at {$appointmentTime}. Reminder: {$reminderType}"
                ];

            case 'doctor':
                return [
                    'title' => 'Appointment Reminder',
                    'body'  => "Dear Dr. {$doctorName}, you have an appointment with Patient {$patientName} scheduled at {$appointmentTime}. Reminder: {$reminderType}"
                ];
            case 'nurse':
            case 'physician':
                return [
                    'title' => 'Appointment Reminder',
                    'body'  => "Dear Healthcare Professional. {$doctorName}, you have an appointment with Patient {$patientName} scheduled at {$appointmentTime}. Reminder: {$reminderType}"
                ];

            case 'user':
            default:
                return [
                    'title' => 'Appointment Reminder',
                    'body'  => "Dear Patient {$patientName}, you have an appointment with {$doctorName} scheduled at {$appointmentTime}. Reminder: {$reminderType}"
                ];
        }
    }

    /**
     * Get human-readable reminder type
     */
    private function getReminderTypeText($setting)
    {
        return match ($setting->reminder_time) {
            'at_time'   => 'At appointment time',
            '5_min'     => '5 minutes before',
            '10_min'    => '10 minutes before',
            '15_min'    => '15 minutes before',
            '30_min'    => '30 minutes before',
            '1_hour'    => '1 hour before',
            '1_day'     => '1 day before',
            'custom'    => "{$setting->custom_time} minutes before",
            default     => 'Reminder'
        };
    }
}
