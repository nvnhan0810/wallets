<?php

namespace Wallets\WalletAccounting\Application\Handler;

use App\Models\Transaction;
use App\Models\TransactionTemplate;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Wallets\Shared\Application\Command;
use Wallets\Shared\Application\CommandHandler;
use Wallets\WalletAccounting\Application\Command\RecordAdjustment;

final class RecordAdjustmentHandler implements CommandHandler
{
    public function handle(Command $command): mixed
    {
        assert($command instanceof RecordAdjustment);

        $data = $command->data;

        return DB::transaction(function () use ($command, $data) {
            $wallet = Wallet::query()->forUser($command->userId)->lockForUpdate()->findOrFail($data['wallet_id']);
            $cashType = $data['adjustment_direction'] === 'increase' ? 'income' : 'expense';

            $tx = Transaction::create([
                'user_id' => $command->userId,
                'wallet_id' => $data['wallet_id'],
                'type' => 'adjustment',
                'adjustment_direction' => $data['adjustment_direction'],
                'amount' => $data['amount'],
                'description' => $data['description'],
                'category' => 'Cân đối',
                'transaction_template_id' => $data['transaction_template_id'] ?? null,
                'transacted_at' => $data['transacted_at'],
                'note' => $data['note'] ?? null,
            ]);

            $wallet->applyTransaction($cashType, (float) $data['amount']);

            if ($command->saveAsTemplate && empty($data['transaction_template_id'])) {
                TransactionTemplate::create([
                    'user_id' => $command->userId,
                    'name' => $command->templateName ?? $data['description'],
                    'type' => 'adjustment',
                    'amount' => $data['amount'],
                    'adjustment_direction' => $data['adjustment_direction'],
                    'description' => $data['description'],
                    'default_wallet_id' => $data['wallet_id'],
                ]);
            }

            return $tx;
        });
    }
}
