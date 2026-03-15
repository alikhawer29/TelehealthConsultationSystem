<?php

namespace App\Http\Requests\Account;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class SupplierUpdateAccountRequest extends FormRequest
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
            'billing_address' => 'required',
            'zip_code' => 'required',
            'title' => 'required',
            'estimated_volumn_per_month' => 'nullable',
            'verification_document' => 'nullable',
            'terms' => 'required',
            'business_entity_name' => 'required',
            'existing_delivery_companies.*' => 'required',
            'federal_tax_eid' => 'required',
            'wholesale_fuel_available.*' => 'required',
            'hq_address' => 'required',
            'branded_fuel_supply.*' => 'required',
            'store_location.*.lat' => 'nullable',
            'store_location.*.lng' => 'nullable',
            'state.*.county.*' => 'required|integer|exists:counties,id',

        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = new JsonResponse(api_validation_errors($validator->errors(), 'Validation Errors'), 422);
        throw new ValidationException($validator, $response);
    }
}
