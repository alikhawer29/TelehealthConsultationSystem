<?php

namespace App\Http\Requests\Account;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class CompanyUpdateAccountRequest extends FormRequest
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
            'office_country_code' => 'required',
            'office_phone' => 'required',
            'state' => 'required',
            'city' => 'required',
            'street_address' => 'required',
            'zip_code' => 'required',
            'terms' => 'required',
            'company_name' => 'required',
            'title' => 'required',
            'terminals.*' => 'required',
            'federal_tax_eid' => 'required',
            'building_no' => 'required',
            'states.*.county.*' => 'required|integer|exists:counties,id',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = new JsonResponse(api_validation_errors($validator->errors(), 'Validation Errors'), 422);
        throw new ValidationException($validator, $response);
    }
}
