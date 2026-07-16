<?php

namespace Wallets\WalletAccounting\Application\Handler;

use App\Models\Transaction;
use App\Models\TransactionTemplate;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Wallets\Shared\Application\Command;
use Wallets\Shared\Application\CommandHandler;
use Wallets\WalletAccounting\Application\Command\RecordIncomeExpense;

final class RecordIncomeExpenseHandler implements CommandHandler
{
    public function handle(Command $command): mixed
    {
        assert($command instanceof RecordIncomeExpense);

        $data = $command->data;

        return DB::transaction(function () use ($command, $data) {
            $wallet = Wallet::query()->forUser($command->userId)->lockForUpdate()->findOrFail($data['wallet_id']);

            $tx = Transaction::create([
                'user_id' => $command->userId,
                'wallet_id' => $data['wallet_id'],
                'type' => $data['type'],
                'amount' => $data['amount'],
                'description' => $data['description'],
                'category' => $data['category'] ?? null,
                'transaction_template_id' => $data['transaction_template_id'] ?? null,
                'transacted_at' => $data['transacted_at'],
                'note' => $data['note'] ?? null,
            ]);

            $wallet->applyTransaction($data['type'], (float) $data['amount']);

            if ($command->saveAsTemplate && empty($data['transaction_template_id'])) {
                TransactionTemplate::create([
                    'user_id' => $command->userId,
                    'name' => $command->templateName ?? $data['description'],
                    'type' => $data['type'],
                    'amount' => $data['amount'],
                    'category' => $data['category'] ?? null,
                    'description' => $data['description'],
                    'default_wallet_id' => $data['wallet_id'],
                ]);
            }

            return $tx;
        });
    }
}
