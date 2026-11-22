<?php

namespace App\Http\Controllers;

use App\Models\Garment;
use App\Models\Order;
use App\Models\ServiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrackingController extends Controller
{
    public static function pickupStoragePath(): string
    {
        return storage_path('app/aqualine_pickup_requests.json');
    }

    public static function loadAllPickupRequests(): array
    {
        $path = self::pickupStoragePath();
        if (!is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        return is_array($decoded) ? $decoded : [];
    }

    public static function saveAllPickupRequests(array $all): void
    {
        $dir = dirname(self::pickupStoragePath());
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents(
            self::pickupStoragePath(),
            json_encode($all, JSON_PRETTY_PRINT),
            LOCK_EX
        );
    }

    public static function getFlattenedPickupRequests(): array
    {
        $all = self::loadAllPickupRequests();
        $flat = [];

        foreach ($all as $customerId => $items) {
            if (!is_array($items)) {
                continue;
            }
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $item['customer_id'] = $item['customer_id'] ?? $customerId;
                $flat[] = $item;
            }
        }

        usort($flat, function ($a, $b) {
            return strcmp((string) ($b['id'] ?? ''), (string) ($a['id'] ?? ''));
        });

        return $flat;
    }

    private function getPickupRequests(int|string $customerId): array
    {
        $all = self::loadAllPickupRequests();
        return $all[(string) $customerId] ?? [];
    }

    private function savePickupRequest(int|string $customerId, array $entry): void
    {
        $all = self::loadAllPickupRequests();
        $key = (string) $customerId;
        $list = $all[$key] ?? [];
        array_unshift($list, $entry);
        $all[$key] = $list;
        self::saveAllPickupRequests($all);
    }

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

        $pickupRequests = $this->getPickupRequests($customerId);
        $deliveryRequests = $orders->where('delivery_status', 'requested');

        return view('customer.dashboard', compact(
            'orders',
            'services',
            'pickupRequests',
            'deliveryRequests'
        ));
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

    public function pickupForm(Request $request)
    {
        $user = Auth::user();
        $customerId = $user->user_id ?? $user->id;
        $selectedService = $request->query('service');

        $orders = Order::where('customer_id', $customerId)
            ->orderByDesc('date_received')
            ->orderByDesc('order_no')
            ->get();

        $services = ServiceItem::where('is_active', 1)
            ->orderBy('service_name')
            ->get();

        $pickupRequests = $this->getPickupRequests($customerId);
        $deliveryRequests = $orders->where('delivery_status', 'requested');

        return view('customer.pickup', compact(
            'orders',
            'services',
            'pickupRequests',
            'deliveryRequests',
            'selectedService'
        ));
    }

    public function storePickup(Request $request)
    {
        $user = Auth::user();
        $customerId = $user->user_id ?? $user->id;

        $validated = $request->validate([
            'pickup_address'      => 'required|string|max:255',
            'pickup_date'         => 'required|date',
            'pickup_notes'        => 'nullable|string|max:500',
            'order_no'            => 'nullable|integer',
            'items'               => 'required|array|min:1',
            'items.*.service'     => 'required|string|max:120',
            'items.*.quantity'    => 'required|integer|min:1|max:20',
        ], [
            'items.required'            => 'Please select at least one service / laundry item.',
            'items.*.service.required'  => 'Please choose a service for each item row.',
            'items.*.quantity.required' => 'Please enter a quantity for each item.',
            'items.*.quantity.integer'  => 'Quantity must be a whole number.',
            'items.*.quantity.min'      => 'Quantity must be at least 1.',
            'items.*.quantity.max'      => 'Quantity cannot be greater than 20 (maximum is 20 per item).',
        ]);

        $matchedOrder = null;
        if (!empty($validated['order_no'])) {
            $matchedOrder = Order::where('order_no', $validated['order_no'])
                ->where('customer_id', $customerId)
                ->firstOrFail();

            $matchedOrder->update([
                'delivery_address' => trim($validated['pickup_address']),
                'delivery_date'    => $validated['pickup_date'],
                'delivery_notes'   => !empty($validated['pickup_notes']) ? trim($validated['pickup_notes']) : null,
                'delivery_status'  => 'pickup_requested',
            ]);
        }

        $servicePriceMap = ServiceItem::where('is_active', 1)
            ->pluck('unit_price', 'service_name');

        $normalizedItems = [];
        $totalItems = 0;
        $estimatedTotal = 0.0;

        foreach ($validated['items'] as $row) {
            $serviceName = trim($row['service']);
            $qty = min(20, max(1, (int) $row['quantity']));
            $unitPrice = (float) ($servicePriceMap[$serviceName] ?? 0.0);
            $lineTotal = $unitPrice * $qty;

            $normalizedItems[] = [
                'service'         => $serviceName,
                'quantity'        => $qty,
                'unit_price'      => $unitPrice,
                'estimated_total' => $lineTotal,
            ];

            $totalItems += $qty;
            $estimatedTotal += $lineTotal;
        }

        $summaryLabel = collect($normalizedItems)
            ->map(fn ($it) => "{$it['service']} (×{$it['quantity']})")
            ->implode(', ');

        $this->savePickupRequest($customerId, [
            'id'              => uniqid('PKP-'),
            'customer_id'     => $customerId,
            'customer_name'   => $user->full_name ?? $user->name ?? 'Customer',
            'customer_phone'  => $user->phone ?? $user->phone_number ?? '',
            'service'         => $summaryLabel,
            'quantity'        => $totalItems,
            'items'           => $normalizedItems,
            'estimated_total' => $estimatedTotal,
            'pickup_address'  => trim($validated['pickup_address']),
            'pickup_date'     => $validated['pickup_date'],
            'pickup_notes'    => !empty($validated['pickup_notes']) ? trim($validated['pickup_notes']) : null,
            'order_no'        => $matchedOrder?->order_no,
            'status'          => 'pickup_requested',
            'created_at'      => now()->format('d M Y, H:i'),
        ]);

        return redirect()
            ->route('customer.pickup.form')
            ->with('success', "Pickup request saved: {$summaryLabel}.");
    }

    public function updatePickupStatus(Request $request, string $pickupId)
    {
        $validated = $request->validate([
            'customer_id' => 'required',
            'status'      => 'required|in:pickup_requested,arranging_pickup,on_the_road,collected',
            'staff_note'  => 'nullable|string|max:255',
        ]);

        $all = self::loadAllPickupRequests();
        $customerKey = (string) $validated['customer_id'];

        if (!empty($all[$customerKey])) {
            foreach ($all[$customerKey] as &$item) {
                if (($item['id'] ?? '') === $pickupId) {
                    $item['status'] = $validated['status'];
                    $item['staff_note'] = !empty($validated['staff_note']) ? trim($validated['staff_note']) : ($item['staff_note'] ?? null);
                    $item['updated_at'] = now()->format('d M Y, H:i');
                    break;
                }
            }
            unset($item);
            self::saveAllPickupRequests($all);
        }

        return redirect()
            ->route('staff.dashboard')
            ->with('success', "Pickup {$pickupId} updated to " . ucwords(str_replace('_', ' ', $validated['status'])) . '.');
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