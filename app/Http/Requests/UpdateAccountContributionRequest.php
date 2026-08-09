<?php

namespace App\Http\Requests;

use App\Models\Contribution;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountContributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Contribution $contribution */
        $contribution = $this->route('contribution');

        return $this->user()->can('update', $contribution);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
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
            'message.max' => 'Je mededeling is te lang.',
        ];
    }
}
