<?php

namespace App\Http\Controllers;

use App\Models\Garment;
use App\Models\Order;
use App\Models\ServiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrackingController extends Controller
{
    public function customerDashboard()
    {
        $user = Auth::user();
        $customerId = $user->user_id ?? $user->id;

        $orders = Order::with([
                'garments.serviceItem',
                'bill.payments.receipt',
            ])
            ->where('customer_id', $customerId)
            ->orderByDesc('date_received')
            ->orderByDesc('order_no')
            ->get();

        $services = ServiceItem::where('is_active', 1)
            ->orderBy('service_name')
            ->get();

        return view('customer.dashboard', compact('orders', 'services'));
    }

    public function deliveryForm()
    {
        $user = Auth::user();
        $customerId = $user->user_id ?? $user->id;

        $orders = Order::where('customer_id', $customerId)
            ->orderByDesc('date_received')
            ->orderByDesc('order_no')
            ->get();

        return view('customer.delivery', compact('orders'));
    }

    public function showDeliveryForm()
    {
        return $this->deliveryForm();
    }

    public function storeDelivery(Request $request)
    {
        $user = Auth::user();
        $customerId = $user->user_id ?? $user->id;

        $validated = $request->validate([
            'order_no'         => 'required|integer',
            'delivery_address' => 'required|string|max:255',
            'delivery_date'    => 'required|date',
            'delivery_notes'   => 'nullable|string|max:500',
        ]);

        $order = Order::where('order_no', $validated['order_no'])
            ->where('customer_id', $customerId)
            ->firstOrFail();

        $order->update([
            'delivery_address' => trim($validated['delivery_address']),
            'delivery_date'    => $validated['delivery_date'],
            'delivery_notes'   => !empty($validated['delivery_notes']) ? trim($validated['delivery_notes']) : null,
            'delivery_status'  => 'requested',
        ]);

        return redirect()
            ->route('customer.dashboard')
            ->with('success', "Delivery request saved for Order #{$order->order_no}.");
    }

    public function updateGarmentStatus(Request $request, $garmentId)
    {
        $validated = $request->validate([
            'status' => 'required|in:received,washing,ironing,in_process,ready,collected',
        ]);

        $garment = Garment::where('garment_id', $garmentId)->firstOrFail();
        $garment->update([
            'status' => $validated['status'],
        ]);

        $order = Order::with('garments')->where('order_no', $garment->order_no)->first();
        if ($order && $order->garments->isNotEmpty()) {
            $statuses = $order->garments->pluck('status');

            if ($statuses->every(fn ($st) => $st === 'collected')) {
                $orderStatus = 'collected';
            } elseif ($statuses->every(fn ($st) => in_array($st, ['ready', 'collected'], true))) {
                $orderStatus = 'ready';
            } elseif ($statuses->contains(fn ($st) => in_array($st, ['washing', 'ironing', 'in_process', 'ready', 'collected'], true))) {
                $orderStatus = 'in_process';
            } else {
                $orderStatus = 'received';
            }

            $order->update(['status' => $orderStatus]);
        }

        return redirect()
            ->route('staff.dashboard')
            ->with('success', "Garment #{$garment->tag_no} status updated to " . ucfirst(str_replace('_', ' ', $validated['status'])) . '.');
    }
}