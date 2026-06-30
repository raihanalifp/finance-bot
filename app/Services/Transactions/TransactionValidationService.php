<?php

namespace App\Services\Transactions;

use App\DTOs\Transactions\ParsedTransactionData;
use App\DTOs\Transactions\TransactionValidationResult;

class TransactionValidationService
{
    public function validate(ParsedTransactionData $parsed): TransactionValidationResult
    {
        if ($parsed->amount === null || $parsed->amount <= 0) {
            return TransactionValidationResult::failed(
                'missing_amount',
                "Nominal belum ditemukan.\n\nGunakan format:\nbonus 1500000 income",
            );
        }

        if (blank($parsed->description)) {
            return TransactionValidationResult::failed(
                'missing_description',
                "Deskripsinya apa?\n\nContoh:\nmakan 100000",
            );
        }

        if ($parsed->type === null) {
            return TransactionValidationResult::failed(
                'missing_type',
                "Tipe transaksi belum jelas.\n\nGunakan income atau expense.",
            );
        }

        return TransactionValidationResult::ok();
    }
}
