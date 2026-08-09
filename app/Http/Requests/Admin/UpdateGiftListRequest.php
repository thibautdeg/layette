<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGiftListRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'intro' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'iban' => [
                'nullable',
                'string',
                'max:34',
            ],
            'account_holder' => [
                'nullable',
                'string',
                'max:255',
            ],
            'wero_phone' => [
                'nullable',
                'string',
                'max:20',
            ],
            'expected_at' => [
                'nullable',
                'date',
            ],
            'is_closed' => [
                'required',
                'boolean',
            ],
            'photo' => [
                'nullable',
                'image',
                'max:5120',
            ],
        ];
    }
}
