<?php

namespace App\Http\Requests\Account;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class DoctorUpdateAccountRequest extends FormRequest
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
            'about' => 'required',
            'experience' => 'required',
            'languages' => 'required|array',
            'file' => 'nullable',

            // Multiple session types
            'session_type' => 'required|array', // Ensure it's an array
            'session_type.*.type' => 'required|in:Chat,Call,Video Call', // Each session type must be one of these values
            'session_type.*.price' => 'required|numeric', // Price should be numeric

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
