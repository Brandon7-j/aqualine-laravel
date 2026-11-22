<?php

namespace App\Http\Controllers;

use App\Models\Garment;
use App\Models\Order;
use App\Models\ServiceItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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

        $orders = Order::with(['customer', 'garments.serviceItem', 'bill.payments.receipt'])
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

        // Exclude pickups already converted into orders so they only live under Orders now
        $allPickups = TrackingController::getFlattenedPickupRequests();
        $staffPickups = array_values(array_filter(
            $allPickups,
            fn ($p) => empty($p['converted_order_no'])
        ));

        $collectedPickups = array_values(array_filter(
            $staffPickups,
            fn ($p) => ($p['status'] ?? '') === 'collected'
        ));

        return view('staff.dashboard', compact(
            'customers',
            'services',
            'orders',
            'search',
            'staffPickups',
            'collectedPickups'
        ));
    }

    public function storeCustomer(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:120',
            'phone'     => 'required|string|max:30|unique:users,phone',
            'email'     => 'nullable|email|max:120|unique:users,email',
            'password'  => 'required|string|min:6|max:100',
        ]);

        $phoneClean = trim($validated['phone']);
        $baseUsername = preg_replace('/[^a-zA-Z0-9_]/', '', $phoneClean) ?: 'cust_' . time();
        $username = $baseUsername;
        $counter = 1;
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . '_' . $counter++;
        }

        User::create([
            'full_name'     => trim($validated['full_name']),
            'phone'         => $phoneClean,
            'email'         => !empty($validated['email']) ? trim($validated['email']) : null,
            'username'      => $username,
            'password_hash' => Hash::make($validated['password']),
            'role'          => 'customer',
            'status'        => 'active',
        ]);

        return redirect()
            ->route('staff.dashboard')
            ->with('success', 'Customer registered successfully.');
    }

    public function showCustomer($userId)
    {
        $customer = User::where('role', 'customer')
            ->where('user_id', $userId)
            ->firstOrFail();

        $customerOrders = Order::with(['garments.serviceItem', 'bill'])
            ->where('customer_id', $customer->user_id)
            ->orderByDesc('date_received')
            ->orderByDesc('order_no')
            ->get();

        return view('staff.customer.show', compact('customer', 'customerOrders'));
    }

    public function updateCustomer(Request $request, $userId)
    {
        $customer = User::where('role', 'customer')
            ->where('user_id', $userId)
            ->firstOrFail();

        $validated = $request->validate([
            'full_name' => 'required|string|max:120',
            'phone'     => 'required|string|max:30|unique:users,phone,' . $customer->user_id . ',user_id',
            'email'     => 'nullable|email|max:120|unique:users,email,' . $customer->user_id . ',user_id',
            'status'    => 'nullable|in:active,inactive',
        ]);

        $customer->update([
            'full_name' => trim($validated['full_name']),
            'phone'     => trim($validated['phone']),
            'email'     => !empty($validated['email']) ? trim($validated['email']) : null,
            'status'    => $validated['status'] ?? ($customer->status ?? 'active'),
        ]);

        return redirect()
            ->route('staff.customers.show', $customer->user_id)
            ->with('success', 'Customer details updated.');
    }

    public function resetCustomerPassword(Request $request, $userId)
    {
        $customer = User::where('role', 'customer')
            ->where('user_id', $userId)
            ->firstOrFail();

        $validated = $request->validate([
            'password' => 'required|string|min:6|max:100',
        ]);

        $customer->update([
            'password_hash' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('staff.customers.show', $customer->user_id)
            ->with('success', 'Customer password reset successfully.');
    }

    public function destroyCustomer($userId)
    {
        $customer = User::where('role', 'customer')
            ->where('user_id', $userId)
            ->firstOrFail();

        $hasOrders = Order::where('customer_id', $customer->user_id)->exists();
        if ($hasOrders) {
            return redirect()
                ->route('staff.customers.show', $customer->user_id)
                ->withErrors(['delete' => 'Cannot remove a customer who already has orders in the system.']);
        }

        $name = $customer->full_name;
        $customer->delete();

        $allPickups = TrackingController::loadAllPickupRequests();
        $key = (string) $customer->user_id;
        if (isset($allPickups[$key])) {
            unset($allPickups[$key]);
            TrackingController::saveAllPickupRequests($allPickups);
        }

        return redirect()
            ->route('staff.dashboard')
            ->with('success', "Fake/unused customer account \"{$name}\" removed.");
    }

    public function storeOrder(Request $request)
    {
        $allPickups = TrackingController::loadAllPickupRequests();
        $collectedEntries = [];

        foreach ($allPickups as $custKey => $list) {
            if (!is_array($list)) {
                continue;
            }
            foreach ($list as $idx => $pkp) {
                if (
                    is_array($pkp)
                    && (($pkp['status'] ?? '') === 'collected')
                    && empty($pkp['converted_order_no'])
                ) {
                    $collectedEntries[] = [
                        'customer_key' => (string) $custKey,
                        'index'        => $idx,
                        'data'         => $pkp,
                    ];
                }
            }
        }

        if (empty($collectedEntries)) {
            return back()
                ->withInput()
                ->withErrors(['pickup' => 'No unconverted Collected pickup found. Mark a pickup as Collected first before generating an order.']);
        }

        $validated = $request->validate([
            'pickup_id'           => 'required|string',
            'expected_ready_date' => 'nullable|date',
            'quantity'            => 'nullable|integer|min:1|max:500',
        ], [
            'pickup_id.required' => 'Please select a Collected pickup from Confirm & Update Customer Pickups.',
        ]);

        $matchedEntry = null;
        foreach ($collectedEntries as $entry) {
            if (($entry['data']['id'] ?? '') === $validated['pickup_id']) {
                $matchedEntry = $entry;
                break;
            }
        }

        if (!$matchedEntry) {
            return back()
                ->withInput()
                ->withErrors(['pickup' => 'Selected pickup is not marked as Collected or has already been recorded as an order.']);
        }

        $pickupData = $matchedEntry['data'];
        $defaultService = ServiceItem::where('is_active', 1)->first();

        $garmentItems = [];
        if (!empty($pickupData['items']) && is_array($pickupData['items'])) {
            $overrideTotalQty = !empty($validated['quantity']) ? (int) $validated['quantity'] : null;
            $itemCount = count($pickupData['items']);

            foreach ($pickupData['items'] as $i => $it) {
                $srv = ServiceItem::where('service_name', $it['service'] ?? '')->first() ?? $defaultService;
                if (!$srv) {
                    continue;
                }

                $qty = max(1, (int) ($it['quantity'] ?? 1));
                if ($overrideTotalQty !== null && $itemCount === 1) {
                    $qty = max(1, $overrideTotalQty);
                }

                $garmentItems[] = [
                    'service_id'     => $srv->service_id,
                    'garment_type'   => trim((string) ($it['service'] ?? 'Laundry Item')),
                    'colour'         => null,
                    'quantity'       => $qty,
                    'condition_note' => 'Collected pickup ' . ($pickupData['id'] ?? ''),
                ];
            }
        }

        if (empty($garmentItems) && $defaultService) {
            $garmentItems[] = [
                'service_id'     => $defaultService->service_id,
                'garment_type'   => trim((string) ($pickupData['service'] ?? $defaultService->service_name)),
                'colour'         => null,
                'quantity'       => max(1, (int) ($validated['quantity'] ?? $pickupData['quantity'] ?? 1)),
                'condition_note' => 'Collected pickup ' . ($pickupData['id'] ?? ''),
            ];
        }

        if (empty($garmentItems)) {
            return back()
                ->withInput()
                ->withErrors(['garments' => 'Could not determine garments from the selected collected pickup.']);
        }

        $staff = Auth::user();
        $staffId = $staff->user_id ?? $staff->id;
        $pickupCustomerId = (int) ($pickupData['customer_id'] ?? 0);

        if ($pickupCustomerId <= 0 || !User::where('user_id', $pickupCustomerId)->exists()) {
            return back()
                ->withInput()
                ->withErrors(['customer' => 'Customer linked to this collected pickup was not found.']);
        }

        $order = DB::transaction(function () use ($validated, $staffId, $pickupCustomerId, $garmentItems, &$allPickups, $matchedEntry) {
            $order = Order::create([
                'customer_id'         => $pickupCustomerId,
                'staff_id'            => $staffId,
                'date_received'       => now(),
                'expected_ready_date' => $validated['expected_ready_date'] ?? null,
                'status'              => 'received',
            ]);

            foreach ($garmentItems as $index => $item) {
                $tagNo = 'TAG-' . $order->order_no . '-' . ($index + 1) . '-' . strtoupper(substr(uniqid(), -4));

                Garment::create([
                    'order_no'       => $order->order_no,
                    'service_id'     => $item['service_id'],
                    'tag_no'         => $tagNo,
                    'garment_type'   => $item['garment_type'],
                    'colour'         => $item['colour'],
                    'quantity'       => $item['quantity'],
                    'condition_note' => $item['condition_note'],
                    'status'         => 'received',
                ]);
            }

            $cKey = $matchedEntry['customer_key'];
            $pIdx = $matchedEntry['index'];
            if (isset($allPickups[$cKey][$pIdx])) {
                $allPickups[$cKey][$pIdx]['order_no'] = $order->order_no;
                $allPickups[$cKey][$pIdx]['converted_order_no'] = $order->order_no;
                $allPickups[$cKey][$pIdx]['converted_at'] = now()->format('d M Y, H:i');
                TrackingController::saveAllPickupRequests($allPickups);
            }

            return $order;
        });

        return redirect()
            ->route('staff.dashboard')
            ->with('success', "Order #{$order->order_no} created under Orders and moved out of pickup requests.");
    }

    public function destroyOrder($orderId)
    {
        $order = Order::with(['bill.payments.receipt', 'garments'])
            ->where('order_no', $orderId)
            ->firstOrFail();

        $bill = $order->bill;
        $hasPayment = ($bill && (float) ($bill->amount_paid ?? 0) > 0)
            || ($bill && ($bill->payment_status ?? '') === 'paid')
            || ($bill && $bill->payments && $bill->payments->where('status', 'paid')->isNotEmpty());

        if ($hasPayment) {
            return redirect()
                ->route('staff.dashboard')
                ->withErrors(['delete' => "Order #{$order->order_no} already has payment recorded and cannot be deleted."]);
        }

        DB::transaction(function () use ($order) {
            Garment::where('order_no', $order->order_no)->delete();
            if ($order->bill) {
                $order->bill->payments()->delete();
                $order->bill->delete();
            }
            $order->delete();
        });

        return redirect()
            ->route('staff.dashboard')
            ->with('success', "Unpaid Order #{$order->order_no} has been deleted.");
    }
}