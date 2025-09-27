<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Traits\ModelFilterTraits;
use Auth;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Stockbatch
 *
 * @property int $id
 * @property Carbon|null $received_date
 * @property Carbon|null $expiry_date
 * @property int $wholesales
 * @property int $bulksales
 * @property int $retail
 * @property int $quantity
 * @property float|null $cost_price
 * @property string|null $batch_no
 * @property float|null $retail_cost_price
 * @property int|null $stock_id
 * @property int|null $supplier_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Stock|null $stock
 * @property Supplier|null $supplier
 *
 * @package App\Models
 */
class Stockbatch extends Model
{

    use ModelFilterTraits;

	protected $table = 'stockbatches';

	protected $casts = [
		'wholesales' => 'int',
		'bulksales' => 'int',
		'retail' => 'int',
		'quantity' => 'int',
        'retail_store' => 'int',
		'cost_price' => 'float',
		'retail_cost_price' => 'float',
		'stock_id' => 'int',
		'supplier_id' => 'int',
        'expiry_date' => 'date'
	];

	protected $fillable = [
		'received_date',
		'expiry_date',
		'wholesales',
		'bulksales',
		'retail',
		'quantity',
		'cost_price',
		'retail_cost_price',
		'stock_id',
		'supplier_id',
        'batch_no',
        'retail_store'
	];

	public function stock()
	{
		return $this->belongsTo(Stock::class);
	}

	public function supplier()
	{
		return $this->belongsTo(Supplier::class);
	}

    public function quantityColumnChanges()
    {
        if ($this->isDirty('quantity')) {
            //changes to main store
            $update = ['quantity' => 0, 'quantity_user_id' => Auth::id()];
            Batchstock::updateOrCreate(['stock_id' => $this->stock->id], $update);
        }

        if ($this->isDirty('wholesales')) {
            //changes to main store
            $update = ['wholesales' => 0, 'wholsale_user_id' => Auth::id()];
            Batchstock::updateOrCreate(['stock_id' => $this->stock->id], $update);
        }

        if ($this->isDirty('bulksales')) {
            //changes to bulksales
            $update = ['bulksales' => 0, 'bulk_user_id' => Auth::id()];
            Batchstock::updateOrCreate(['stock_id' => $this->stock->id], $update);
        }

        if ($this->isDirty('retail')) {
            //changes to retail
            $update = ['retail' => 0, 'retail_user_id' => Auth::id()];
            Batchstock::updateOrCreate(['stock_id' => $this->stock->id], $update);
        }

        $this->stock->batched = time();
        $this->stock->update();
    }

}
