<?php

namespace App\Http\Requests\Account;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class BuyerUpdateAccountRequest extends FormRequest
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
            'city' => 'required',
            'state' => 'required',
            'billing_address' => 'required',
            'zip_code' => 'required',
            'company_name' => 'required',
            'title' => 'required',
            'estimated_volumn_per_month' => 'required',
            'verification_document' => 'nullable',
            'terms' => 'nullable',
            'favourite_suppliers.*.supplier_id' => 'required',
            'favourite_suppliers.*.terms' => 'required',
            'favourite_suppliers.*.rating' => 'required',
            'delivery_address.*.address' => 'required',
            'delivery_address.*.city' => 'required',
            'delivery_address.*.zip_code' => 'required',
            'delivery_address.*.state' => 'required',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = new JsonResponse(api_validation_errors($validator->errors(), 'Validation Errors'), 422);
        throw new ValidationException($validator, $response);
    }
}
