<?php

namespace App\Http\Requests;

use App\DTO\FreeContributionData;
use Illuminate\Foundation\Http\FormRequest;

class StoreFreeContributionRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'integer',
                'min:100',
                'max:1000000',
            ],
            'message' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'together_with' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Vul een bedrag in.',
            'amount.min' => 'Geef een bedrag van minstens 1 euro op.',
            'amount.max' => 'Dat bedrag is te hoog.',
            'message.max' => 'Je mededeling is te lang.',
        ];
    }

    public function fromRequest(): FreeContributionData
    {
        return new FreeContributionData(
            amount: $this->integer('amount'),
            message: $this->filled('message') ? $this->string('message')->trim()->toString() : null,
            togetherWith: $this->filled('together_with') ? $this->string('together_with')->trim()->toString() : null,
        );
    }
}
