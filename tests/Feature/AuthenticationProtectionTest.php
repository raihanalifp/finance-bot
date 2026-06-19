<?php

namespace Tests\Feature;

use App\Enums\TransactionSource;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationProtectionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_is_redirected_to_login_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        User::query()->updateOrCreate(['email' => 'admin@example.com'], [
            'name' => 'Personal Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('dashboard.index'));

        $this->assertAuthenticated();
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        User::query()->updateOrCreate(['email' => 'admin@example.com'], [
            'name' => 'Personal Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->from('/login')->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_telegram_webhook_is_not_protected_by_session_auth(): void
    {
        $this->postJson('/telegram/webhook/invalid-secret', [
            'update_id' => 1,
            'message' => [
                'message_id' => 10,
                'chat' => ['id' => '12345'],
                'text' => 'kopi 25000',
            ],
        ])->assertForbidden();
    }

    public function test_user_cannot_access_another_users_transaction(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $transaction = Transaction::factory()->create([
            'user_id' => $owner->id,
            'category_id' => null,
            'type' => TransactionType::Expense,
            'source' => TransactionSource::Dashboard,
        ]);

        $this->actingAs($otherUser)
            ->get(route('dashboard.transactions.show', $transaction))
            ->assertForbidden();
    }
}
