<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Enums\KafkaAction;
use App\Enums\KafkaTopics;
use App\Jobs\PushDataServer;
use App\Traits\ModelFilterTraits;
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
    use ModelFilterTraits;

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

    public function updateonlinePush()
    {
        if(($this->parentStock->bulk_price > 0 || $this->parentStock->retail_price > 0)) {
            dispatch(new PushDataServer(['KAFKA_ACTION' => KafkaAction::UPDATE_STOCK, 'KAFKA_TOPICS'=> KafkaTopics::STOCKS, 'action' => 'update', 'table' => 'stock', 'data' => $this->parentStock->getBulkPushData(), 'endpoint' => 'stocks', 'url'=>onlineBase()."dataupdate/add_or_update_stock"]));
        }
    }


    public function newonlinePush()
    {
        if(($this->stock->bulk_price > 0 || $this->stock->retail_price > 0)) {
            dispatch(new PushDataServer(['KAFKA_ACTION' => KafkaAction::UPDATE_STOCK, 'KAFKA_TOPICS'=> KafkaTopics::STOCKS, 'action' => 'update', 'table' => 'stock', 'data' => $this->stock->getBulkPushData(), 'endpoint' => 'stocks', 'url'=>onlineBase()."dataupdate/add_or_update_stock"]));
        }
    }
}
