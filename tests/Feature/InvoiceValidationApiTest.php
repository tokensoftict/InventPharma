<?php

namespace Tests\Feature;

use App\Models\PurchaseLimit;
use App\Models\Stock;
use App\Models\Stockbatch;
use Carbon\Carbon;
use Tests\TestCase;

class InvoiceValidationApiTest extends TestCase
{
    protected Stock $testStock;
    protected Stockbatch $testBatch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testStock = Stock::create([
            'name' => 'API TEST PARACETAMOL 500MG',
            'whole_price' => 1500.00,
            'retail_price' => 1800.00,
            'wholesales' => 50,
            'retail' => 50,
            'carton' => 10,
            'box' => 5,
            'status' => true,
        ]);

        $this->testBatch = Stockbatch::create([
            'stock_id' => $this->testStock->id,
            'batch_no' => 'TEST-BATCH-001',
            'wholesales' => 50,
            'retail' => 50,
            'cost_price' => 1200.00,
            'retail_cost_price' => 1200.00,
            'received_date' => Carbon::today(),
            'expiry_date' => Carbon::today()->addYear(),
        ]);
    }

    protected function tearDown(): void
    {
        if (isset($this->testBatch->id)) {
            $this->testBatch->delete();
        }
        if (isset($this->testStock->id)) {
            $this->testStock->delete();
        }

        parent::tearDown();
    }

    public function test_api_validation_fails_on_empty_payload(): void
    {
        $response = $this->postJson('/api/invoice/validate', []);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'valid' => false,
            ])
            ->assertJsonStructure([
                'status',
                'valid',
                'message',
                'errors',
            ]);
    }

    public function test_api_validation_fails_when_product_does_not_exist(): void
    {
        $response = $this->postJson('/api/invoice/validate', [
            'department' => 'wholesales',
            'customer_id' => 1,
            'items' => [
                [
                    'stock_id' => 99999999,
                    'quantity' => 1,
                ]
            ],
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'valid' => false,
            ])
            ->assertJsonFragment([
                '99999999' => 'Product with ID 99999999 was not found in inventory.',
            ]);
    }

    public function test_api_validation_fails_on_insufficient_stock(): void
    {
        $excessiveQty = $this->testStock->wholesales + 500;

        $response = $this->postJson('/api/invoice/validate', [
            'department' => 'wholesales',
            'customer_id' => 1,
            'items' => [
                [
                    'stock_id' => $this->testStock->id,
                    'quantity' => $excessiveQty,
                ]
            ],
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'valid' => false,
            ]);

        $this->assertArrayHasKey((string) $this->testStock->id, $response->json('errors'));
        $this->assertStringContainsString('Not enough available quantity', $response->json('errors')[(string) $this->testStock->id]);
    }

    public function test_api_validation_succeeds_for_available_stock(): void
    {
        $response = $this->postJson('/api/invoice/validate', [
            'department' => 'wholesales',
            'customer_id' => 1,
            'items' => [
                [
                    'stock_id' => $this->testStock->id,
                    'quantity' => 5,
                ]
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'valid' => true,
            ])
            ->assertJsonStructure([
                'status',
                'valid',
                'message',
                'data' => [
                    'department',
                    'customer_id',
                    'sub_total',
                    'items',
                ],
            ]);

        $this->assertEquals(7500.0, $response->json('data.sub_total')); // 5 * 1500.00
    }

    public function test_api_validation_succeeds_for_retail_department(): void
    {
        $response = $this->postJson('/api/invoice/validate', [
            'department' => 'retail',
            'customer_id' => 1,
            'items' => [
                [
                    'stock_id' => $this->testStock->id,
                    'quantity' => 5,
                ]
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'valid' => true,
            ])
            ->assertJsonPath('data.department', 'retail')
            ->assertJsonPath('data.items.0.selling_price', 1800); // retail price is 1800.00

        $this->assertEquals(9000.0, $response->json('data.sub_total')); // 5 * 1800.00
    }

    public function test_api_validation_fails_when_purchase_limit_exceeded(): void
    {
        $limit = PurchaseLimit::create([
            'name' => 'Test Rule',
            'department' => 'wholesales',
            'max_quantity' => 2,
            'period_value' => 1,
            'period_unit' => 'months',
            'start_date' => Carbon::now()->subDay(),
            'is_active' => true,
        ]);
        $limit->stocks()->attach($this->testStock->id);

        try {
            $response = $this->postJson('/api/invoice/validate', [
                'department' => 'wholesales',
                'customer_id' => 5, // non-walk-in customer
                'items' => [
                    [
                        'stock_id' => $this->testStock->id,
                        'quantity' => 3, // exceeds max_quantity of 2
                    ]
                ],
            ]);

            $response->assertStatus(422)
                ->assertJson([
                    'status' => false,
                    'valid' => false,
                ]);

            $errors = $response->json('errors');
            $this->assertArrayHasKey((string) $this->testStock->id, $errors);
            $this->assertStringContainsString('Purchase Limit Exceeded: You can purchase a maximum of 2 API TEST PARACETAMOL 500MG every 1 month. You requested 3, but only 2 is currently allowed.', $errors[(string) $this->testStock->id]);
        } finally {
            $limit->stocks()->detach();
            $limit->delete();
        }
    }

    public function test_api_validation_fails_when_purchase_limit_exceeded_in_retail_department(): void
    {
        $limit = PurchaseLimit::create([
            'name' => 'Retail Limit Rule',
            'department' => 'retail',
            'max_quantity' => 2,
            'period_value' => 1,
            'period_unit' => 'months',
            'start_date' => Carbon::now()->subDay(),
            'is_active' => true,
        ]);
        $limit->stocks()->attach($this->testStock->id);

        try {
            $response = $this->postJson('/api/invoice/validate', [
                'department' => 'retail',
                'customer_id' => 5, // registered customer buying in retail
                'items' => [
                    [
                        'stock_id' => $this->testStock->id,
                        'quantity' => 4, // exceeds limit of 2
                    ]
                ],
            ]);

            $response->assertStatus(422)
                ->assertJson([
                    'status' => false,
                    'valid' => false,
                ]);

            $errors = $response->json('errors');
            $this->assertArrayHasKey((string) $this->testStock->id, $errors);
            $this->assertStringContainsString('Purchase Limit Exceeded: You can purchase a maximum of 2 API TEST PARACETAMOL 500MG every 1 month. You requested 4, but only 2 is currently allowed.', $errors[(string) $this->testStock->id]);
        } finally {
            $limit->stocks()->detach();
            $limit->delete();
        }
    }

    public function test_wholesale_purchase_limit_does_not_block_retail_invoices(): void
    {
        // Limit is created for WHOLESALES
        $limit = PurchaseLimit::create([
            'name' => 'Wholesale Only Rule',
            'department' => 'wholesales',
            'max_quantity' => 2,
            'period_value' => 1,
            'period_unit' => 'months',
            'start_date' => Carbon::now()->subDay(),
            'is_active' => true,
        ]);
        $limit->stocks()->attach($this->testStock->id);

        try {
            // Request is for RETAIL department, ordering 4 units (more than 2)
            $response = $this->postJson('/api/invoice/validate', [
                'department' => 'retail',
                'customer_id' => 5,
                'items' => [
                    [
                        'stock_id' => $this->testStock->id,
                        'quantity' => 4,
                    ]
                ],
            ]);

            // Retail invoice should NOT be blocked by wholesale purchase limit!
            $response->assertStatus(200)
                ->assertJson([
                    'status' => true,
                    'valid' => true,
                ]);
        } finally {
            $limit->stocks()->detach();
            $limit->delete();
        }
    }

    public function test_retail_purchase_limit_does_not_block_wholesale_invoices(): void
    {
        // Limit is created for RETAIL
        $limit = PurchaseLimit::create([
            'name' => 'Retail Only Rule',
            'department' => 'retail',
            'max_quantity' => 2,
            'period_value' => 1,
            'period_unit' => 'months',
            'start_date' => Carbon::now()->subDay(),
            'is_active' => true,
        ]);
        $limit->stocks()->attach($this->testStock->id);

        try {
            // Request is for WHOLESALES department, ordering 4 units (more than 2)
            $response = $this->postJson('/api/invoice/validate', [
                'department' => 'wholesales',
                'customer_id' => 5,
                'items' => [
                    [
                        'stock_id' => $this->testStock->id,
                        'quantity' => 4,
                    ]
                ],
            ]);

            // Wholesale invoice should NOT be blocked by retail purchase limit!
            $response->assertStatus(200)
                ->assertJson([
                    'status' => true,
                    'valid' => true,
                ]);
        } finally {
            $limit->stocks()->detach();
            $limit->delete();
        }
    }

    public function test_wholesale_limit_applies_to_bulksales(): void
    {
        $limit = PurchaseLimit::create([
            'name' => 'Wholesale Rule for Bulksales',
            'department' => 'wholesales',
            'max_quantity' => 2,
            'period_value' => 1,
            'period_unit' => 'months',
            'start_date' => Carbon::now()->subDay(),
            'is_active' => true,
        ]);
        $limit->stocks()->attach($this->testStock->id);

        try {
            // Bulksales maps to wholesale limits
            $response = $this->postJson('/api/invoice/validate-purchase-limits', [
                'department' => 'bulksales',
                'customer_id' => 5,
                'items' => [
                    ['stock_id' => $this->testStock->id, 'quantity' => 3]
                ],
            ]);

            $response->assertStatus(422)
                ->assertJson(['status' => false]);
            $this->assertStringContainsString('Purchase Limit Exceeded: You can purchase a maximum of 2 API TEST PARACETAMOL 500MG every 1 month. You requested 3, but only 2 is currently allowed.', $response->json('errors')[(string) $this->testStock->id]);
        } finally {
            $limit->stocks()->detach();
            $limit->delete();
        }
    }

    public function test_wholesale_and_retail_limits_coexist_without_conflict(): void
    {
        $wholesaleLimit = PurchaseLimit::create([
            'name' => 'Wholesale Limit Rule',
            'department' => 'wholesales',
            'max_quantity' => 50,
            'period_value' => 1,
            'period_unit' => 'months',
            'is_active' => true,
        ]);
        $wholesaleLimit->stocks()->attach($this->testStock->id);

        try {
            $service = new \App\Services\PurchaseLimitService();

            // Creating a Retail limit for the SAME product should NOT conflict
            $retailLimit = new PurchaseLimit([
                'name' => 'Retail Limit Rule',
                'department' => 'retail',
                'max_quantity' => 5,
                'period_value' => 1,
                'period_unit' => 'days',
                'is_active' => true,
            ]);

            $conflicts = $service->validateNoConflict($retailLimit, [$this->testStock->id]);
            $this->assertEmpty($conflicts, 'Wholesale and Retail limits on the same product must not conflict.');

            // However, another Wholesale limit for the same product SHOULD conflict
            $duplicateWholesaleLimit = new PurchaseLimit([
                'name' => 'Another Wholesale Rule',
                'department' => 'wholesales',
                'max_quantity' => 20,
                'period_value' => 1,
                'period_unit' => 'months',
                'is_active' => true,
            ]);

            $conflictsWholesale = $service->validateNoConflict($duplicateWholesaleLimit, [$this->testStock->id]);
            $this->assertNotEmpty($conflictsWholesale, 'Two active wholesale limits on the same product must conflict.');
        } finally {
            $wholesaleLimit->stocks()->detach();
            $wholesaleLimit->delete();
        }
    }

    public function test_api_validate_purchase_limits_endpoint_works(): void
    {
        $limit = PurchaseLimit::create([
            'name' => 'Standalone Limit Test',
            'max_quantity' => 2,
            'period_value' => 1,
            'period_unit' => 'months',
            'start_date' => Carbon::now()->subDay(),
            'is_active' => true,
        ]);
        $limit->stocks()->attach($this->testStock->id);

        try {
            // Test failure when exceeding
            $responseFail = $this->postJson('/api/invoice/validate-purchase-limits', [
                'department' => 'wholesales',
                'customer_id' => 5,
                'items' => [
                    ['stock_id' => $this->testStock->id, 'quantity' => 3]
                ],
            ]);

            $responseFail->assertStatus(422)
                ->assertJson(['status' => false]);
            $this->assertArrayHasKey((string) $this->testStock->id, $responseFail->json('errors'));

            // Test pass when within limit
            $responsePass = $this->postJson('/api/invoice/validate-purchase-limits', [
                'department' => 'wholesales',
                'customer_id' => 5,
                'items' => [
                    ['stock_id' => $this->testStock->id, 'quantity' => 1]
                ],
            ]);

            $responsePass->assertStatus(200)
                ->assertJson(['status' => true, 'valid' => true]);
        } finally {
            $limit->stocks()->detach();
            $limit->delete();
        }
    }

    public function test_v1_alias_route_works(): void
    {
        $response = $this->postJson('/api/v1/invoice/validate', [
            'department' => 'wholesales',
            'customer_id' => 1,
            'items' => [
                [
                    'stock_id' => 99999999,
                    'quantity' => 1,
                ]
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_purchase_limit_component_persists_department_properly(): void
    {
        $test = \Livewire\Livewire::test(\App\Livewire\PurchaseLimit\PurchaseLimitComponent::class);
        $test->assertSet('department', 'wholesales');

        $test->set('name', 'Livewire Test Retail Rule')
            ->set('department', 'retail')
            ->set('max_quantity', '5')
            ->set('period_value', '14')
            ->set('period_unit', 'days')
            ->set('selectedProducts', [
                ['id' => $this->testStock->id, 'name' => $this->testStock->name]
            ])
            ->call('save');

        $savedLimit = PurchaseLimit::where('name', 'Livewire Test Retail Rule')->first();
        $this->assertNotNull($savedLimit);
        $this->assertEquals('retail', $savedLimit->department);
        $this->assertEquals(5, $savedLimit->max_quantity);

        // Edit
        $editTest = \Livewire\Livewire::test(\App\Livewire\PurchaseLimit\PurchaseLimitComponent::class);
        $editTest->call('edit', $savedLimit->id);
        $editTest->assertSet('department', 'retail');

        // Clean up
        $savedLimit->stocks()->detach();
        $savedLimit->delete();
    }

    public function test_purchase_limit_error_message_format_exact_match(): void
    {
        $stock = Stock::create([
            'name' => 'TUYIL PARACETAMOL TABLET X96',
            'whole_price' => 1500.00,
            'wholesales' => 50,
            'status' => true,
        ]);
        $batch = Stockbatch::create([
            'stock_id' => $stock->id,
            'batch_no' => 'TUYIL-001',
            'wholesales' => 50,
            'cost_price' => 1200.00,
        ]);
        $limit = PurchaseLimit::create([
            'name' => 'TUYIL Limit',
            'department' => 'wholesales',
            'max_quantity' => 1,
            'period_value' => 10,
            'period_unit' => 'days',
            'start_date' => Carbon::now()->subDay(),
            'is_active' => true,
        ]);
        $limit->stocks()->attach($stock->id);

        try {
            $response = $this->postJson('/api/invoice/validate', [
                'department' => 'wholesales',
                'customer_id' => 5,
                'items' => [
                    ['stock_id' => $stock->id, 'quantity' => 2]
                ],
            ]);

            $response->assertStatus(422);
            $errors = $response->json('errors');
            $expectedMsg = 'Purchase Limit Exceeded: You can purchase a maximum of 1 TUYIL PARACETAMOL TABLET X96 every 10 days. You requested 2, but only 1 is currently allowed.';
            $this->assertEquals($expectedMsg, $errors[(string) $stock->id]);
        } finally {
            $limit->stocks()->detach();
            $limit->delete();
            $batch->delete();
            $stock->delete();
        }
    }
}
