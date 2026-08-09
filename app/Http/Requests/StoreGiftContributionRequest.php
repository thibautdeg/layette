<?php

namespace App\Http\Requests;

use App\DTO\ContributionData;
use App\Enums\ContributionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGiftContributionRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'type' => [
                'required',
                Rule::enum(ContributionType::class),
            ],
            'amount' => [
                'exclude_if:type,purchase',
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

    public function fromRequest(): ContributionData
    {
        return new ContributionData(
            type: $this->enum('type', ContributionType::class),
            amount: $this->filled('amount') ? $this->integer('amount') : null,
            message: $this->filled('message') ? $this->string('message')->trim()->toString() : null,
            togetherWith: $this->filled('together_with') ? $this->string('together_with')->trim()->toString() : null,
        );
    }
}
