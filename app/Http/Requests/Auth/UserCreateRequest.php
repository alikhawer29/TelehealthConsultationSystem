<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserCreateRequest extends FormRequest
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
            'user_name' => 'required',
            'email' => ['required', Rule::unique('users', 'email')->whereNull('deleted_at')],
            'country_code' => 'nullable',
            'phone' => 'nullable',
            'password' => [
                'required',
                'string',
                'min:8', // minimum 8 characters
                'regex:/[A-Z]/', // must contain at least one uppercase letter
                'regex:/[a-z]/', // must contain at least one lowercase letter
                'regex:/[0-9]/', // must contain at least one digit
                'regex:/[@$!%*?&]/', // must contain a special character
                'confirmed'
            ],
            'user_id' => ['required', Rule::unique('users', 'user_id')->whereNull('deleted_at')],
            'apply_time_restriction' => 'required',
            'time_slots.*.day' => 'nullable', // Allow day to be optional
            'time_slots.*.from' => 'nullable|required_with:time_slots.*.day', // Only required if day is provided
            'time_slots.*.to' => 'nullable|required_with:time_slots.*.day', // Only required if day is provided
            'access_rights.*' => 'nullable',
            'accounts_permission.*' => 'nullable',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = new JsonResponse(api_validation_errors($validator->errors(), 'Validation Errors'), 422);
        throw new ValidationException($validator, $response);
    }
}
