<?php

namespace App\Http\Requests\Bundle;

use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class BundleRequest extends FormRequest
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
            'bundle_name'   => ['required', 'string', 'max:255'],
            "about" => ['required'],
            'parameters_included' => ['required'],
            "precautions" => ['required'],
            "fasting_requirments" => ['required'],
            "turnaround_time" => ['required'],
            'when_to_get_tested' => ['required'],
            'price'         => ['required'],
            'status'        => ['required'], // Adjust based on status options
            'file'          => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'], // File validation (adjust as needed)
            'icon'          => ['required', 'file'], // File validation (adjust as needed)

        ];
    }


    protected function failedValidation(Validator $validator)
    {
        $response = new JsonResponse(api_validation_errors($validator->errors(), 'Validation Errors'), 422);
        throw new ValidationException($validator, $response);
    }
}
