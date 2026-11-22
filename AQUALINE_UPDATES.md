# Aqualine System Updates Summary

## 1. Staff Dashboard & Collected Pickups (`StaffOrderController.php`, `resources/views/staff/dashboard.blade.php`)
- `StaffOrderController@dashboard` builds `$collectedPickups` by filtering `$staffPickups` to items with `status === 'collected'`.
- Section **2. Confirm & Update Customer Pickups** lets staff move pickups through `pickup_requested`, `arranging_pickup`, `on_the_road`, and `collected`.
- The **Record Order & Garments (Collected at Laundry Hub)** card includes `#collected-pickup-select` listing only `collected` pickups and auto-filling the customer, service/type, and quantity into the first garment row when selected.

## 2. Multi-Item Pickup & Quantity Cap (`TrackingController.php`, `resources/views/customer/pickup.blade.php`)
- Customers can add multiple service rows (`items[i][service]`, `items[i][quantity]`) from active `ServiceItem` records.
- Quantity per item is capped at `20` (`min:1|max:20` on both client-side checks and server validation), rejecting values above `20` with a clear limit message.

## 3. Single-Owner Policy & Owner Account Management (`AuthController.php`, `AccountManagementController.php`, `resources/views/owner/staff/index.blade.php`)
- Only one owner account is allowed; extra owner accounts cannot log in or be created while an owner exists.
- Owner can view and delete non-self accounts across roles (`owner`, `staff`, `customer`).
