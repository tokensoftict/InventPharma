<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Traits\ModelFilterTraits;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Invoiceitembatch
 *
 * @property int $id
 * @property int $invoice_id
 * @property int $invoiceitem_id
 * @property int|null $stock_id
 * @property int|null $stockbatch_id
 * @property float $cost_price
 * @property float $selling_price
 * @property string|null $department
 * @property string|null $batch_no
 * @property int $quantity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Invoice $invoice
 * @property Invoiceitem $invoiceitem
 * @property Stock|null $stock
 * @property Stockbatch|null $stockbatch
 *
 * @package App\Models
 */
class Invoiceitembatch extends Model
{
    use ModelFilterTraits;

	protected $table = 'invoiceitembatches';

	protected $casts = [
		'invoice_id' => 'int',
		'invoiceitem_id' => 'int',
		'stock_id' => 'int',
		'stockbatch_id' => 'int',
		'cost_price' => 'float',
		'selling_price' => 'float',
		'quantity' => 'int'
	];

	protected $fillable = [
		'invoice_id',
		'invoiceitem_id',
		'stock_id',
		'stockbatch_id',
		'cost_price',
		'selling_price',
		'department',
		'quantity',
        'batch_no'
	];

    protected $with = ['stockbatch'];

    protected $appends = ['av_qty'];

    public function getAvQtyAttribute()
    {
        return $this->stockbatch->{$this->department} ?? 0;
    }

	public function invoice()
	{
		return $this->belongsTo(Invoice::class);
	}

	public function invoiceitem()
	{
		return $this->belongsTo(Invoiceitem::class);
	}

	public function stock()
	{
		return $this->belongsTo(Stock::class);
	}

	public function stockbatch()
	{
		return $this->belongsTo(Stockbatch::class);
	}
}
