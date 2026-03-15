<?php

namespace App\Http\Requests\Password;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserVerifyCodeRequest extends FormRequest
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
            'email' => ['required', 'email'],
            'email_hash' => ['required', Rule::exists('users', 'email_hash')
                ->where(function ($query) {
                    $query->whereNull('deleted_at');
                })],
            'code' => 'required',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Create email_hash from the email for validation
        if ($this->has('email')) {
            $this->merge([
                'email_hash' => hash('sha256', strtolower(trim($this->email)))
            ]);
        }
    }

    public function messages()
    {
        return [
            'email_hash.exists' => 'Email doesn\'t exist in our record. Please try with valid email.',
            'code.required' => 'Verification code is required.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = new JsonResponse(api_validation_errors($validator->errors(), 'Validation Errors'), 422);
        throw new ValidationException($validator, $response);
    }
}
