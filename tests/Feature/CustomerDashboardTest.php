<?php

namespace Tests\Feature;

use App\Livewire\Customer\CustomerManagerComponent;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\MemberGroup;
use App\Models\Status;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerDashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.customer_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
            'database.default' => 'customer_test',
            'cache.default' => 'array',
        ]);
        DB::purge('customer_test');

        // Create minimal schema for testing
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('email')->nullable();
            $table->boolean('status')->default(1);
            $table->mediumText('address')->nullable();
            $table->string('phone_number')->nullable()->index();
            $table->boolean('retail_customer')->default(0);
            $table->string('customer_type', 20)->nullable()->default(null);
            $table->unsignedBigInteger('city_id')->nullable();
            $table->decimal('credit_balance', 20, 5)->default(0);
            $table->decimal('deposit_balance', 20, 5)->default(0);
            $table->decimal('loyalty_points')->default(0);
            $table->decimal('retail_loyalty_points')->default(0);
            $table->unsignedBigInteger('member_group_id')->nullable();
            $table->unsignedBigInteger('retail_member_group_id')->nullable();
            $table->boolean('is_manual_member_group')->default(0);
            $table->boolean('is_manual_retail_member_group')->default(0);
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('in_department')->nullable();
            $table->unsignedBigInteger('status_id')->default(1);
            $table->decimal('sub_total', 20, 5)->default(0);
            $table->decimal('total_amount_paid', 20, 5)->default(0);
            $table->date('invoice_date')->nullable();
            $table->timestamps();
        });

        Schema::create('statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('member_groups', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->decimal('min_sales_amount', 20, 5)->default(0);
            $table->decimal('retail_min_sales_amount', 20, 5)->default(0);
            $table->boolean('status')->default(1);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->date('payment_date')->nullable();
            $table->decimal('total_paid', 20, 5)->default(0);
            $table->timestamps();
        });

        Schema::create('creditpaymentlogs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->decimal('amount', 20, 5)->default(0);
            $table->date('payment_date')->nullable();
            $table->timestamps();
        });

        // Seed basic statuses
        Status::create(['id' => 1, 'name' => 'Paid']);
        Status::create(['id' => 2, 'name' => 'Complete']);
        Status::create(['id' => 3, 'name' => 'Dispatched']);
    }

    public function test_customer_department_type_resolution(): void
    {
        $wholesale = Customer::create([
            'firstname' => 'Alhaji',
            'lastname' => 'Musa',
            'customer_type' => 'wholesale',
            'retail_customer' => false,
        ]);
        $this->assertSame('wholesale', $wholesale->department_type);
        $this->assertSame('Wholesales', $wholesale->type_label);
        $this->assertStringContainsString('Wholesales', $wholesale->type_badge);

        $retail = Customer::create([
            'firstname' => 'Fatima',
            'lastname' => 'Aliyu',
            'customer_type' => 'retail',
            'retail_customer' => true,
        ]);
        $this->assertSame('retail', $retail->department_type);
        $this->assertSame('Retail', $retail->type_label);
        $this->assertStringContainsString('Retail', $retail->type_badge);

        $both = Customer::create([
            'firstname' => 'Chinedu',
            'lastname' => 'Okonkwo',
            'customer_type' => 'both',
            'retail_customer' => false,
        ]);
        $this->assertSame('both', $both->department_type);
        $this->assertSame('Wholesales & Retail', $both->type_label);
        $this->assertStringContainsString('Both', $both->type_badge);
    }

    public function test_customer_owing_status_calculation(): void
    {
        // Debtor customer (negative credit_balance)
        $debtor = Customer::create([
            'firstname' => 'Debtor',
            'lastname' => 'User',
            'credit_balance' => -25000.50,
        ]);
        $this->assertTrue($debtor->is_owing);
        $this->assertEquals(25000.50, $debtor->owing_amount);
        $this->assertStringContainsString('Owing', $debtor->owing_badge);
        $this->assertStringContainsString('25,000.50', $debtor->owing_badge);

        // Cleared customer
        $cleared = Customer::create([
            'firstname' => 'Cleared',
            'lastname' => 'User',
            'credit_balance' => 0,
        ]);
        $this->assertFalse($cleared->is_owing);
        $this->assertEquals(0, $cleared->owing_amount);
        $this->assertStringContainsString('Cleared', $cleared->owing_badge);

        // Customer with deposit
        $deposit = Customer::create([
            'firstname' => 'Deposit',
            'lastname' => 'User',
            'credit_balance' => 50000,
        ]);
        $this->assertFalse($deposit->is_owing);
        $this->assertStringContainsString('Deposit', $deposit->owing_badge);
    }

    public function test_user_can_update_customer_phone_number_without_being_blocked(): void
    {
        $customer = Customer::create([
            'firstname' => 'Ibrahim',
            'lastname' => 'Shehu',
            'phone_number' => '08011112222',
            'customer_type' => 'wholesale',
        ]);

        Livewire::test(CustomerManagerComponent::class)
            ->call('edit', $customer->id)
            ->set('phone_number', '080 9999 8888') // formatted phone number with spaces
            ->set('firstname', 'Ibrahim Updated')
            ->call('saveCustomers')
            ->assertHasNoErrors();

        $customer->refresh();
        $this->assertSame('Ibrahim Updated', $customer->firstname);
        $this->assertSame('08099998888', $customer->phone_number);
    }

    public function test_phone_is_required_and_unique(): void
    {
        $existing = Customer::create([
            'firstname' => 'Existing',
            'lastname' => 'Holder',
            'phone_number' => '08033334444',
            'status' => 1,
        ]);

        // Phone is required
        Livewire::test(CustomerManagerComponent::class)
            ->call('new')
            ->set('firstname', 'No')
            ->set('lastname', 'Phone')
            ->call('saveCustomers')
            ->assertHasErrors(['phone_number']);

        // Duplicate phone (even with formatting) is rejected
        $component = Livewire::test(CustomerManagerComponent::class)
            ->call('new')
            ->set('firstname', 'New')
            ->set('lastname', 'Customer')
            ->set('phone_number', '0803 333 4444')
            ->call('saveCustomers')
            ->assertHasErrors(['phone_number']);

        $this->assertNotNull($component->get('duplicatePhoneWarning'));
        $this->assertDatabaseMissing('customers', ['firstname' => 'New']);
        $this->assertSame(1, Customer::where('phone_number', '08033334444')->count());

        // Editing the owner with their own phone number is fine
        Livewire::test(CustomerManagerComponent::class)
            ->call('edit', $existing->id)
            ->set('firstname', 'Renamed')
            ->call('saveCustomers')
            ->assertHasNoErrors();
        $this->assertSame('Renamed', $existing->fresh()->firstname);
    }

    public function test_customer_view_glance_loads_transaction_details(): void
    {
        $customer = Customer::create([
            'firstname' => 'Ahmed',
            'lastname' => 'Garba',
            'phone_number' => '08055556666',
            'credit_balance' => -15000,
            'customer_type' => 'both',
        ]);

        Invoice::create([
            'invoice_number' => 'INV-001',
            'customer_id' => $customer->id,
            'in_department' => 'wholesales',
            'status_id' => 1,
            'sub_total' => 100000,
            'total_amount_paid' => 100000,
            'invoice_date' => now()->toDateString(),
        ]);

        Invoice::create([
            'invoice_number' => 'INV-002',
            'customer_id' => $customer->id,
            'in_department' => 'retail',
            'status_id' => 1,
            'sub_total' => 25000,
            'total_amount_paid' => 10000,
            'invoice_date' => now()->toDateString(),
        ]);

        Livewire::test(CustomerManagerComponent::class)
            ->call('view', $customer->id)
            ->assertDispatched('openGlanceModal')
            ->assertSet('selectedCustomer.id', $customer->id)
            ->assertSet('selectedCustomerStats.total_spent', 125000.0)
            ->assertSet('selectedCustomerStats.wholesale_spent', 100000.0)
            ->assertSet('selectedCustomerStats.retail_spent', 25000.0);

        // Also test dispatching the viewCustomer event (with array or named id)
        Livewire::test(CustomerManagerComponent::class)
            ->dispatch('viewCustomer', ['id' => $customer->id])
            ->assertDispatched('openGlanceModal')
            ->assertSet('selectedCustomer.id', $customer->id);
    }

    public function test_customer_type_determined_from_invoices_in_department(): void
    {
        // 1. Customer with bulksales and wholesales invoices -> Wholesale
        $custWholesale = Customer::create([
            'firstname' => 'Wholesale',
            'lastname' => 'Buyer',
            'customer_type' => null,
            'retail_customer' => 0,
        ]);
        Invoice::create([
            'invoice_number' => 'INV-W1',
            'customer_id' => $custWholesale->id,
            'in_department' => 'bulksales',
            'status_id' => 1,
            'sub_total' => 50000,
        ]);
        Invoice::create([
            'invoice_number' => 'INV-W2',
            'customer_id' => $custWholesale->id,
            'in_department' => 'wholesales',
            'status_id' => 1,
            'sub_total' => 30000,
        ]);

        // 2. Customer with retail invoice only -> Retail
        $custRetail = Customer::create([
            'firstname' => 'Retail',
            'lastname' => 'Shopper',
            'customer_type' => null,
            'retail_customer' => 0,
        ]);
        Invoice::create([
            'invoice_number' => 'INV-R1',
            'customer_id' => $custRetail->id,
            'in_department' => 'retail',
            'status_id' => 1,
            'sub_total' => 12000,
        ]);

        // 3. Customer with both wholesales and retail invoices -> Both
        $custBoth = Customer::create([
            'firstname' => 'Dual',
            'lastname' => 'Client',
            'customer_type' => null,
            'retail_customer' => 0,
        ]);
        Invoice::create([
            'invoice_number' => 'INV-B1',
            'customer_id' => $custBoth->id,
            'in_department' => 'wholesales',
            'status_id' => 1,
            'sub_total' => 80000,
        ]);
        Invoice::create([
            'invoice_number' => 'INV-B2',
            'customer_id' => $custBoth->id,
            'in_department' => 'retail',
            'status_id' => 1,
            'sub_total' => 15000,
        ]);

        // Run migration logic (SQLite branch)
        $rows = DB::table('invoices')
            ->select('customer_id')
            ->selectRaw("MAX(CASE WHEN in_department IN ('bulksales', 'wholesales') THEN 1 ELSE 0 END) as has_wholesale")
            ->selectRaw("MAX(CASE WHEN in_department = 'retail' THEN 1 ELSE 0 END) as has_retail")
            ->whereNotNull('customer_id')
            ->groupBy('customer_id')
            ->get();

        foreach ($rows as $row) {
            $type = 'wholesale';
            if ($row->has_wholesale && $row->has_retail) {
                $type = 'both';
            } elseif ($row->has_retail) {
                $type = 'retail';
            }
            DB::table('customers')->where('id', $row->customer_id)->update(['customer_type' => $type]);
        }

        $custWholesale->refresh();
        $custRetail->refresh();
        $custBoth->refresh();

        $this->assertSame('wholesale', $custWholesale->customer_type);
        $this->assertSame('Wholesales', $custWholesale->type_label);

        $this->assertSame('retail', $custRetail->customer_type);
        $this->assertSame('Retail', $custRetail->type_label);

        $this->assertSame('both', $custBoth->customer_type);
        $this->assertSame('Wholesales & Retail', $custBoth->type_label);
    }
}

