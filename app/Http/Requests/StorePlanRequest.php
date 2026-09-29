<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\BillingCycle;
use App\Tenancy\MerchantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $merchantId = app(MerchantContext::class)->id();

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('plans', 'name')->where('merchant_id', $merchantId),
            ],
            'base_price' => ['required', 'integer', 'min:0'],
            'billing_cycle' => ['required', 'string', Rule::enum(BillingCycle::class)],
            'included_usage_units' => ['required', 'integer', 'min:0'],
            'overage_rate' => ['required', 'integer', 'min:0'],
        ];
    }
}
