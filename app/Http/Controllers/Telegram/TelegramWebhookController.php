<?php

namespace App\Http\Controllers\Telegram;

use App\Exceptions\Telegram\InvalidTelegramPayloadException;
use App\Exceptions\Telegram\UnauthorizedTelegramWebhookException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Telegram\TelegramWebhookRequest;
use App\Services\Telegram\TelegramWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class TelegramWebhookController extends Controller
{
    public function __invoke(
        TelegramWebhookRequest $request,
        string $secret,
        TelegramWebhookService $telegramWebhookService,
    ): JsonResponse {
        try {
            $telegramWebhookService->handle($secret, $request->validated());
        } catch (UnauthorizedTelegramWebhookException) {
            abort(403, 'Invalid Telegram webhook secret.');
        } catch (InvalidTelegramPayloadException $exception) {
            throw ValidationException::withMessages([
                'payload' => $exception->getMessage(),
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
