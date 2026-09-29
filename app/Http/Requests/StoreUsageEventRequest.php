<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreUsageEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'event_id' => ['required', 'string', 'max:255'],
            'customer_id' => ['required', 'string'],
            'subscription_id' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'occurred_at' => ['required', 'date'],
        ];
    }
}
