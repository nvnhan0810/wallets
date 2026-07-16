<?php

namespace App\Http\Controllers;

use App\Models\TransactionTemplate;
use Illuminate\Http\Request;
use Wallets\Catalog\Application\Command\CreateTransactionTemplate;
use Wallets\Catalog\Application\Command\DeleteTransactionTemplate;
use Wallets\Catalog\Application\Query\ListTemplates;
use Wallets\Shared\Application\CommandBus;
use Wallets\Shared\Application\QueryBus;
use Wallets\WalletAccounting\Application\Query\ListWallets;

class TransactionTemplateController extends Controller
{
    public function __construct(
        private readonly CommandBus $commands,
        private readonly QueryBus $queries,
    ) {}

    public function index()
    {
        $templates = $this->queries->ask(new ListTemplates(userId: auth()->id()));
        $wallets = $this->queries->ask(new ListWallets(userId: auth()->id(), activeOnly: true));

        return view('transaction-templates.index', compact('templates', 'wallets'));
    }

    public function store(Request $request)
    {
        $type = $request->input('type');

        $rules = [
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,expense,adjustment,transfer',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ];

        if ($type === 'transfer') {
            $rules['from_wallet_id'] = 'required|exists:wallets,id';
            $rules['to_wallet_id'] = 'required|exists:wallets,id|different:from_wallet_id';
            $rules['fee'] = 'nullable|numeric|min:0';
        } elseif ($type === 'adjustment') {
            $rules['default_wallet_id'] = 'required|exists:wallets,id';
            $rules['adjustment_direction'] = 'required|in:increase,decrease';
        } else {
            $rules['category'] = 'nullable|string|max:255';
            $rules['default_wallet_id'] = 'nullable|exists:wallets,id';
        }

        $validated = $request->validate($rules);
        $validated['fee'] = $type === 'transfer' ? (float) ($validated['fee'] ?? 0) : null;

        $this->commands->dispatch(new CreateTransactionTemplate(
            userId: auth()->id(),
            data: $validated,
        ));

        return redirect()->route('transaction-templates.index')->with('success', 'Đã tạo mẫu giao dịch.');
    }

    public function destroy(TransactionTemplate $transactionTemplate)
    {
        $this->commands->dispatch(new DeleteTransactionTemplate(
            userId: auth()->id(),
            templateId: $transactionTemplate->id,
        ));

        return redirect()->route('transaction-templates.index')->with('success', 'Đã xóa mẫu.');
    }
}
