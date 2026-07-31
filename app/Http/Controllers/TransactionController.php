<?php

namespace App\Http\Controllers;

use App\Http\Concerns\ConvertsVietnameseDates;
use App\Models\Transaction;
use App\Support\InertiaData;
use DomainException;
use Illuminate\Http\Request;
use Inertia\Inertia;
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

        $items = $transactions->getCollection()->map(fn ($tx) => InertiaData::transaction($tx))->values();
        $transactions->setCollection($items);

        return Inertia::render('Transactions/Index', [
            'transactions' => InertiaData::paginator($transactions),
            'wallets' => InertiaData::wallets($wallets),
            'filters' => [
                'wallet_id' => $request->input('wallet_id'),
                'type' => $request->input('type'),
            ],
        ]);
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

        return Inertia::render('Transactions/Create', [
            'wallets' => InertiaData::wallets($wallets),
            'templates' => $templates->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'type' => $t->type,
                'type_label' => $t->typeLabel(),
                'amount' => (float) $t->amount,
                'fee' => (float) ($t->fee ?? 0),
                'category' => $t->category,
                'description' => $t->description,
                'default_wallet_id' => $t->default_wallet_id,
                'from_wallet_id' => $t->from_wallet_id,
                'to_wallet_id' => $t->to_wallet_id,
                'adjustment_direction' => $t->adjustment_direction,
            ])->values()->all(),
            'selectedTemplateId' => $selectedTemplate?->id,
            'prefill' => $prefill,
            'walletBalances' => $wallets->mapWithKeys(fn ($w) => [(string) $w->id => (float) $w->balance]),
        ]);
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
