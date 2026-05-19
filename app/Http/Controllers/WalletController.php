<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WalletController extends Controller
{
    public function index()
    {
        $wallets = Wallet::query()
            ->withCount('transactions')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('wallets.index', compact('wallets'));
    }

    public function create()
    {
        return view('wallets.create');
    }

    public function store(Request $request)
    {
        Wallet::create($this->validatedWalletData($request));

        return redirect()->route('wallets.index')->with('success', 'Đã tạo ví.');
    }

    public function edit(Wallet $wallet)
    {
        return view('wallets.edit', compact('wallet'));
    }

    public function update(Request $request, Wallet $wallet)
    {
        $wallet->update($this->validatedWalletData($request, $wallet));

        return redirect()->route('wallets.index')->with('success', 'Đã cập nhật ví.');
    }

    public function destroy(Wallet $wallet)
    {
        if ($wallet->transactions()->exists() || $wallet->recurringItems()->exists()) {
            return back()->withErrors(['wallet' => 'Không thể xóa ví đang có giao dịch hoặc khoản thu/chi cố định.']);
        }

        $wallet->delete();

        return redirect()->route('wallets.index')->with('success', 'Đã xóa ví.');
    }

    private function validatedWalletData(Request $request, ?Wallet $wallet = null): array
    {
        $isCreditCard = $request->input('type') === 'credit_card';
        $isEdit = $wallet !== null;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(array_keys(Wallet::TYPES))],
            'balance' => [Rule::requiredIf(! $isCreditCard && ! $isEdit), 'nullable', 'numeric'],
            'credit_limit' => [Rule::requiredIf($isCreditCard), 'nullable', 'numeric', 'min:0'],
            'statement_day' => [Rule::requiredIf($isCreditCard), 'nullable', 'integer', 'min:1', 'max:31'],
            'payment_day' => [Rule::requiredIf($isCreditCard), 'nullable', 'integer', 'min:1', 'max:31'],
            'outstanding_balance' => [Rule::requiredIf($isCreditCard && ! $isEdit), 'nullable', 'numeric', 'min:0'],
            'notes' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $data = [
            'name' => $validated['name'],
            'type' => $validated['type'],
            'notes' => $validated['notes'] ?? null,
        ];

        if ($wallet) {
            $data['is_active'] = $request->boolean('is_active');
        } else {
            $data['is_active'] = true;
        }

        if ($isCreditCard) {
            $limit = (float) $validated['credit_limit'];
            $data['credit_limit'] = $limit;
            $data['statement_day'] = (int) $validated['statement_day'];
            $data['payment_day'] = (int) $validated['payment_day'];

            if (! $isEdit) {
                $outstanding = (float) $validated['outstanding_balance'];
                if ($outstanding > $limit) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'outstanding_balance' => 'Dư nợ không được vượt hạn mức.',
                    ]);
                }
                $data['outstanding_balance'] = $outstanding;
                $data['balance'] = max(0, $limit - $outstanding);
            }
        } elseif (! $isEdit) {
            $data['balance'] = (float) ($validated['balance'] ?? 0);
            $data['credit_limit'] = null;
            $data['statement_day'] = null;
            $data['payment_day'] = null;
            $data['outstanding_balance'] = null;
        } elseif ($isEdit && $wallet->isCreditCard() && ! $isCreditCard) {
            $data['credit_limit'] = null;
            $data['statement_day'] = null;
            $data['payment_day'] = null;
            $data['outstanding_balance'] = null;
        }

        return $data;
    }
}
