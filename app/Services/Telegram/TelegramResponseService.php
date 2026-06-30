<?php

namespace App\Services\Telegram;

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionDraft;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class TelegramResponseService
{
    public function startHelp(): string
    {
        return "Bot finance aktif.\n\nKirim transaksi seperti:\nkopi 18000\nmakan 35000 food\ngaji 8000000 income\nincome freelance 2000000 2026-06-26\n\nKetik /help untuk bantuan.";
    }

    public function inputHelp(): string
    {
        return "Format transaksi:\n[deskripsi] [nominal] [tipe opsional] [kategori opsional] [tanggal opsional]\n\nContoh:\nkopi 18000\nmakan 35000 food\ngaji 8000000 income\nincome freelance 2000000 2026-06-26";
    }

    public function validationError(string $message): string
    {
        return $message;
    }

    public function transactionSaved(Transaction $transaction, Category $category, string $reason = '', bool $learned = false): string
    {
        $lines = [
            ($transaction->type === TransactionType::Income ? '✅ Pemasukan dicatat' : '✅ Pengeluaran dicatat'),
            '',
            'Deskripsi: '.$transaction->description,
            'Nominal: '.$this->money((float) $transaction->amount),
            'Kategori: '.$category->name,
            'Tanggal: '.$this->date($transaction->transaction_date),
            '',
            'Ketik /undo untuk membatalkan.',
        ];

        if ($learned) {
            $lines[] = '';
            $lines[] = 'Memory kategori diperbarui untuk transaksi berikutnya.';
        }

        return implode("\n", $lines);
    }

    public function confirmationQuestion(Transaction|TransactionDraft $draftLike, Category $category): string
    {
        return implode("\n", [
            'Apakah transaksi ini benar?',
            '',
            $draftLike->type === TransactionType::Income ? 'Income: '.$this->money((float) $draftLike->amount) : 'Expense: '.$this->money((float) $draftLike->amount),
            'Deskripsi: '.$draftLike->description,
            'Kategori: '.$category->name,
            '',
            'Balas:',
            '1 untuk simpan',
            '2 untuk ubah kategori',
            '3 untuk batal',
        ]);
    }

    /** @param array<int, Category> $categories */
    public function categoryQuestion(array $categories, string $reason = 'Kategori belum diketahui.', int $confidenceScore = 0): string
    {
        $lines = [
            'Pilih kategori:',
        ];

        if ($reason !== '') {
            array_unshift($lines, "Alasan: {$reason}", '');
        }

        foreach ($categories as $index => $category) {
            $lines[] = ($index + 1).'. '.$category->name;
        }

        return implode("\n", $lines);
    }

    public function cancelled(): string
    {
        return 'Draft transaksi dibatalkan.';
    }

    public function nothingToCancel(): string
    {
        return 'Tidak ada transaksi draft yang perlu dibatalkan.';
    }

    public function unknownCommand(): string
    {
        return 'Command belum dikenal. Ketik /help untuk bantuan.';
    }

    public function summary(string $title, float $income, float $expense, int $count): string
    {
        return implode("\n", [
            $title,
            '',
            'Pemasukan: '.$this->money($income),
            'Pengeluaran: '.$this->money($expense),
            'Net: '.$this->money($income - $expense),
            'Transaksi: '.$count,
        ]);
    }

    public function noLastTransaction(): string
    {
        return 'Belum ada transaksi dari Telegram.';
    }

    public function lastTransaction(Transaction $transaction): string
    {
        return implode("\n", [
            'Transaksi terakhir:',
            '',
            'Deskripsi: '.$transaction->description,
            'Nominal: '.$this->money((float) $transaction->amount),
            'Kategori: '.($transaction->category?->name ?? 'Uncategorized'),
            'Tanggal: '.$this->date($transaction->transaction_date),
        ]);
    }

    public function undoSuccess(Transaction $transaction): string
    {
        return implode("\n", [
            'Transaksi terakhir dibatalkan.',
            '',
            'Deskripsi: '.$transaction->description,
            'Nominal: '.$this->money((float) $transaction->amount),
        ]);
    }

    public function undoUnavailable(): string
    {
        return 'Tidak ada transaksi Telegram terakhir yang bisa dibatalkan dalam 15 menit terakhir.';
    }

    /** @param Collection<int, Category> $categories */
    public function categories(Collection $categories): string
    {
        if ($categories->isEmpty()) {
            return 'Belum ada kategori aktif.';
        }

        $lines = ['Kategori aktif:'];

        foreach ([TransactionType::Expense, TransactionType::Income] as $type) {
            $items = $categories->filter(fn (Category $category): bool => $category->type === $type);

            if ($items->isEmpty()) {
                continue;
            }

            $lines[] = '';
            $lines[] = $type === TransactionType::Expense ? 'Expense:' : 'Income:';

            foreach ($items as $category) {
                $lines[] = '- '.$category->name.' ('.$category->slug.')';
            }
        }

        return implode("\n", $lines);
    }

    public function money(float $amount): string
    {
        return 'Rp'.number_format($amount, 0, ',', '.');
    }

    public function date(CarbonInterface|string|null $date): string
    {
        if ($date instanceof CarbonInterface) {
            return $date->format('d M Y');
        }

        return $date ? (string) $date : now()->format('d M Y');
    }
}
