<?php

namespace App\Services\Printing;

/**
 * Unified receipt data structure shared by both PDF and ESC/POS renderers.
 * This ensures both renderers display identical business information.
 */
class ReceiptData
{
    public array $company = [];
    public array $transaction = [];
    public array $customer = [];
    public array $items = [];
    public array $totals = [];
    public ?array $onlineOrderTotals = null;
    public array $barcode = [];
    public array $meta = [];
    public ?array $picker = null;
    public ?array $onlinePicker = null;

    /**
     * Create from an associative array.
     */
    public static function fromArray(array $data): self
    {
        $instance = new self();
        foreach ($data as $key => $value) {
            if (property_exists($instance, $key)) {
                $instance->{$key} = $value;
            }
        }
        return $instance;
    }

    /**
     * Convert to array for serialization/testing.
     */
    public function toArray(): array
    {
        return [
            'company' => $this->company,
            'transaction' => $this->transaction,
            'customer' => $this->customer,
            'items' => $this->items,
            'totals' => $this->totals,
            'onlineOrderTotals' => $this->onlineOrderTotals,
            'barcode' => $this->barcode,
            'meta' => $this->meta,
            'picker' => $this->picker,
            'onlinePicker' => $this->onlinePicker,
        ];
    }
}
