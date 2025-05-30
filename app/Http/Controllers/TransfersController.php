<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use NYCorp\Finance\Http\Payment\DefaultPaymentProvider;

class TransfersController extends Controller
{


    public function transfer(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'recipient_phone' => 'required|string',
            'country_code' => 'required|string',
            'payment_method' => 'required|string',
            'add_supplement' => 'boolean',
        ]);

        // Find recipient
        $recipient = User::where('phone', $validated['recipient_phone'])->first();

        if (!$recipient) {
            return $this->reply(false, 'Recipient not found', null, 404);
        }

        // Debug auth user
        $sender = auth()->user();
        if (!$sender) {
            return $this->reply(false, 'Unauthorized: User not authenticated', null, 401);
        }

        // Check if sender has sufficient balance
        $sender = \Illuminate\Support\Facades\Auth::user();
       // return $sender;
        if ($sender->balance < $validated['amount']) {
            return $this->reply(false, 'Insufficient balance', null, 400);
        }

        try {
            // Deduct from sender
            $sender->withdrawal(
                DefaultPaymentProvider::getId(),
                $validated['amount'],
                $validated['description'] ?? 'Transfer to ' . $recipient->phone
            );

            // Add to recipient
            $recipient->deposit(
                DefaultPaymentProvider::getId(),
                $validated['amount'],
                $validated['description'] ?? 'Transfer from ' . $sender->phone
            );

            return $this->reply(true, 'Transfer successful', [
                'transaction_id' => uniqid('TRX'),
                'amount' => $validated['amount'],
                'recipient' => [
                    'phone' => $recipient->phone,
                    'name' => $recipient->profile->name ?? 'Unknown',
                ],
                'payment_method' => $validated['payment_method'],
                'country' => $validated['country_code'],
                'date' => now(),
            ]);

        } catch (\Exception $e) {
            return $this->reply(false, 'Transfer failed: ' . $e->getMessage(), null, 500);
        }
    }


}
