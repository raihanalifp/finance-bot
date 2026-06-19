<?php

namespace App\Http\Requests\Telegram;

use Illuminate\Foundation\Http\FormRequest;

class TelegramWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'update_id' => ['nullable'],
            'message' => ['required_without:edited_message', 'array'],
            'edited_message' => ['required_without:message', 'array'],
            'message.chat.id' => ['required_with:message'],
            'message.text' => ['nullable', 'string', 'max:4096'],
            'edited_message.chat.id' => ['required_with:edited_message'],
            'edited_message.text' => ['nullable', 'string', 'max:4096'],
        ];
    }
}
