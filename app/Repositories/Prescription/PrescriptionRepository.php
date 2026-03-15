<?php

namespace App\Repositories\Prescription;

use App\Models\Prescription;
use App\Core\Abstracts\Filters;
use Illuminate\Database\Eloquent\Model;
use App\Core\Abstracts\Repository\BaseRepository;

class PrescriptionRepository extends BaseRepository implements PrescriptionRepositoryContract
{
    protected $model;

    public function setModel(Model $model)
    {
        $this->model = $model;
    }

    public function create(array $params, $role = null): Model
    {
        $user = request()->user();

        // If role is "patient", set patient_id to logged-in user
        $patientId = ($role === 'patient') ? $user->id : ($params['patient_id'] ?? null);

        return $this->model->create([
            'doctor_id'      => $params['doctor_id']      ?? null,
            'patient_id'     => $patientId,
            'appointment_id' => $params['appointment_id'] ?? null,
            'medication'     => $params['medication']     ?? null,
            'dosage'         => $params['dosage']         ?? null,
            'status'         => $params['status']         ?? true,
            'role'           => $params['role']           ?? null,
            'type'           => $params['type']           ?? null,
            'file_name'      => $params['file_name']      ?? null,
            'created_by'     => $user->id,
        ]);
    }


    public function updatePrescription($id, array $params): Model
    {
        $prescription = $this->model->findOrFail($id);

        $prescription->update([
            'medication' => $params['medication'] ?? $prescription->medication,
            'dosage' => $params['dosage'] ?? $prescription->dosage,
            'role' => $params['role'] ?? $prescription->role,
            'status' => $params['status'] ?? $prescription->status,
            'type'  => $params['type'] ?? $prescription->type,
            'file_name'          => $params['file_name']          ?? $prescription->file_name,
        ]);

        return $prescription;
    }

    public function status($id): bool
    {
        $prescription = $this->model->findOrFail($id);
        $prescription->status = !$prescription->status;
        $prescription->save();

        return $prescription->status;
    }
}
