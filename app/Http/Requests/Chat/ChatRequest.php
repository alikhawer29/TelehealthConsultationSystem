<?php

namespace App\Http\Requests\Chat;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ChatRequest extends FormRequest
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
            'chat_type' => 'required|in:admin,doctor,physician,nurse,user',
            'receiver_id' => 'required_if:chat_type,doctor,physician,nurse|exists:users,id',
            // 'appointment_id' => 'required_if:chat_type,doctor,physician,nurse|exists:appointments,id',
            'appointment_id' => 'nullable',

        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = new JsonResponse(api_validation_errors($validator->errors(), 'Validation Errors'), 422);
        throw new ValidationException($validator, $response);
    }
}
