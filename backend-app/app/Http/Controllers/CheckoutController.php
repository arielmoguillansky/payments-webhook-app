<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PaymentGatewayService;

class CheckoutController extends Controller
{
    public function initiatePayment(Request $request, PaymentGatewayService $gateway)
    {
        $validated = $request->validate([
            'card_number' => 'required|string',
            'exp_month' => 'required|string',
            'exp_year' => 'required|string',
            'cvc' => 'required|string',
            'amount' => 'required|numeric',
            'currency' => 'required|string',
        ]);

        // Simulated authenticated user id
        $userId = 'user_1';

        $token = $gateway->tokenize([
            'card_number' => $validated['card_number'],
            'exp_month' => $validated['exp_month'],
            'exp_year' => $validated['exp_year'],
            'cvc' => $validated['cvc']
        ]);

        if (!$token) {
            return response()->json(['error' => 'Failed to tokenize card'], 400);
        }

        $success = $gateway->charge($token, $validated['amount'], $validated['currency'], $userId);

        if (!$success) {
            return response()->json(['error' => 'Failed to initiate charge'], 400);
        }

        return response()->json(['message' => 'Payment initiated. Awaiting webhook for confirmation.']);
    }
}
