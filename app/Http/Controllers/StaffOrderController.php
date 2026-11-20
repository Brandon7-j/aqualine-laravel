<?php

namespace App\Http\Controllers;

use App\Models\Garment;
use App\Models\Order;
use App\Models\ServiceItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StaffOrderController extends Controller
{
    public function dashboard(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        $customers = User::where('role', 'customer')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('full_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('user_id')
            ->get();

        $services = ServiceItem::where('is_active', 1)
            ->orderBy('service_name')
            ->get();

        $orders = Order::with(['customer', 'garments.serviceItem', 'bill'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where('order_no', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($sub) use ($search) {
                        $sub->where('full_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('garments', function ($sub) use ($search) {
                        $sub->where('tag_no', 'like', "%{$search}%")
                            ->orWhere('garment_type', 'like', "%{$search}%");
                    });
            })
            ->orderByDesc('date_received')
            ->get();

        return view('staff.dashboard', compact('customers', 'services', 'orders', 'search'));
    }

    public function storeOrder(Request $request)
    {
        $validated = $request->validate([
            'customer_id'         => 'required|integer|exists:users,user_id',
            'expected_ready_date' => 'nullable|date',
            'garments'            => 'required|array|min:1',
            'garments.*.service_id'     => 'required|integer|exists:service_items,service_id',
            'garments.*.garment_type'   => 'required|string|max:120',
            'garments.*.colour'         => 'nullable|string|max:60',
            'garments.*.quantity'       => 'required|integer|min:1',
            'garments.*.condition_note' => 'nullable|string|max:255',
        ]);

        $staff = Auth::user();
        $staffId = $staff->user_id ?? $staff->id;

        $order = DB::transaction(function () use ($validated, $staffId) {
            $order = Order::create([
                'customer_id'         => $validated['customer_id'],
                'staff_id'            => $staffId,
                'date_received'       => now(),
                'expected_ready_date' => $validated['expected_ready_date'] ?? null,
                'status'              => 'received',
            ]);

            foreach ($validated['garments'] as $index => $item) {
                $tagNo = 'TAG-' . $order->order_no . '-' . ($index + 1) . '-' . strtoupper(substr(uniqid(), -4));

                Garment::create([
                    'order_no'       => $order->order_no,
                    'service_id'     => $item['service_id'],
                    'tag_no'         => $tagNo,
                    'garment_type'   => trim($item['garment_type']),
                    'colour'         => !empty($item['colour']) ? trim($item['colour']) : null,
                    'quantity'       => $item['quantity'],
                    'condition_note' => !empty($item['condition_note']) ? trim($item['condition_note']) : null,
                    'status'         => 'received',
                ]);
            }

            return $order;
        });

        return redirect()
            ->route('staff.dashboard')
            ->with('success', "Order #{$order->order_no} created successfully.");
    }
}