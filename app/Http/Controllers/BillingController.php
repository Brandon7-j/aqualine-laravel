<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BillingController extends Controller
{
    public function generateBill($orderId)
    {
        $order = Order::with(['garments.serviceItem', 'bill'])->findOrFail($orderId);

        if ($order->bill) {
            return back()->withErrors(['bill' => "A bill already exists for Order #{$order->order_no}."]);
        }

        if ($order->garments->isEmpty()) {
            return back()->withErrors(['bill' => 'Cannot generate a bill for an order with no garments.']);
        }

        $totalAmount = $order->garments->sum(function ($garment) {
            $unitPrice = (float) ($garment->serviceItem->unit_price ?? 0);
            return $garment->quantity * $unitPrice;
        });

        Bill::create([
            'order_no' => $order->order_no,
            'total_amount' => $totalAmount,
            'amount_paid' => 0,
            'payment_status' => 'unpaid',
            'generated_on' => now(),
        ]);

        return back()->with('success', "Bill generated for Order #{$order->order_no} (KES " . number_format($totalAmount, 2) . ").");
    }

    public function recordPayment(Request $request, $billId)
    {
        $request->validate([
            'amount' => 'required|numeric|gt:0',
            'payment_method' => 'nullable|string|in:cash,mpesa',
        ]);

        $bill = Bill::with('receipt')->findOrFail($billId);
        $totalBill = (float) ($bill->total_amount ?? $bill->amount_due);
        $currentBalance = max(0, $totalBill - (float) $bill->amount_paid);

        if ($bill->payment_status === 'paid' || $currentBalance <= 0) {
            return back()->withErrors(['payment' => 'This bill is already fully paid.']);
        }

        $amountToPay = (float) $request->amount;
        if ($amountToPay > $currentBalance) {
            return back()->withErrors(['payment' => "Payment amount exceeds the remaining balance of KES " . number_format($currentBalance, 2) . "."]);
        }

        DB::transaction(function () use ($bill, $amountToPay, $totalBill, $request) {
            $method = $request->input('payment_method', 'cash');

            Payment::create([
                'bill_id' => $bill->bill_id,
                'amount' => $amountToPay,
                'payment_method' => $method,
                'status' => 'paid',
                'received_by' => Auth::id(),
                'paid_at' => now(),
            ]);

            $newAmountPaid = (float) $bill->amount_paid + $amountToPay;
            $newBalance = max(0, $totalBill - $newAmountPaid);

            $bill->amount_paid = $newAmountPaid;
            $bill->payment_status = $newBalance <= 0 ? 'paid' : 'partial';
            $bill->save();

            if ($newBalance <= 0 && !$bill->receipt) {
                $receiptNo = 'RCT-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4));

                Receipt::create([
                    'receipt_no' => $receiptNo,
                    'bill_id' => $bill->bill_id,
                    'issued_on' => now(),
                ]);
            }
        });

        return back()->with('success', "Payment of KES " . number_format($amountToPay, 2) . " recorded successfully.");
    }

        public function destroyPayment($paymentId)
{
    DB::transaction(function () use ($paymentId) {
        $payment = Payment::where('payment_id', $paymentId)->firstOrFail();
        $bill = Bill::lockForUpdate()->findOrFail($payment->bill_id);

        $bill->amount_paid = max(0, (float) $bill->amount_paid - (float) $payment->amount);

        if ($bill->amount_paid <= 0) {
            $bill->payment_status = 'unpaid';
        } elseif ($bill->amount_paid < (float) $bill->total_amount) {
            $bill->payment_status = 'partial';
        } else {
            $bill->payment_status = 'paid';
        }

        $bill->save();

        if ($bill->payment_status !== 'paid') {
            Receipt::where('bill_id', $bill->bill_id)->delete();
        }

        $payment->delete();
    });

    return back()->with('success', 'Payment deleted and bill balance recalculated.');
}

    public function showReceipt($receiptId)
    {
        $decodedId = urldecode((string) $receiptId);

$receipt = Receipt::with(['bill.order.customer', 'bill.order.garments.serviceItem', 'bill.payments'])
    ->where('receipt_id', $decodedId)
    ->orWhere('receipt_no', $decodedId)
    ->orWhere('bill_id', $decodedId)
    ->firstOrFail();

        if (Auth::user()->role === 'customer' && $receipt->bill->order->customer_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this receipt.');
        }

        return view('receipts.show', compact('receipt'));
    }
}
