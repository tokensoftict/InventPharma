<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Retailnearoutofstock
 * 
 * @property int $id
 * @property int|null $stock_id
 * @property int|null $stockgroup_id
 * @property string $threshold_type
 * @property string $os_type
 * @property int $qty_to_buy
 * @property int|null $supplier_id
 * @property float|null $threshold_value
 * @property float|null $current_qty
 * @property float|null $current_sold
 * @property int|null $group_os_id
 * @property bool $is_grouped
 * @property int|null $last_qty_purchased
 * @property Carbon|null $last_purchase_date
 * @property int|null $purchaseitem_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Purchaseitem|null $purchaseitem
 * @property Stock|null $stock
 * @property Supplier|null $supplier
 *
 * @package App\Models
 */
class Retailnearoutofstock extends Model
{
	protected $table = 'retailnearoutofstock';

	protected $casts = [
		'stock_id' => 'int',
		'stockgroup_id' => 'int',
		'qty_to_buy' => 'int',
		'supplier_id' => 'int',
		'threshold_value' => 'float',
		'current_qty' => 'float',
		'current_sold' => 'float',
		'group_os_id' => 'int',
		'is_grouped' => 'bool',
		'last_qty_purchased' => 'int',
		'last_purchase_date' => 'datetime',
		'purchaseitem_id' => 'int'
	];

	protected $fillable = [
		'stock_id',
		'stockgroup_id',
		'threshold_type',
		'os_type',
		'qty_to_buy',
		'supplier_id',
		'threshold_value',
		'current_qty',
		'current_sold',
		'group_os_id',
		'is_grouped',
		'last_qty_purchased',
		'last_purchase_date',
		'purchaseitem_id'
	];

	public function purchaseitem()
	{
		return $this->belongsTo(Purchaseitem::class);
	}

	public function stock()
	{
		return $this->belongsTo(Stock::class);
	}

	public function supplier()
	{
		return $this->belongsTo(Supplier::class);
	}
}
