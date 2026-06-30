<?php

namespace Tests\Feature;

use App\Enums\TransactionDraftStatus;
use App\Enums\TransactionSource;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\TelegramUser;
use App\Models\Transaction;
use App\Models\TransactionDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramConversationFlowTest extends TestCase
{
    use DatabaseTransactions;

    private string $secret = 'telegram-secret';

    private User $user;

    private TelegramUser $telegramUser;

    private int $nextUpdateId = 1000;

    private int $nextMessageId = 5000;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-19 10:00:00');

        config([
            'services.telegram.webhook_secret' => $this->secret,
            'services.telegram.bot_token' => '123456:test-token',
            'services.telegram.allowed_chat_ids' => [],
        ]);

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $this->user = User::factory()->create();
        $this->telegramUser = TelegramUser::factory()->create([
            'user_id' => $this->user->id,
            'telegram_chat_id' => '12345',
            'is_authorized' => true,
        ]);

        $this->createCategory('Food & Drink', 'food-drink', TransactionType::Expense, 1);
        $this->createCategory('Transport', 'transport', TransactionType::Expense, 2);
        $this->createCategory('Shopping', 'shopping', TransactionType::Expense, 3);
        $this->createCategory('Entertainment', 'entertainment', TransactionType::Expense, 4);
        $this->createCategory('Other Expense', 'other-expense', TransactionType::Expense, 5);
        $this->createCategory('Bills', 'bills', TransactionType::Expense, 6);
        $this->createCategory('Salary', 'salary', TransactionType::Income, 1);
        $this->createCategory('Other Income', 'other-income', TransactionType::Income, 2);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_simple_expense_is_saved_and_acknowledged(): void
    {
        $this->postTelegram('kopi 18000')->assertOk();

        $transaction = Transaction::query()->firstOrFail();

        $this->assertSame(TransactionType::Expense, $transaction->type);
        $this->assertSame('kopi', $transaction->description);
        $this->assertEquals(18000, (float) $transaction->amount);
        $this->assertSame('food-drink', $transaction->category->slug);
        $this->assertSame('2026-06-19', $transaction->transaction_date->toDateString());
        $this->assertTelegramSent('✅ Pengeluaran dicatat');
        $this->assertTelegramSent('Ketik /undo untuk membatalkan.');
    }

    public function test_income_and_optional_date_are_parsed(): void
    {
        $this->postTelegram('income freelance 2000000 2026-06-26')->assertOk();

        $transaction = Transaction::query()->firstOrFail();

        $this->assertSame(TransactionType::Income, $transaction->type);
        $this->assertSame('freelance', $transaction->description);
        $this->assertEquals(2000000, (float) $transaction->amount);
        $this->assertSame('salary', $transaction->category->slug);
        $this->assertSame('2026-06-26', $transaction->transaction_date->toDateString());
    }

    public function test_explicit_category_alias_is_used(): void
    {
        $this->postTelegram('makan 35000 food')->assertOk();

        $transaction = Transaction::query()->firstOrFail();

        $this->assertSame('makan', $transaction->description);
        $this->assertSame('food-drink', $transaction->category->slug);
    }

    public function test_ambiguous_missing_amount_and_description_return_guidance(): void
    {
        $transactionCount = Transaction::query()->count();

        $this->postTelegram('bonus')->assertOk();
        $this->assertSame($transactionCount, Transaction::query()->count());
        $this->assertTelegramSent('Nominal belum ditemukan.');

        $this->postTelegram('100000')->assertOk();
        $this->assertSame($transactionCount, Transaction::query()->count());
        $this->assertTelegramSent('Deskripsinya apa?');
    }

    public function test_commands_help_summary_last_categories_cancel_and_undo(): void
    {
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => Category::query()->where('slug', 'food-drink')->value('id'),
            'telegram_user_id' => $this->telegramUser->id,
            'type' => TransactionType::Expense,
            'amount' => 18000,
            'description' => 'kopi',
            'transaction_date' => now()->toDateString(),
            'source' => TransactionSource::Telegram,
            'created_at' => now(),
        ]);

        TransactionDraft::factory()->create([
            'user_id' => $this->user->id,
            'telegram_user_id' => $this->telegramUser->id,
            'status' => TransactionDraftStatus::Pending,
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->postTelegram('/help')->assertOk();
        $this->assertTelegramSent('Format transaksi:');

        $this->postTelegram('/today')->assertOk();
        $this->assertTelegramSent('Ringkasan hari ini');

        $this->postTelegram('/month')->assertOk();
        $this->assertTelegramSent('Ringkasan bulan berjalan');

        $this->postTelegram('/last')->assertOk();
        $this->assertTelegramSent('Transaksi terakhir:');

        $this->postTelegram('/categories')->assertOk();
        $this->assertTelegramSent('Kategori aktif:');

        $this->postTelegram('/cancel')->assertOk();
        $this->assertTelegramSent('Draft transaksi dibatalkan.');

        $this->postTelegram('/undo')->assertOk();
        $this->assertSoftDeleted($transaction);
        $this->assertTelegramSent('Transaksi terakhir dibatalkan.');
    }

    public function test_undo_is_unavailable_after_safe_window(): void
    {
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => Category::query()->where('slug', 'food-drink')->value('id'),
            'telegram_user_id' => $this->telegramUser->id,
            'type' => TransactionType::Expense,
            'amount' => 18000,
            'description' => 'kopi',
            'transaction_date' => now()->toDateString(),
            'source' => TransactionSource::Telegram,
            'created_at' => now()->subMinutes(16),
        ]);

        $this->postTelegram('/undo')->assertOk();

        $this->assertTelegramSent('Tidak ada transaksi Telegram terakhir yang bisa dibatalkan');
    }

    public function test_confirmation_flow_can_save_or_cancel_ambiguous_category(): void
    {
        $this->postTelegram('sepatu 42000')->assertOk();
        $this->assertDatabaseMissing('transactions', [
            'user_id' => $this->user->id,
            'description' => 'sepatu',
        ]);
        $this->assertDatabaseHas('transaction_drafts', [
            'description' => 'sepatu',
            'status' => TransactionDraftStatus::Pending,
        ]);
        $this->assertTelegramSent('Apakah transaksi ini benar?');

        $this->postTelegram('1')->assertOk();
        $this->assertDatabaseHas('transactions', [
            'description' => 'sepatu',
            'amount' => 42000,
        ]);

        $this->postTelegram('jaket 50000')->assertOk();
        $this->postTelegram('3')->assertOk();
        $this->assertDatabaseHas('transaction_drafts', [
            'description' => 'jaket',
            'status' => TransactionDraftStatus::Cancelled,
        ]);
    }

    public function test_duplicate_processed_update_does_not_create_duplicate_transaction(): void
    {
        $payload = $this->telegramPayload('kopi 18000', updateId: 42, messageId: 99);

        $this->postJson('/telegram/webhook/'.$this->secret, $payload)->assertOk();
        $this->postJson('/telegram/webhook/'.$this->secret, $payload)->assertOk();

        $this->assertSame(1, Transaction::query()
            ->where('user_id', $this->user->id)
            ->where('description', 'kopi')
            ->where('amount', 18000)
            ->count());
    }

    public function test_unauthorized_chat_is_ignored(): void
    {
        $this->telegramUser->update(['is_authorized' => false]);
        $transactionCount = Transaction::query()->count();

        $this->postTelegram('kopi 18000')->assertOk();

        $this->assertSame($transactionCount, Transaction::query()->count());
        Http::assertNothingSent();
    }

    private function postTelegram(string $text)
    {
        return $this->postJson('/telegram/webhook/'.$this->secret, $this->telegramPayload($text));
    }

    private function telegramPayload(string $text, ?int $updateId = null, ?int $messageId = null): array
    {
        return [
            'update_id' => $updateId ?? $this->nextUpdateId++,
            'message' => [
                'message_id' => $messageId ?? $this->nextMessageId++,
                'from' => [
                    'id' => '67890',
                    'username' => 'finance_user',
                    'first_name' => 'Finance',
                ],
                'chat' => ['id' => $this->telegramUser->telegram_chat_id],
                'text' => $text,
            ],
        ];
    }

    private function createCategory(string $name, string $slug, TransactionType $type, int $sortOrder): Category
    {
        return Category::factory()->create([
            'user_id' => $this->user->id,
            'name' => $name,
            'slug' => $slug,
            'type' => $type,
            'sort_order' => $sortOrder,
            'is_active' => true,
        ]);
    }

    private function assertTelegramSent(string $text): void
    {
        Http::assertSent(fn (Request $request): bool => str_contains((string) $request['text'], $text));
    }
}
