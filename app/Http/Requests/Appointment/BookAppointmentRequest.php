<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class BookAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'appointment_date' => 'required|date|date_format:Y-m-d',

            // Ensure `family_members` is an array
            // 'family_member_id' => 'required|exists:family_member,id',
            'family_member_id' => 'nullable',

            'service_type' => 'required|in:homecare,lab,doctor,lab_bundle,lab_custom,iv_drip,nursing_care,iv_drip_custom,custom',
            'session_type' => 'required_if:service_type,doctor|in:chat,call,video_call',

            // doctor_id is required only if service_type is doctor
            'doctor_id' => 'required_if:service_type,doctor|nullable|exists:users,id',

            'service_id' => 'required_if:service_type,homecare,lab,lab_custom,iv_drip,nursing_care,iv_drip_custom,custom|nullable',

            'slot_id' => 'required|exists:slots,id',

            'address_id' => 'required_if:service_type,homecare,lab,lab_custom,iv_drip,nursing_care,iv_drip_custom,custom|nullable|exists:addresses,id',

            // Ensure charges are numeric
            'appointment_charges' => 'required|numeric',

            'payment_type' => 'required|in:card,insurance',

        ];
    }


    protected function failedValidation(Validator $validator)
    {
        $response = new JsonResponse(api_validation_errors($validator->errors(), 'Validation Errors'), 422);
        throw new ValidationException($validator, $response);
    }
}
