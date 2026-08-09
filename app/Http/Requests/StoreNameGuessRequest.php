<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNameGuessRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:50',
                'regex:/^[\pL\s\'\-]+$/u',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Vul een naam in.',
            'name.min' => 'Dat is wel een héél korte naam.',
            'name.max' => 'Dat is wel een héél lange naam.',
            'name.regex' => 'Enkel letters, spaties, apostrofs en koppeltekens.',
        ];
    }
}
