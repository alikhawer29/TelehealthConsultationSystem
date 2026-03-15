<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserRegisterRequest extends FormRequest
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
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            // 'email' => [
            //     'required',
            //     'email',
            //     Rule::unique('users', 'email')->whereNull('deleted_at')
            // ],
            'email' => [
                'required',
                'email',
                function ($attribute, $value, $fail) {
                    $emailHash = hash('sha256', strtolower(trim($value)));
                    $exists = \App\Models\User::where('email_hash', $emailHash)
                        ->whereNull('deleted_at')
                        ->exists();

                    if ($exists) {
                        $fail('The email has already been taken.');
                    }
                },
            ],
            'country_code' => 'required|string|max:5',
            'phone' => 'required|string|max:20',

            'password' => [
                'required',
                'string',
                'min:8',
                'max:64',
                'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[@$!%*?&]).+$/',
                'confirmed'
            ],

            'file' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',

            'insurance_name' => 'nullable|string|max:100',
            'insurance_card_number' => [
                'nullable',
                Rule::unique('insurances', 'card_number')
            ],
            'insurance_card_holder_name' => 'nullable|string|max:100',
            'insurance_file' => 'nullable|file|max:2048',

            'flag_type' => 'nullable|string|max:50',

            'doc' => 'nullable',
            'scanType' => 'nullable',
        ];
    }


    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();
        $firstError = $errors->first();

        $response = new JsonResponse(api_validation_errors($validator->errors(), $firstError), 422);
        throw new ValidationException($validator, $response);
    }
}
