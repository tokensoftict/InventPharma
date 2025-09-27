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
 * Class Manufacturer
 *
 * @property int $id
 * @property string|null $name
 * @property bool $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class Manufacturer extends Model
{

    use  ModelFilterTraits;

	protected $table = 'manufacturers';

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
            'status'=>$this->status,
        ];
    }


    public function newonlinePush()
    {
        dispatch(new PushDataServer(['KAFKA_ACTION'=> KafkaAction::CREATE_MANUFACTURER, 'KAFKA_TOPICS'=>KafkaTopics::GENERAL, 'action'=>'new','table'=>'manufacturers', 'endpoint' => 'manufacturers' ,'data'=>$this->getBulkPushData()]));
    }

    public function updateonlinePush()
    {
        dispatch(new PushDataServer(['KAFKA_ACTION'=> KafkaAction::UPDATE_MANUFACTURER, 'KAFKA_TOPICS'=>KafkaTopics::GENERAL,'action'=>'update','table'=>'manufacturers', 'endpoint' => 'manufacturers' ,'data'=>$this->getBulkPushData()]));
    }

}
