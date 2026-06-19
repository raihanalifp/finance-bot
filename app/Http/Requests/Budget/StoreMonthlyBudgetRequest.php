<?php

namespace App\Http\Requests\Budget;

use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMonthlyBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where('user_id', $this->user()?->id)->where('type', TransactionType::Expense->value)->where('is_active', true),
            ],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'month' => ['required', 'integer', 'between:1,12'],
            'amount' => ['required', 'numeric', 'min:1', 'max:999999999999'],
            'currency' => ['required', 'string', 'size:3', Rule::in(['IDR'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'currency' => strtoupper((string) $this->input('currency', 'IDR')),
            'notes' => $this->input('notes') ? strip_tags((string) $this->input('notes')) : null,
        ]);
    }
}
