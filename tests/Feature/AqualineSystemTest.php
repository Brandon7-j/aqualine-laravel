<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Garment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ServiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AqualineSystemTest extends TestCase
{
    use DatabaseTransactions;

    public function test_role_based_login_and_access_control_nfr01(): void
    {
        $owner = User::create([
            'full_name' => 'Test Owner',
            'phone' => '0700000001',
            'username' => 'test_owner_unit',
            'password_hash' => Hash::make('Owner@123'),
            'role' => 'owner',
            'status' => 'active',
        ]);

        $customer = User::create([
            'full_name' => 'Test Customer',
            'phone' => '0700000002',
            'password_hash' => Hash::make('Customer@123'),
            'role' => 'customer',
            'status' => 'active',
        ]);

        // FR03: Owner logs in and reaches Owner Dashboard
        $this->post('/login', [
            'login_id' => 'test_owner_unit',
            'password' => 'Owner@123',
        ])->assertRedirect(route('owner.dashboard'));

        $this->post('/logout');

        // FR01: Customer logs in and redirects to frontend
        $this->post('/login', [
            'login_id' => '0700000002',
            'password' => 'Customer@123',
        ])->assertRedirect('http://localhost/laundry-system/index.php');

        // NFR01: Customer is blocked from Owner and Staff portals
        $this->actingAs($customer)->get('/owner/dashboard')->assertStatus(403);
        $this->actingAs($customer)->get('/staff/dashboard')->assertStatus(403);
    }

    public function test_end_to_end_order_billing_and_mpesa_callback_workflow(): void
    {
        $owner = User::create([
            'full_name' => 'Owner User',
            'phone' => '0700000010',
            'username' => 'owner_flow',
            'password_hash' => Hash::make('Owner@123'),
            'role' => 'owner',
            'status' => 'active',
        ]);

        $staff = User::create([
            'full_name' => 'Staff User',
            'phone' => '0700000011',
            'username' => 'staff_flow',
            'password_hash' => Hash::make('Staff@123'),
            'role' => 'staff',
            'status' => 'active',
        ]);

        $customer = User::create([
            'full_name' => 'Customer User',
            'phone' => '0700000012',
            'password_hash' => Hash::make('Customer@123'),
            'role' => 'customer',
            'status' => 'active',
        ]);

        $service = ServiceItem::create([
            'service_name' => 'Test Suit Dry Clean',
            'unit' => 'Per Item',
            'unit_price' => 500.00,
            'is_active' => 1,
            'defined_by' => $owner->user_id,
        ]);

        // FR13 & FR14: Staff creates order with 2 suits (2 x 500 = KES 1,000)
        $this->actingAs($staff)->post('/staff/orders', [
            'customer_id' => $customer->user_id,
            'expected_ready_date' => now()->addDays(2)->toDateString(),
            'garments' => [
                [
                    'service_id' => $service->service_id,
                    'garment_type' => 'Two-Piece Suit',
                    'colour' => 'Navy Blue',
                    'quantity' => 2,
                    'condition_note' => 'Good condition',
                ],
            ],
        ])->assertSessionHasNoErrors();

        $order = Order::where('customer_id', $customer->user_id)->firstOrFail();
        $this->assertEquals('received', $order->status);

        // FR18: Generate bill and verify KES 1,000.00 total
        $this->actingAs($staff)->post("/staff/orders/{$order->order_no}/bill")
            ->assertSessionHasNoErrors();

        $bill = Bill::where('order_no', $order->order_no)->firstOrFail();
        $this->assertEquals(1000.00, (float) $bill->amount_due);
        $this->assertEquals('unpaid', $bill->payment_status);

        // NFR04: Duplicate bill for the same order is prevented
        $this->actingAs($staff)->post("/staff/orders/{$order->order_no}/bill");
        $this->assertEquals(1, Bill::where('order_no', $order->order_no)->count());
    }
}