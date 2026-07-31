<?php

namespace App\Http\Controllers;

use App\Http\Concerns\ConvertsVietnameseDates;
use App\Models\Transaction;
use DomainException;
use Illuminate\Http\Request;
use Wallets\Catalog\Application\Query\ListTemplates;
use Wallets\Shared\Application\CommandBus;
use Wallets\Shared\Application\QueryBus;
use Wallets\WalletAccounting\Application\Command\DeleteTransaction;
use Wallets\WalletAccounting\Application\Command\RecordAdjustment;
use Wallets\WalletAccounting\Application\Command\RecordIncomeExpense;
use Wallets\WalletAccounting\Application\Command\TransferBetweenWallets;
use Wallets\WalletAccounting\Application\Query\ListTransactions;
use Wallets\WalletAccounting\Application\Query\ListWallets;

class TransactionController extends Controller
{
    use ConvertsVietnameseDates;

    public function __construct(
        private readonly CommandBus $commands,
        private readonly QueryBus $queries,
    ) {}

    public function index(Request $request)
    {
        $transactions = $this->queries->ask(new ListTransactions(
            userId: auth()->id(),
            walletId: $request->filled('wallet_id') ? (int) $request->wallet_id : null,
            type: $request->input('type'),
        ));
        $wallets = $this->queries->ask(new ListWallets(userId: auth()->id(), activeOnly: true));

        return view('transactions.index', compact('transactions', 'wallets'));
    }

    public function create(Request $request)
    {
        $wallets = $this->queries->ask(new ListWallets(userId: auth()->id(), activeOnly: true));
        $templates = $this->queries->ask(new ListTemplates(userId: auth()->id()));
        $selectedTemplate = $request->filled('template_id')
            ? $templates->firstWhere('id', (int) $request->template_id)
            : null;

        $prefill = [
            'wallet_id' => $request->integer('wallet_id') ?: null,
            'type' => in_array($request->type, ['income', 'expense', 'adjustment', 'transfer'], true) ? $request->type : 'expense',
            'amount' => $request->input('amount'),
            'description' => $request->input('description'),
            'category' => $request->input('category'),
            'transacted_at' => $request->input('transacted_at'),
            'recurring_item_id' => $request->integer('recurring_item_id') ?: null,
            'recurring_occurrence_id' => $request->integer('recurring_occurrence_id') ?: null,
        ];

        return view('transactions.create', compact('wallets', 'templates', 'selectedTemplate', 'prefill'));
    }

    public function store(Request $request)
    {
        $type = $request->input('type');

        if ($type === 'transfer') {
            return $this->storeTransfer($request);
        }

        if ($type === 'adjustment') {
            return $this->storeAdjustment($request);
        }

        return $this->storeIncomeExpense($request);
    }

    public function destroy(Transaction $transaction)
    {
        try {
            $result = $this->commands->dispatch(new DeleteTransaction(
                userId: auth()->id(),
                transactionId: $transaction->id,
            ));
        } catch (DomainException $e) {
            return back()->withErrors(['transaction' => $e->getMessage()]);
        }

        $message = ($result['type'] ?? null) === 'transfer'
            ? 'Đã hủy chuyển ví và hoàn số dư.'
            : 'Đã xóa giao dịch.';

        return back()->with('success', $message);
    }

    private function storeIncomeExpense(Request $request)
    {
        $validated = $request->validate([
            'wallet_id' => 'required|exists:wallets,id',
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'transacted_at' => 'required|string',
            'note' => 'nullable|string',
            'transaction_template_id' => 'nullable|exists:transaction_templates,id',
            'recurring_item_id' => 'nullable|exists:recurring_items,id',
            'recurring_occurrence_id' => 'nullable|exists:recurring_occurrences,id',
            'save_as_template' => 'sometimes|boolean',
            'template_name' => 'required_if:save_as_template,1|nullable|string|max:255',
        ]);

        $validated['transacted_at'] = $this->convertDateFormat($validated['transacted_at']);
        $validated['amount'] = abs((float) $validated['amount']);

        $this->commands->dispatch(new RecordIncomeExpense(
            userId: auth()->id(),
            data: $validated,
            saveAsTemplate: $request->boolean('save_as_template'),
            templateName: $validated['template_name'] ?? null,
        ));

        return redirect()->route('transactions.index')->with('success', 'Đã ghi nhận giao dịch.');
    }

    private function storeAdjustment(Request $request)
    {
        $validated = $request->validate([
            'wallet_id' => 'required|exists:wallets,id',
            'target_balance' => 'required|numeric|min:0',
            'description' => 'required|string|max:255',
            'transacted_at' => 'required|string',
            'note' => 'nullable|string',
            'transaction_template_id' => 'nullable|exists:transaction_templates,id',
            'save_as_template' => 'sometimes|boolean',
            'template_name' => 'required_if:save_as_template,1|nullable|string|max:255',
        ]);

        $validated['transacted_at'] = $this->convertDateFormat($validated['transacted_at']);
        $validated['target_balance'] = (float) $validated['target_balance'];

        try {
            $this->commands->dispatch(new RecordAdjustment(
                userId: auth()->id(),
                data: $validated,
                saveAsTemplate: $request->boolean('save_as_template'),
                templateName: $validated['template_name'] ?? null,
            ));
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['target_balance' => $e->getMessage()]);
        }

        return redirect()->route('transactions.index')->with('success', 'Đã ghi cân đối số dư ví.');
    }

    private function storeTransfer(Request $request)
    {
        $validated = $request->validate([
            'from_wallet_id' => 'required|exists:wallets,id',
            'to_wallet_id' => 'required|exists:wallets,id|different:from_wallet_id',
            'amount' => 'required|numeric|min:0.01',
            'fee' => 'nullable|numeric|min:0',
            'description' => 'required|string|max:255',
            'transacted_at' => 'required|string',
            'note' => 'nullable|string',
            'transaction_template_id' => 'nullable|exists:transaction_templates,id',
            'save_as_template' => 'sometimes|boolean',
            'template_name' => 'required_if:save_as_template,1|nullable|string|max:255',
        ]);

        $validated['transacted_at'] = $this->convertDateFormat($validated['transacted_at']);
        $validated['fee'] = (float) ($validated['fee'] ?? 0);

        $this->commands->dispatch(new TransferBetweenWallets(
            userId: auth()->id(),
            data: $validated,
            saveAsTemplate: $request->boolean('save_as_template'),
            templateName: $validated['template_name'] ?? null,
        ));

        return redirect()->route('transactions.index')->with('success', 'Đã chuyển tiền giữa các ví.');
    }
}
