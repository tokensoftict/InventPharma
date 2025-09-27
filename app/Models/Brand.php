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
 * Class Brand
 *
 * @property int $id
 * @property string|null $name
 * @property bool $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class Brand extends Model
{

    use  ModelFilterTraits;

	protected $table = 'brands';

	protected $casts = [
		'status' => 'bool'
	];

	protected $fillable = [
		'name',
		'status'
	];

    public function getBulkPushData() : array{
        return [
            'id'=>$this->id,
            'name'=> $this->name,
            'status'=>$this->status
        ];
    }


    public function newonlinePush()
    {
        dispatch(new PushDataServer(['KAFKA_ACTION'=> KafkaAction::CREATE_BRAND, 'KAFKA_TOPICS'=>KafkaTopics::GENERAL, 'action'=>'new','table'=>'brands','data'=>$this->getBulkPushData()]));
    }

    public function updateonlinePush()
    {
        dispatch(new PushDataServer(['KAFKA_ACTION'=> KafkaAction::UPDATE_BRAND, 'KAFKA_TOPICS'=>KafkaTopics::GENERAL, 'action'=>'update','table'=>'brands','data'=>$this->getBulkPushData()]));
    }

}
