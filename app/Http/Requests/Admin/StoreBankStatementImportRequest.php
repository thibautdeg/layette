<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankStatementImportRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'statement' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:10240',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'statement.required' => 'Kies een CSV-bestand.',
            'statement.mimes' => 'Enkel CSV-bestanden worden ondersteund.',
        ];
    }
}
