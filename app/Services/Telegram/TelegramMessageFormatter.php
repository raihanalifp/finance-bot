<?php

namespace App\Services\Telegram;

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Support\Number;

class TelegramMessageFormatter
{
    public function startHelp(): string
    {
        return "Halo! Kirim transaksi seperti:\nnasi padang 15k\nkopi 25000\ngaji 5000000\nparkir 5000";
    }

    public function inputHelp(): string
    {
        return "Format transaksi:\n[deskripsi] [nominal]\n\nContoh:\nnasi padang 15k\nkopi 25000\ngaji 5000000";
    }

    public function incompleteInput(): string
    {
        return "Input belum lengkap. Gunakan format:\nnasi padang 15k\nkopi 25000";
    }

    public function transactionSaved(Transaction $transaction, Category $category, string $reason, bool $learned): string
    {
        return sprintf(
            "%s dicatat\n\nDeskripsi: %s\nNominal: %s\nKategori: %s\nAlasan: %s%s",
            $transaction->type === TransactionType::Income ? '✅ Pemasukan' : '✅ Pengeluaran',
            $transaction->description,
            Number::currency((float) $transaction->amount, $transaction->currency, 'id'),
            $category->name,
            $reason,
            $learned ? "\nMemory kategori diperbarui untuk transaksi berikutnya." : '',
        );
    }

    public function categoryQuestion(array $categories, string $reason, int $confidenceScore): string
    {
        $lines = [
            'Kategori belum cukup yakin.',
            "Alasan: {$reason}",
            "Confidence: {$confidenceScore}%",
            '',
            'Pilih kategori:',
        ];

        foreach ($categories as $index => $category) {
            $lines[] = ($index + 1).'. '.$this->localizedCategoryName($category);
        }

        return implode("\n", $lines);
    }

    private function localizedCategoryName(Category $category): string
    {
        return match ($category->slug) {
            'food-drink' => 'Makanan',
            'transport' => 'Transportasi',
            'shopping' => 'Belanja',
            'entertainment' => 'Hiburan',
            'other-expense' => 'Lainnya',
            default => $category->name,
        };
    }
}
