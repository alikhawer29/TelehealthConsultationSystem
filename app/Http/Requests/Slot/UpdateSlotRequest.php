<?php

namespace App\Http\Requests\Slot;

use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class UpdateSlotRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'type' => ['required', Rule::in(['homecare', 'lab', 'iv_drip', 'nursing_care'])],

            // 'service' => [
            //     Rule::requiredIf(function () {
            //         return in_array(request('type'), ['homecare', 'nursing_care']);
            //     }),
            //     'exists:service,id',
            // ],

            'slots' => ['nullable', 'array'],
            'slots.*.status' => ['required', 'boolean'],

            'slots.*.times' => ['nullable', 'array'],
            'slots.*.times.*.start_time' => [
                'required_with:slots.*.times',
                'date_format:H:i'
            ],
            'slots.*.times.*.end_time' => [
                'required_with:slots.*.times',
                'date_format:H:i',
                'after:slots.*.times.*.start_time'
            ],
        ];
    }

    /**
     * Custom validation logic for duplicates and overlaps.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $slots = $this->input('slots', []);

            foreach ($slots as $day => $slotData) {
                if (empty($slotData['times']) || !$slotData['status']) {
                    continue;
                }

                $timePairs = [];

                foreach ($slotData['times'] as $index => $time) {
                    $start = $time['start_time'] ?? null;
                    $end = $time['end_time'] ?? null;
                    $id = $time['id'] ?? null; // For update mode (existing slot ID)

                    if (!$start || !$end) {
                        continue;
                    }

                    $key = "{$start}-{$end}";

                    // ✅ Duplicate check (ignore same ID)
                    if (isset($timePairs[$key]) && $timePairs[$key] !== $id) {
                        $validator->errors()->add(
                            "slots.$day.times.$index.start_time",
                            "Duplicate slot detected on {$day} ({$start} - {$end})."
                        );
                    }

                    // Store with ID for duplicate-safe updates
                    $timePairs[$key] = $id ?? uniqid();

                    // ⚡ Overlap check with existing pairs
                    foreach ($timePairs as $pairKey => $pairId) {
                        if ($pairKey === $key) continue;

                        [$prevStart, $prevEnd] = explode('-', $pairKey);

                        if ($start < $prevEnd && $end > $prevStart) {
                            $validator->errors()->add(
                                "slots.$day.times.$index.start_time",
                                "Overlapping slot detected on {$day} ({$start} - {$end})."
                            );
                            break;
                        }
                    }
                }
            }
        });
    }

    protected function failedValidation(Validator $validator)
    {
        $response = new JsonResponse(api_validation_errors($validator->errors(), 'Validation Errors'), 422);
        throw new ValidationException($validator, $response);
    }
}
