<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Movingstock
 * 
 * @property int $id
 * @property int|null $stock_id
 * @property int|null $supplier_id
 * @property int|null $stockgroup_id
 * @property float|null $retail_qty
 * @property float|null $no_qty_sold
 * @property float|null $daily_qty_sold
 * @property float|null $average_inventory
 * @property float|null $turn_over_rate
 * @property int|null $group_os_id
 * @property int $is_grouped
 * @property float|null $turn_over_rate2
 * @property int|null $lastpurchase_days
 * @property float|null $moving_stocks_constant2
 * @property string|null $name
 * @property int|null $box
 * @property float|null $threshold
 * @property int|null $cartoon
 * @property string|null $supplier_name
 * @property string|null $av_cost_price
 * @property string|null $av_rt_cost_price
 * @property string|null $rt_qty
 * @property string|null $all_qty
 * @property string|null $tt_av_cost_price
 * @property string|null $tt_av_rt_cost_price
 * @property string $last_supply_date
 * @property int $last_supply_quantity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Stock|null $stock
 * @property Stockgroup|null $stockgroup
 * @property Supplier|null $supplier
 *
 * @package App\Models
 */
class Movingstock extends Model
{
	protected $table = 'movingstocks';

	protected $casts = [
		'stock_id' => 'int',
		'supplier_id' => 'int',
		'stockgroup_id' => 'int',
		'retail_qty' => 'float',
		'no_qty_sold' => 'float',
		'daily_qty_sold' => 'float',
		'average_inventory' => 'float',
		'turn_over_rate' => 'float',
		'group_os_id' => 'int',
		'is_grouped' => 'int',
		'turn_over_rate2' => 'float',
		'lastpurchase_days' => 'int',
		'moving_stocks_constant2' => 'float',
		'box' => 'int',
		'threshold' => 'float',
		'cartoon' => 'int',
		'last_supply_quantity' => 'int'
	];

	protected $fillable = [
		'stock_id',
		'supplier_id',
		'stockgroup_id',
		'retail_qty',
		'no_qty_sold',
		'daily_qty_sold',
		'average_inventory',
		'turn_over_rate',
		'group_os_id',
		'is_grouped',
		'turn_over_rate2',
		'lastpurchase_days',
		'moving_stocks_constant2',
		'name',
		'box',
		'threshold',
		'cartoon',
		'supplier_name',
		'av_cost_price',
		'av_rt_cost_price',
		'rt_qty',
		'all_qty',
		'tt_av_cost_price',
		'tt_av_rt_cost_price',
		'last_supply_date',
		'last_supply_quantity'
	];

	public function stock()
	{
		return $this->belongsTo(Stock::class);
	}

	public function stockgroup()
	{
		return $this->belongsTo(Stockgroup::class);
	}

	public function supplier()
	{
		return $this->belongsTo(Supplier::class);
	}
}
