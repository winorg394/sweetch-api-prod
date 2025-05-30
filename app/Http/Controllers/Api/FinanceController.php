<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use NYCorp\Finance\Http\Payment\DefaultPaymentProvider;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class FinanceController extends Controller
{
    public function deposit(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'isSelfRecharge' => 'boolean',
            'phone' => 'nullable|string',
        ]);

        if (($validated['isSelfRecharge'] ?? false)) {
            $recipient = User::where('phone', $validated['phone'])->first();

            if (!$recipient) {
                return $this->reply(false, 'Recipient not found', null, 404);
            }

            return $recipient->deposit(
                DefaultPaymentProvider::getId(),
                $validated['amount'],
                $validated['description'] ?? ''
            );
        }

        return $request->user()->deposit(
            DefaultPaymentProvider::getId(),
            $validated['amount'],
            $validated['description'] ?? ''
        );
    }

    public function getBalance(Request $request)
    {
        return $this->reply(
            true,
            'Balance retrieved successfully',
            ['balance' => auth()->user()->balance,
            'currency' => auth()->user()->getCurrency(),
            ]
        );
    }

    public function getUserTransaction()
    {
        return $this->reply(
            true,
            'Balance retrieved successfully',
            Finance::getUserTransaction(auth()->user()->id)
        );
    }
}
