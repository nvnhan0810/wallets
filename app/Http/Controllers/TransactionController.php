<?php

namespace App\Http\Controllers;

use App\Http\Concerns\ConvertsVietnameseDates;
use App\Models\Transaction;
use App\Models\TransactionTemplate;
use App\Models\Wallet;
use App\Models\WalletTransfer;
use App\Services\WalletTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    use ConvertsVietnameseDates;

    public function __construct(private WalletTransferService $transferService) {}

    public function index(Request $request)
    {
        $query = Transaction::query()
            ->with(['wallet', 'walletTransfer.fromWallet', 'walletTransfer.toWallet'])
            ->orderByDesc('transacted_at')
            ->orderByDesc('id');

        if ($request->filled('wallet_id')) {
            $query->where('wallet_id', $request->wallet_id);
        }

        if ($request->filled('type') && in_array($request->type, ['income', 'expense', 'adjustment', 'transfer'], true)) {
            if ($request->type === 'transfer') {
                $query->whereNotNull('wallet_transfer_id');
            } else {
                $query->where('type', $request->type)->whereNull('wallet_transfer_id');
            }
        }

        $transactions = $query->paginate(20)->withQueryString();
        $wallets = Wallet::query()->where('is_active', true)->orderBy('name')->get();

        return view('transactions.index', compact('transactions', 'wallets'));
    }

    public function create(Request $request)
    {
        $wallets = Wallet::query()->where('is_active', true)->orderBy('name')->get();
        $templates = TransactionTemplate::query()->with(['defaultWallet', 'fromWallet', 'toWallet'])->orderBy('name')->get();
        $selectedTemplate = $request->filled('template_id')
            ? $templates->firstWhere('id', (int) $request->template_id)
            : null;

        $prefill = [
            'wallet_id' => $request->integer('wallet_id') ?: null,
            'type' => in_array($request->type, ['income', 'expense', 'adjustment', 'transfer'], true) ? $request->type : 'expense',
            'amount' => $request->input('amount'),
            'description' => $request->input('description'),
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
        if ($transaction->isFromLoan()) {
            return back()->withErrors(['transaction' => 'Giao dịch liên kết khoản vay. Xóa tại trang Khoản vay nếu cần.']);
        }

        if ($transaction->wallet_transfer_id) {
            $transfer = WalletTransfer::query()->findOrFail($transaction->wallet_transfer_id);
            $this->transferService->reverse($transfer);

            return back()->with('success', 'Đã hủy chuyển ví và hoàn số dư.');
        }

        DB::transaction(function () use ($transaction) {
            $wallet = Wallet::query()->lockForUpdate()->findOrFail($transaction->wallet_id);
            $effectiveType = $transaction->isAdjustment()
                ? ($transaction->adjustment_direction === 'increase' ? 'income' : 'expense')
                : $transaction->type;
            $wallet->reverseTransaction($effectiveType, (float) $transaction->amount);
            $transaction->delete();
        });

        return back()->with('success', 'Đã xóa giao dịch.');
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
            'save_as_template' => 'sometimes|boolean',
            'template_name' => 'required_if:save_as_template,1|nullable|string|max:255',
        ]);

        $validated['transacted_at'] = $this->convertDateFormat($validated['transacted_at']);
        $validated['amount'] = abs((float) $validated['amount']);

        DB::transaction(function () use ($validated, $request) {
            $wallet = Wallet::query()->lockForUpdate()->findOrFail($validated['wallet_id']);

            Transaction::create([
                'wallet_id' => $validated['wallet_id'],
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'description' => $validated['description'],
                'category' => $validated['category'] ?? null,
                'transaction_template_id' => $validated['transaction_template_id'] ?? null,
                'transacted_at' => $validated['transacted_at'],
                'note' => $validated['note'] ?? null,
            ]);

            $wallet->applyTransaction($validated['type'], $validated['amount']);

            $this->maybeSaveTemplate($request, $validated);
        });

        return redirect()->route('transactions.index')->with('success', 'Đã ghi nhận giao dịch.');
    }

    private function storeAdjustment(Request $request)
    {
        $validated = $request->validate([
            'wallet_id' => 'required|exists:wallets,id',
            'adjustment_direction' => 'required|in:increase,decrease',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'transacted_at' => 'required|string',
            'note' => 'nullable|string',
            'transaction_template_id' => 'nullable|exists:transaction_templates,id',
            'save_as_template' => 'sometimes|boolean',
            'template_name' => 'required_if:save_as_template,1|nullable|string|max:255',
        ]);

        $validated['transacted_at'] = $this->convertDateFormat($validated['transacted_at']);
        $validated['amount'] = abs((float) $validated['amount']);

        DB::transaction(function () use ($validated, $request) {
            $wallet = Wallet::query()->lockForUpdate()->findOrFail($validated['wallet_id']);
            $cashType = $validated['adjustment_direction'] === 'increase' ? 'income' : 'expense';

            Transaction::create([
                'wallet_id' => $validated['wallet_id'],
                'type' => 'adjustment',
                'adjustment_direction' => $validated['adjustment_direction'],
                'amount' => $validated['amount'],
                'description' => $validated['description'],
                'category' => 'Cân đối',
                'transaction_template_id' => $validated['transaction_template_id'] ?? null,
                'transacted_at' => $validated['transacted_at'],
                'note' => $validated['note'] ?? null,
            ]);

            $wallet->applyTransaction($cashType, $validated['amount']);

            if ($request->boolean('save_as_template') && ! ($validated['transaction_template_id'] ?? null)) {
                TransactionTemplate::create([
                    'name' => $validated['template_name'] ?? $validated['description'],
                    'type' => 'adjustment',
                    'amount' => $validated['amount'],
                    'adjustment_direction' => $validated['adjustment_direction'],
                    'description' => $validated['description'],
                    'default_wallet_id' => $validated['wallet_id'],
                ]);
            }
        });

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

        $from = Wallet::query()->findOrFail($validated['from_wallet_id']);
        $to = Wallet::query()->findOrFail($validated['to_wallet_id']);

        $this->transferService->record(
            $from,
            $to,
            (float) $validated['amount'],
            $validated['fee'],
            $validated['description'],
            $validated['transacted_at'],
            $validated['note'] ?? null,
            $validated['transaction_template_id'] ?? null,
        );

        if ($request->boolean('save_as_template') && ! ($validated['transaction_template_id'] ?? null)) {
            TransactionTemplate::create([
                'name' => $validated['template_name'] ?? $validated['description'],
                'type' => 'transfer',
                'amount' => $validated['amount'],
                'fee' => $validated['fee'],
                'description' => $validated['description'],
                'from_wallet_id' => $validated['from_wallet_id'],
                'to_wallet_id' => $validated['to_wallet_id'],
            ]);
        }

        return redirect()->route('transactions.index')->with('success', 'Đã chuyển tiền giữa các ví.');
    }

    private function maybeSaveTemplate(Request $request, array $validated): void
    {
        if ($request->boolean('save_as_template') && ! ($validated['transaction_template_id'] ?? null)) {
            TransactionTemplate::create([
                'name' => $validated['template_name'] ?? $validated['description'],
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'category' => $validated['category'] ?? null,
                'description' => $validated['description'],
                'default_wallet_id' => $validated['wallet_id'],
            ]);
        }
    }
}
