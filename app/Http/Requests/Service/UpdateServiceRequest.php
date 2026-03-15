<?php

namespace App\Http\Requests\Service;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class UpdateServiceRequest extends FormRequest
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
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric'],
            'type' => ['required', 'string'], // homecare, lab, iv_drip, nursing_care
            'status' => ['required'],
            'file' => ['nullable'],
            'icon' => ['nullable'],

        ];

        $type = $this->input('type');

        if ($type === 'homecare') {
            $rules = array_merge($rules, [
                'about' => ['required'],
                'conditions_to_treat' => ['required', 'array'],
                'what_to_expect_during_the_sessions' => ['required', 'array'],
                'preparations_and_precautions' => ['required', 'array'],
                'who_should_consider_this_service' => ['required'],

                // slots required for homecare
                'slots' => ['nullable', 'array'],
                'slots.*.status' => ['required', 'boolean'],
                'slots.*.times' => ['nullable', 'array'],
                'slots.*.times.*.start_time' => ['required_with:slots.*.times', 'date_format:H:i'],
                'slots.*.times.*.end_time' => [
                    'required_with:slots.*.times',
                    'date_format:H:i',
                    // Note: 'after' won't work reliably with wildcards. Use custom rule if needed.
                ],
            ]);
        } elseif ($type === 'iv_drip') {
            $rules = array_merge($rules, [
                'general_information' => ['required'],
                'ingredients' => ['required', 'array'],
                'preparations' => ['required'],
                'administration_time' => ['required'],
                'restriction' => ['required', 'array'],
            ]);
        } elseif ($type === 'lab') {
            $rules = array_merge($rules, [
                "about" => ['required'],
                'parameters_included' => ['required'],
                "precautions" => ['required'],
                "fasting_requirments" => ['required'],
                "turnaround_time" => ['required'],
                'when_to_get_tested' => ['required'],
            ]);
        } elseif ($type === 'nursing_care') {
            $rules = array_merge($rules, [
                'about' => ['required'],
                'conditions_to_treat' => ['required', 'array'],
                'what_to_expect_during_the_sessions' => ['required', 'array'],
                'preparations_and_precautions' => ['required', 'array'],
                'who_should_consider_this_service' => ['required'],

                // slots required for iv_drip
                'slots' => ['nullable', 'array'],
                'slots.*.status' => ['required', 'boolean'],
                'slots.*.times' => ['nullable', 'array'],
                'slots.*.times.*.start_time' => ['required_with:slots.*.times', 'date_format:H:i'],
                'slots.*.times.*.end_time' => [
                    'required_with:slots.*.times',
                    'date_format:H:i',
                ],
            ]);
        }

        return $rules;
    }

    protected function failedValidation(Validator $validator)
    {
        $response = new JsonResponse(api_validation_errors($validator->errors(), 'Validation Errors'), 422);
        throw new ValidationException($validator, $response);
    }
}
