<?php

namespace App\Repositories\Report;

use App\Models\User;
use App\Models\Admin;
use App\Models\Appointment;
use App\Core\Notifications\DatabaseNotification;
use App\Core\Abstracts\Repository\BaseRepository;
use App\Repositories\Report\ReportRepositoryContract;

class ReportRepository extends BaseRepository implements ReportRepositoryContract
{
    public function create(array $params)
    {
        \DB::beginTransaction();

        try {
            $user = request()->user();
            $id   = $params['reference_id'];
            $type = $params['type'];

            $report        = null;
            $reportable    = null;
            $reportType    = null;
            $reportableType = null;
            $reportName    = '';
            $designation   = '';
            $serviceType   = '';

            if ($type === 'profile') {
                // Reporting a user profile
                $report        = User::findOrFail($id);
                $reportable    = $report;
                $reportType    = User::class;
                $reportableType = 'user';
                $reportName    = trim("{$report->first_name} {$report->last_name}");
                $designation   = $report->role === 'doctor' ? 'Dr' : '';
                $serviceType   = "{$report->role}_profile";
            } else {
                // Reporting an appointment
                $report = Appointment::where('id', $id)
                    // ->where('user_id', $user->id)
                    ->first();
                if (!$report) {
                    throw new \Exception('Appointment not found');
                }

                if ($report->service_type === 'doctor') {
                    $reportable = User::findOrFail($report->bookable_id);
                } else {
                    $reportable = User::findOrFail($report->provider);
                }

                $reportType    = Appointment::class;
                $reportableType = 'appointment';
                $reportName    = trim("{$reportable->first_name} {$reportable->last_name}");
                $designation   = $reportable->role === 'doctor' ? 'Dr' : 'Healthcare Professional';

                $serviceTypeMap = [
                    'lab_custom' => 'lab_service',
                    'lab_bundle' => 'lab_service',
                    'lab'        => 'lab_service',
                ];
                $serviceType = $serviceTypeMap[$report->service_type] ?? "{$report->service_type}_service";
            }

            // Prevent duplicate reports
            $alreadyReported = $this->model->where([
                'reportable_id'   => $report->id,
                'reportable_type' => $reportType,
                'user_id'         => $user->id,
            ])->exists();

            if ($alreadyReported) {
                throw new \Exception("You have already reported this. Please wait for resolution.");
            }

            // Create the report
            $reportModel = $this->model->create([
                'user_id'         => $user->id,
                'reportable_id'   => $report->id,
                'reportable_type' => $reportType,
                'service_type'    => $serviceType,
                'reason'          => $params['reason'],
                'status'          => 'pending',
            ]);

            // -------------------------
            // Notifications
            // -------------------------

            // Notify reported user (profile or appointment)
            $title = 'You have been reported';
            $body  = $reportableType === 'user'
                ? "Your profile has been reported by patient {$user->first_name} {$user->last_name}."
                : "Your {$type} has been reported by patient {$user->first_name} {$user->last_name}.";

            $reportable->notify(new DatabaseNotification([
                'title' => $title,
                'body'  => $body,
                'id'    => $reportModel->id,
                'data'  => [
                    'route' => [
                        'name'   => 'reports.show',
                        'params' => ['id' => $reportModel->id],
                    ],
                ],
            ]));

            // Notify the first active admin
            if ($admin = Admin::where('status', '1')->first()) {
                $admin->notify(new \App\Notifications\GenericNotification([
                    'title' => 'New Report Submitted',
                    'body'  => "Patient {$user->first_name} {$user->last_name} reported {$designation} {$reportName} ({$type}).",
                    'id'    => $reportModel->id,
                    'data'  => [
                        'route' => [
                            'name'   => 'admin.reports.show',
                            'params' => ['id' => $reportModel->id],
                        ],
                    ],
                ]));
            }

            \DB::commit();
            return $reportModel;
        } catch (\Throwable $th) {
            \DB::rollBack();
            throw $th;
        }
    }
}
