<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreGiftRequest extends FormRequest
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
            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'price' => [
                'required',
                'integer',
                'min:100',
                'max:1000000',
            ],
            'shop_url' => [
                'nullable',
                'url',
                'max:2048',
            ],
            'allows_partial_contributions' => [
                'required',
                'boolean',
            ],
            'allows_purchase' => [
                'required',
                'boolean',
            ],
            'image' => [
                'nullable',
                'image',
                'max:5120',
            ],
            'image_url' => [
                'nullable',
                'url:http,https',
                'max:2048',
            ],
        ];
    }
}
