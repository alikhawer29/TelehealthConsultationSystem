<?php

namespace App\Http\Requests\HealthCare;

use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class UpdateHealthCareRequest extends FormRequest
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
            'first_name' => 'required',
            'last_name' => 'required',
            'country_code' => 'required',
            'phone' => 'required',
            'professional' => 'required|in:Physician,Nurse,Consultant',

            'about' => 'required',
            'experience' => 'required',
            'languages' => 'required|array',
            'file' => 'nullable',

            'session_type' => 'required_if:professional,Consultant|array',
            'session_type.*.type' => 'required_if:professional,Consultant|in:Chat,Call,Video Call',
            'session_type.*.price' => 'required_if:professional,Consultant|numeric',

            // Single license
            'license.authroity' => 'required|string',
            'license.number' => 'required|string',
            'license.expiry' => 'required|date',
            'license.specialty' => 'required',
            'license.license_file' => 'nullable',


            // Multiple education entries
            'education' => 'required|array', // Ensure it's an array
            'education.*.institution_name' => 'required|string', // Institution name should be required
            'education.*.degree_title' => 'required|string', // Degree title should be required
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = new JsonResponse(api_validation_errors($validator->errors(), 'Validation Errors'), 422);
        throw new ValidationException($validator, $response);
    }
}
