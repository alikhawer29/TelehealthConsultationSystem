<?php

namespace App\Http\Requests\Slot;

use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class SlotRequest extends FormRequest
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
            'type' => ['required', Rule::in(['homecare', 'lab', 'iv_drip', 'nursing_care'])], // Only allows "homecare" or "lab"
            // 'service' => [
            //     Rule::requiredIf(function () {
            //         return in_array(request('type'), ['homecare', 'nursing_care']);
            //     }),
            //     'exists:service,id',
            // ],
            // Slots validation
            'slots' => ['nullable', 'array'],
            'slots.*.status' => ['required', 'boolean'],

            // Validate each day's slot times (can be an array of times)
            'slots.*.times' => ['nullable', 'array'],
            'slots.*.times.*.start_time' => ['required_with:slots.*.times', 'date_format:H:i'],
            'slots.*.times.*.end_time' => ['required_with:slots.*.times', 'date_format:H:i', 'after:slots.*.times.*.start_time'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = new JsonResponse(api_validation_errors($validator->errors(), 'Validation Errors'), 422);
        throw new ValidationException($validator, $response);
    }
}
