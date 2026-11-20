<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ServiceItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OwnerReportController extends Controller
{
    public function dashboard(Request $request)
    {
        $startDate = $request->input('from', $request->input('start_date', now()->startOfMonth()->toDateString()));
        $endDate = $request->input('to', $request->input('end_date', now()->toDateString()));
        $from = $startDate;
        $to = $endDate;
        $statusFilter = $request->input('status');
        $customerId = $request->input('customer_id');

        $totalOrders = Order::whereBetween(DB::raw('DATE(date_received)'), [$startDate, $endDate])->count();
        $ordersCount = $totalOrders;

        $completedOrders = Order::whereBetween(DB::raw('DATE(date_received)'), [$startDate, $endDate])
            ->whereIn('status', ['ready', 'collected'])
            ->count();

        // FR24: Daily / Period Sales Report
        $totalSales = Payment::where('status', 'paid')
            ->whereBetween(DB::raw('DATE(paid_at)'), [$startDate, $endDate])
            ->sum('amount');
        $totalRevenue = $totalSales;

        $cashSales = Payment::where('status', 'paid')
            ->whereBetween(DB::raw('DATE(paid_at)'), [$startDate, $endDate])
            ->where('payment_method', 'cash')
            ->sum('amount');
        $cashRevenue = $cashSales;

        $mpesaSales = Payment::where('status', 'paid')
            ->whereBetween(DB::raw('DATE(paid_at)'), [$startDate, $endDate])
            ->where('payment_method', 'mpesa')
            ->sum('amount');
        $mpesaRevenue = $mpesaSales;

        // FR25: Outstanding Balances Report (computed from total_amount - amount_paid)
        $outstandingBalance = Bill::where('payment_status', '!=', 'paid')
            ->sum(DB::raw('(total_amount - amount_paid)'));
        $outstandingTotal = $outstandingBalance;

        $unpaidBills = Bill::with(['order.customer'])
            ->where('payment_status', '!=', 'paid')
            ->orderByDesc('bill_id')
            ->get();
        $unpaidCount = $unpaidBills->count();

        // Recent Paid Payments
        $payments = Payment::with(['bill.order.customer'])
            ->where('status', 'paid')
            ->whereBetween(DB::raw('DATE(paid_at)'), [$startDate, $endDate])
            ->orderByDesc('paid_at')
            ->get();
        $recentPayments = $payments;

        // FR26: Orders Grouped by Status
        $ordersQuery = Order::with(['customer', 'bill']);
        if ($statusFilter) {
            $ordersQuery->where('status', $statusFilter);
        }
        $ordersByStatus = $ordersQuery->orderByDesc('date_received')->get();
        $orders = $ordersByStatus;
        $statusCounts = Order::selectRaw('status, COUNT(*) as count')->groupBy('status')->pluck('count', 'status');

        // FR27: Customer Order History
        $customers = User::where('role', 'customer')->orderBy('full_name')->get();
        $customerHistory = $customerId
            ? Order::with(['garments.serviceItem', 'bill'])->where('customer_id', $customerId)->orderByDesc('date_received')->get()
            : collect();

        return view('owner.dashboard', compact(
            'from',
            'to',
            'startDate',
            'endDate',
            'totalOrders',
            'ordersCount',
            'completedOrders',
            'totalSales',
            'totalRevenue',
            'cashSales',
            'cashRevenue',
            'mpesaSales',
            'mpesaRevenue',
            'outstandingBalance',
            'outstandingTotal',
            'unpaidBills',
            'unpaidCount',
            'payments',
            'recentPayments',
            'ordersByStatus',
            'orders',
            'statusCounts',
            'statusFilter',
            'customers',
            'customerId',
            'customerHistory'
        ));
    }

    public function printReport(Request $request)
    {
        return $this->dashboard($request);
    }

    public function servicesIndex()
    {
        $services = ServiceItem::orderBy('service_name')->get();
        return view('owner.services.index', compact('services'));
    }

    public function storeService(Request $request)
    {
        $validated = $request->validate([
            'service_name' => 'required|string|max:80',
            'unit' => 'required|string|max:20',
            'unit_price' => 'required|numeric|gt:0',
        ]);

        ServiceItem::create([
            'service_name' => trim($validated['service_name']),
            'unit' => trim($validated['unit']),
            'unit_price' => $validated['unit_price'],
            'is_active' => 1,
            'defined_by' => Auth::id(),
        ]);

        return back()->with('success', 'Service price added successfully.');
    }

    public function updateService(Request $request, $serviceId)
    {
        $validated = $request->validate([
            'service_name' => 'sometimes|string|max:80',
            'unit' => 'sometimes|string|max:20',
            'unit_price' => 'required|numeric|gt:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $service = ServiceItem::findOrFail($serviceId);
        $service->fill($validated);
        if ($request->has('is_active')) {
            $service->is_active = (bool) $request->is_active;
        }
        $service->save();

        return back()->with('success', 'Service updated successfully.');
    }
}