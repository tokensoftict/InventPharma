<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class SupplierStockOpening
 * 
 * @property int $id
 * @property int $supplier_id
 * @property float $total_opening_cost_price
 * @property float $total_opening_retail_cost_price
 * @property float $total_supplier_outstanding
 * @property int $total_opening_quantity_retail
 * @property int $total_opening_quantity
 * @property Carbon $last_supplier_date
 * @property Carbon $date_added
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Supplier $supplier
 *
 * @package App\Models
 */
class SupplierStockOpening extends Model
{
	protected $table = 'supplier_stock_openings';

	protected $casts = [
		'supplier_id' => 'int',
		'total_opening_cost_price' => 'float',
		'total_opening_retail_cost_price' => 'float',
		'total_supplier_outstanding' => 'float',
		'total_opening_quantity_retail' => 'int',
		'total_opening_quantity' => 'int',
		'last_supplier_date' => 'datetime',
		'date_added' => 'datetime'
	];

	protected $fillable = [
		'supplier_id',
		'total_opening_cost_price',
		'total_opening_retail_cost_price',
		'total_supplier_outstanding',
		'total_opening_quantity_retail',
		'total_opening_quantity',
		'last_supplier_date',
		'date_added'
	];

	public function supplier()
	{
		return $this->belongsTo(Supplier::class);
	}
}
