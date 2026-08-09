<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreGiftPreviewRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'url' => [
                'required',
                'url:http,https',
                'max:2048',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.required' => 'Plak een link naar de webshop.',
            'url.url' => 'Dat lijkt geen geldige link.',
        ];
    }
}
