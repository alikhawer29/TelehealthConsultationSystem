<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Appointment;
use Illuminate\Support\Carbon;
use App\Repositories\Appointment\AppointmentRepository;

class MarkMissedAppointments extends Command
{
    protected $signature = 'appointments:mark-missed';
    protected $description = 'Mark appointments as missed if user did not join within 15 minutes after start time';

    public function handle()
    {
        $now = Carbon::now();

        $appointments = Appointment::with([
            'bookable.file',
            'familyMember',
            'serviceProvider.file',
            'serviceProvider',
            'address',
            'bundleServices.service.file',
            'reviews',
            'user',
            'providerUser',
            'attendedLogs'
        ])
            ->where('status', 'scheduled')
            ->where('appointment_status', 'inprogress')
            ->where('is_live', 1)
            ->whereDate('appointment_date', $now->toDateString())
            ->get();

        foreach ($appointments as $appointment) {
            $start = Carbon::parse("{$appointment->appointment_date} {$appointment->appointment_start_time}");
            $gracePeriod = $start->copy()->addMinutes(15);

            // if grace period passed AND no attendance log exists for the appointment user
            $userAttended = $appointment->attendedLogs()
                ->where('user_id', $appointment->user_id)
                ->exists();

            if ($now->greaterThanOrEqualTo($gracePeriod) && !$userAttended) {
                $appointment->update([
                    'status' => 'missed',
                    'appointment_status' => 'missed'
                ]);

                $repo = new AppointmentRepository();

                $user     = $appointment->user;       // Patient
                $doctor   = $appointment->bookable;   // Doctor
                $service  = $appointment->bookable_name;

                // Build names
                $patientName = $appointment->family_member?->name
                    ?? trim("{$user->first_name} {$user->last_name}");

                // Prefer service_provider if exists, otherwise fallback to bookable
                if (!empty($appointment->service_provider)) {
                    $doctorName  = trim(($appointment->service_provider->first_name ?? '') . ' ' . ($appointment->service_provider->last_name ?? ''));
                    $designation = ucfirst($appointment->service_provider->role ?? 'Doctor');
                } else {
                    $doctorName  = trim(($appointment->bookable->first_name ?? '') . ' ' . ($appointment->bookable->last_name ?? ''));
                    $designation = ucfirst($appointment->bookable->role ?? 'Doctor');
                }

                $repo->notification()->send(
                    $user,
                    title: 'Missed Appointment',
                    body: "Dear {$patientName}, you missed your appointment with {$doctorName} {$designation} scheduled at {$appointment->appointment_start_time}.",
                    data: [
                        'id'     => $appointment->id,
                        'type'   => $appointment->service_type,
                        'status' => 'missed',
                        'route'  => json_encode([
                            'name'   => 'appointment.details',
                            'params' => ['id' => $appointment->id],
                        ]),
                    ]
                );


                \Log::info("Appointment ID {$appointment->id} marked as missed.");
                $this->info("Appointment ID {$appointment->id} marked as missed.");
            }
        }

        return Command::SUCCESS;
    }
}
