<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class MpesaController extends Controller
{
    private function baseUrl(): string
    {
        return config('services.mpesa.env') === 'live'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    private function getAccessToken(): ?string
    {
        $response = Http::withBasicAuth(
            config('services.mpesa.consumer_key'),
            config('services.mpesa.consumer_secret')
        )->get($this->baseUrl() . '/oauth/v1/generate?grant_type=client_credentials');

        return $response->json('access_token');
    }

    // Format Kenyan phone numbers (07XX... or +2547XX...) to 2547XX...
    private function formatPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($digits, '0')) {
            return '254' . substr($digits, 1);
        }
        return $digits;
    }

    // Staff triggers STK Push to customer's phone
    public function stkPush(Request $request, $billId)
    {
        $validated = $request->validate([
            'phone' => 'required|string|min:10|max:15',
            'amount' => 'required|numeric|min:1',
        ]);

        $bill = Bill::with('order')->findOrFail($billId);
        $balance = $bill->total_amount - $bill->amount_paid;

        if ($validated['amount'] > $balance) {
            return back()->withErrors(['amount' => "Amount exceeds balance of KES " . number_format($balance, 2)]);
        }

        $token = $this->getAccessToken();
        if (!$token) {
            return back()->withErrors(['mpesa' => 'Failed to authenticate with Safaricom Daraja API. Check your keys.']);
        }

        $timestamp = date('YmdHis');
        $shortcode = config('services.mpesa.shortcode');
        $passkey = config('services.mpesa.passkey');
        $password = base64_encode($shortcode . $passkey . $timestamp);
        $phone = $this->formatPhone($validated['phone']);

        $response = Http::withToken($token)->post($this->baseUrl() . '/mpesa/stkpush/v1/processrequest', [
            'BusinessShortCode' => $shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => (int) round($validated['amount']),
            'PartyA' => $phone,
            'PartyB' => $shortcode,
            'PhoneNumber' => $phone,
            'CallBackURL' => config('services.mpesa.callback_url'),
            'AccountReference' => 'Aqualine-' . $bill->order->order_no,
            'TransactionDesc' => 'Laundry Payment Order #' . $bill->order->order_no,
        ]);

        $data = $response->json();

        if (($data['ResponseCode'] ?? null) === '0') {
            $bill->checkout_request_id = $data['CheckoutRequestID'];
            $bill->save();

            return back()->with('success', "M-Pesa STK Push sent to {$phone}! Ask customer to enter their M-Pesa PIN.");
        }

        return back()->withErrors(['mpesa' => 'STK Push failed: ' . ($data['errorMessage'] ?? $data['ResponseDescription'] ?? 'Unknown error')]);
    }

    // Safaricom Daraja Callback URL (receives payment confirmation)
    public function callback(Request $request)
    {
        $stkCallback = $request->input('Body.stkCallback');
        if (!$stkCallback) {
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        $checkoutRequestId = $stkCallback['CheckoutRequestID'] ?? null;
        $resultCode = $stkCallback['ResultCode'] ?? 1;

        if ($resultCode == 0 && $checkoutRequestId) {
            $bill = Bill::where('checkout_request_id', $checkoutRequestId)->first();

            if ($bill) {
                $items = collect($stkCallback['CallbackMetadata']['Item'] ?? []);
                $amount = (float) ($items->firstWhere('Name', 'Amount')['Value'] ?? 0);
                $mpesaReceipt = $items->firstWhere('Name', 'MpesaReceiptNumber')['Value'] ?? 'DARAJA-TXN';

                DB::transaction(function () use ($bill, $amount, $mpesaReceipt) {
                    // Prevent duplicate callback processing
                    if (Payment::where('mpesa_ref', $mpesaReceipt)->exists()) {
                        return;
                    }

                    Payment::create([
                        'bill_id' => $bill->bill_id,
                        'amount' => $amount,
                        'payment_method' => 'mpesa',
                        'mpesa_ref' => $mpesaReceipt,
                        'received_by' => $bill->order->staff_id,
                    ]);

                    $bill->amount_paid += $amount;
                    $bill->payment_status = $bill->amount_paid >= $bill->total_amount ? 'paid' : 'partial';
                    $bill->save();

                    if ($bill->payment_status === 'paid' && !$bill->receipt) {
                        Receipt::create([
                            'bill_id' => $bill->bill_id,
                            'receipt_no' => 'RCP-' . date('Ymd') . '-' . str_pad($bill->bill_id, 4, '0', STR_PAD_LEFT),
                        ]);
                    }
                });
            }
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Success']);
    }
}