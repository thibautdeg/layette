<?php

namespace App\Http\Requests\Admin;

use App\Models\Contribution;
use Illuminate\Foundation\Http\FormRequest;

class UpdateContributionRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        /** @var Contribution $contribution */
        $contribution = $this->route('contribution');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'amount' => $contribution->isPurchase() ? [
                'nullable',
            ] : [
                'required',
                'integer',
                'min:100',
                'max:1000000',
            ],
        ];
    }
}
