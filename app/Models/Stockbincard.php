<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Stockbincard
 * 
 * @property int $id
 * @property int|null $stock_id
 * @property string $bin_card_type
 * @property string $bin_card_date
 * @property int|null $user_id
 * @property int $in_qty
 * @property int $out_qty
 * @property int $sold_qty
 * @property int $return_qty
 * @property int|null $stockbatch_id
 * @property string|null $to_department
 * @property string|null $from_department
 * @property int|null $supplier_id
 * @property int|null $invoice_id
 * @property int|null $stocktransfer_id
 * @property int|null $purchase_id
 * @property int|null $balance
 * @property string|null $comment
 * @property float|null $department_balance
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Invoice|null $invoice
 * @property Purchase|null $purchase
 * @property Stock|null $stock
 * @property Stockbatch|null $stockbatch
 * @property Stocktransfer|null $stocktransfer
 * @property Supplier|null $supplier
 * @property User|null $user
 *
 * @package App\Models
 */
class Stockbincard extends Model
{
	protected $table = 'stockbincards';

	protected $casts = [
		'stock_id' => 'int',
		'user_id' => 'int',
		'in_qty' => 'int',
		'out_qty' => 'int',
		'sold_qty' => 'int',
		'return_qty' => 'int',
		'stockbatch_id' => 'int',
		'supplier_id' => 'int',
		'invoice_id' => 'int',
		'stocktransfer_id' => 'int',
		'purchase_id' => 'int',
		'balance' => 'int',
		'department_balance' => 'float'
	];

	protected $fillable = [
		'stock_id',
		'bin_card_type',
		'bin_card_date',
		'user_id',
		'in_qty',
		'out_qty',
		'sold_qty',
		'return_qty',
		'stockbatch_id',
		'to_department',
		'from_department',
		'supplier_id',
		'invoice_id',
		'stocktransfer_id',
		'purchase_id',
		'balance',
		'comment',
		'department_balance'
	];

	public function invoice()
	{
		return $this->belongsTo(Invoice::class);
	}

	public function purchase()
	{
		return $this->belongsTo(Purchase::class);
	}

	public function stock()
	{
		return $this->belongsTo(Stock::class);
	}

	public function stockbatch()
	{
		return $this->belongsTo(Stockbatch::class);
	}

	public function stocktransfer()
	{
		return $this->belongsTo(Stocktransfer::class);
	}

	public function supplier()
	{
		return $this->belongsTo(Supplier::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
