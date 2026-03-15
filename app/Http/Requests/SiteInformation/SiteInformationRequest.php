<?php

namespace App\Http\Requests\SiteInformation;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class SiteInformationRequest extends FormRequest
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
            'email' => 'required|email',
            'website_link' => 'nullable|url',
            'whatsapp_numbers' => 'nullable|array',
            'landline_numbers' => 'nullable|array',
            'social_media' => 'nullable|array',
            'social_media.*.name' => 'required|string', // Validate each social media name
            'social_media.*.url' => 'required|url', // Validate each social media URL
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = new JsonResponse(api_validation_errors($validator->errors(), 'Validation Errors'), 422);
        throw new ValidationException($validator, $response);
    }
}
