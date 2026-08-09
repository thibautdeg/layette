<?php

namespace App\Http\Requests;

use App\Models\Contribution;
use Illuminate\Foundation\Http\FormRequest;

class DestroyAccountContributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Contribution $contribution */
        $contribution = $this->route('contribution');

        return $this->user()->can('delete', $contribution);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
