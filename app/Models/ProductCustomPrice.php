<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Enums\KafkaAction;
use App\Enums\KafkaTopics;
use App\Jobs\PushDataServer;
use App\Jobs\PushStockUpdateToServer;
use App\Traits\ModelFilterTraits;
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
 * @property Stock $stock
 * @property User $user
 *
 * @package App\Models
 */
class ProductCustomPrice extends Model
{
    use ModelFilterTraits;

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
        if(($this->stock->bulk_price > 0 || $this->stock->retail_price > 0)) {
            dispatch(new PushDataServer(['KAFKA_ACTION' => KafkaAction::UPDATE_STOCK, 'KAFKA_TOPICS'=> KafkaTopics::STOCKS, 'action' => 'update', 'table' => 'stock', 'data' => $this->stock->getBulkPushData(), 'endpoint' => 'stocks', 'url'=>onlineBase()."dataupdate/add_or_update_stock"]));
        }
    }

    public function newonlinePush()
    {
        if(($this->stock->bulk_price > 0 || $this->stock->retail_price > 0)) {
            dispatch(new PushDataServer(['KAFKA_ACTION' => KafkaAction::UPDATE_STOCK, 'KAFKA_TOPICS'=> KafkaTopics::STOCKS, 'action' => 'update', 'table' => 'stock', 'data' => $this->stock->getBulkPushData(), 'endpoint' => 'stocks', 'url'=>onlineBase()."dataupdate/add_or_update_stock"]));
        }
    }



    public static function repushToKafka()
    {
        $stock_array = self::query()->select('stock_id')->groupBy('stock_id')->pluck('stock_id')->toArray();
        dispatch(new PushStockUpdateToServer($stock_array));
    }

}
