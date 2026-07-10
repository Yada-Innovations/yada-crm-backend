<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Ticket;
use App\Models\SubscriptionPlan;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;

class CrmSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@yadacrm.com'],
            [
                'name' => 'System Admin',
                'password' => bcrypt('Livymugo@20'),
            ]
        );
        $admin->assignRole('admin');

        $rose = User::firstOrCreate(
            ['email' => 'rose@yada.co.ke'],
            [
                'name' => 'Rose Wanjiku',
                'password' => bcrypt('password'),
            ]
        );
        $rose->assignRole('admin');
        // Subscription plans
        $starter = SubscriptionPlan::create([
            'id' => Str::uuid(), 'name' => 'Starter',
            'price' => 15000, 'currency' => 'KES',
            'max_seats' => 10, 'billing_cycle' => 'monthly',
        ]);
        $pro = SubscriptionPlan::create([
            'id' => Str::uuid(), 'name' => 'Pro',
            'price' => 45000, 'currency' => 'KES',
            'max_seats' => 25, 'billing_cycle' => 'monthly',
        ]);
        $enterprise = SubscriptionPlan::create([
            'id' => Str::uuid(), 'name' => 'Enterprise',
            'price' => 120000, 'currency' => 'KES',
            'max_seats' => 100, 'billing_cycle' => 'monthly',
        ]);

        // Clients
        $xyz = Client::create(['id' => Str::uuid(), 'name' => 'XYZ Ltd', 'email' => 'info@xyz.co.ke', 'company' => 'XYZ Ltd', 'industry' => 'Finance', 'status' => 'active', 'account_manager_id' => $admin->id]);
        $abc = Client::create(['id' => Str::uuid(), 'name' => 'ABC Bank', 'email' => 'info@abcbank.co.ke', 'company' => 'ABC Bank', 'industry' => 'Banking', 'status' => 'active', 'account_manager_id' => $admin->id]);
        $kcb = Client::create(['id' => Str::uuid(), 'name' => 'KCB Group', 'email' => 'info@kcb.co.ke', 'company' => 'KCB Group', 'industry' => 'Banking', 'status' => 'active', 'account_manager_id' => $admin->id]);

        // Subscriptions
        Subscription::create(['id' => Str::uuid(), 'client_id' => $xyz->id, 'plan_id' => $enterprise->id, 'seats_used' => 32, 'status' => 'active', 'starts_at' => '2026-01-01', 'ends_at' => '2026-08-01']);
        Subscription::create(['id' => Str::uuid(), 'client_id' => $abc->id, 'plan_id' => $pro->id, 'seats_used' => 18, 'status' => 'active', 'starts_at' => '2026-01-01', 'ends_at' => '2026-07-15']);
        Subscription::create(['id' => Str::uuid(), 'client_id' => $kcb->id, 'plan_id' => $enterprise->id, 'seats_used' => 67, 'status' => 'active', 'starts_at' => '2026-01-01', 'ends_at' => '2026-07-01']);

        // Leads
        Lead::create(['id' => Str::uuid(), 'company' => 'Equity Bank', 'name' => 'John Kamau', 'email' => 'jkamau@equity.co.ke', 'status' => 'new', 'estimated_value' => 320000, 'assigned_to' => $admin->id]);
        Lead::create(['id' => Str::uuid(), 'company' => 'Safaricom', 'name' => 'Mary Wanjiru', 'email' => 'mwanjiru@safaricom.co.ke', 'status' => 'contacted', 'estimated_value' => 1200000, 'assigned_to' => $admin->id]);
        Lead::create(['id' => Str::uuid(), 'company' => 'NCBA Bank', 'name' => 'Peter Otieno', 'email' => 'potieno@ncba.co.ke', 'status' => 'qualified', 'estimated_value' => 440000, 'assigned_to' => $admin->id]);
        Lead::create(['id' => Str::uuid(), 'company' => 'Stanbic Bank', 'name' => 'Alice Njeri', 'email' => 'anjeri@stanbic.co.ke', 'status' => 'qualified', 'estimated_value' => 780000, 'assigned_to' => $admin->id]);
        Lead::create(['id' => Str::uuid(), 'company' => 'I&M Bank', 'name' => 'David Maina', 'email' => 'dmaina@im.co.ke', 'status' => 'converted', 'estimated_value' => 500000, 'assigned_to' => $admin->id]);

        // Tickets
        Ticket::create(['id' => Str::uuid(), 'subject' => 'API timeout on invoice sync', 'description' => 'Invoice sync timing out after 30s', 'client_id' => $xyz->id, 'status' => 'in_progress', 'priority' => 'high', 'created_by' => $admin->id]);
        Ticket::create(['id' => Str::uuid(), 'subject' => 'Cannot export leads to CSV', 'description' => 'Export button returns 500 error', 'client_id' => $abc->id, 'status' => 'open', 'priority' => 'medium', 'created_by' => $admin->id]);
        Ticket::create(['id' => Str::uuid(), 'subject' => 'Dashboard loads slowly', 'description' => 'Dashboard taking 8s to load', 'client_id' => $kcb->id, 'status' => 'resolved', 'priority' => 'low', 'created_by' => $admin->id]);

        // Invoice
        $invoice = Invoice::create([
            'id'             => Str::uuid(),
            'invoice_number' => 'INV-2031',
            'client_id'      => $abc->id,
            'subtotal'       => 500000,
            'discount_pct'   => 0,
            'total'          => 500000,
            'margin_pct'     => 62,
            'status'         => 'paid',
            'etims_status'   => 'synced',
            'etims_code'     => 'ETIMS-ABC12345',
            'created_by'     => $admin->id,
            'due_date'       => '2026-07-01',
        ]);
        InvoiceItem::create(['id' => Str::uuid(), 'invoice_id' => $invoice->id, 'description' => 'Enterprise license setup', 'quantity' => 1, 'unit_price' => 500000, 'total' => 500000]);
    }
}