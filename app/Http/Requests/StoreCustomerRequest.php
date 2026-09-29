<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Tenancy\MerchantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCustomerRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'external_reference' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('customers', 'external_reference')->where('merchant_id', $merchantId),
            ],
        ];
    }
}
