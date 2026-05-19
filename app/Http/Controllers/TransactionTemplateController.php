<?php

namespace App\Http\Controllers;

use App\Models\TransactionTemplate;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TransactionTemplateController extends Controller
{
    public function index()
    {
        $templates = TransactionTemplate::query()
            ->with(['defaultWallet', 'fromWallet', 'toWallet'])
            ->orderBy('name')
            ->get();

        $wallets = Wallet::query()->where('is_active', true)->orderBy('name')->get();

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

        TransactionTemplate::create($validated);

        return redirect()->route('transaction-templates.index')->with('success', 'Đã tạo mẫu giao dịch.');
    }

    public function destroy(TransactionTemplate $transactionTemplate)
    {
        $transactionTemplate->delete();

        return redirect()->route('transaction-templates.index')->with('success', 'Đã xóa mẫu.');
    }
}
