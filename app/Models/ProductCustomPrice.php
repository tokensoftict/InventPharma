<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Enums\KafkaAction;
use App\Enums\KafkaTopics;
use App\Jobs\PushDataServer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ProductCustomPrice
 * 
 * @property int $id
 * @property int $stock_id
 * @property int $user_id
 * @property float $price
 * @property int $min_qty
 * @property int $max_qty
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Stock $stock
 * @property User $user
 *
 * @package App\Models
 */
class ProductCustomPrice extends Model
{
	protected $table = 'product_custom_prices';

	protected $casts = [
		'stock_id' => 'int',
		'user_id' => 'int',
		'price' => 'float',
		'min_qty' => 'int',
		'max_qty' => 'int'
	];

	protected $fillable = [
		'stock_id',
		'user_id',
		'price',
		'min_qty',
		'max_qty'
	];

	public function stock()
	{
		return $this->belongsTo(Stock::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}


    public function updateonlinePush()
    {
        if(($this->bulk_price > 0 || $this->retail_price > 0)  && !$this->isDirty('batched')) {
            dispatch(new PushDataServer(['KAFKA_ACTION' => KafkaAction::UPDATE_STOCK, 'KAFKA_TOPICS'=> KafkaTopics::STOCKS, 'action' => 'update', 'table' => 'stock', 'data' => $this->stock->getBulkPushData(), 'endpoint' => 'stocks', 'url'=>onlineBase()."dataupdate/add_or_update_stock"]));
        }
    }
}
