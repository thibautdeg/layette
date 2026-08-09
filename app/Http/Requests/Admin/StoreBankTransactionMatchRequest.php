<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankTransactionMatchRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'contribution_id' => [
                'required',
                'integer',
                'exists:contributions,id',
            ],
        ];
    }
}
