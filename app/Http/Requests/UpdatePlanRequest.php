<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\BillingCycle;
use App\Tenancy\MerchantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $merchantId = app(MerchantContext::class)->id();
        $planId = $this->route('plan')?->id;

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('plans', 'name')->where('merchant_id', $merchantId)->ignore($planId),
            ],
            'base_price' => ['sometimes', 'required', 'integer', 'min:0'],
            'billing_cycle' => ['sometimes', 'required', 'string', Rule::enum(BillingCycle::class)],
            'included_usage_units' => ['sometimes', 'required', 'integer', 'min:0'],
            'overage_rate' => ['sometimes', 'required', 'integer', 'min:0'],
        ];
    }
}
