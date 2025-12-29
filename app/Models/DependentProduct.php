<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class DependentProduct
 * 
 * @property int $id
 * @property int $parent_stock_id
 * @property int $stock_id
 * @property int $parent
 * @property int $child
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Stock $stock
 * @property Stock $parentStock
 *
 * @package App\Models
 */
class DependentProduct extends Model
{
	protected $table = 'dependent_products';

	protected $casts = [
		'parent_stock_id' => 'int',
		'stock_id' => 'int',
		'parent' => 'int',
		'child' => 'int'
	];

	protected $fillable = [
		'parent_stock_id',
		'stock_id',
		'parent',
		'child'
	];

    public function parentStock()
    {
        return $this->belongsTo(Stock::class, 'parent_stock_id');
    }


	public function stock()
	{
		return $this->belongsTo(Stock::class, 'stock_id');
	}
}
