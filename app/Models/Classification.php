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
use Google\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Classification
 *
 * @property int $id
 * @property string|null $name
 * @property bool $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection[] $stocks
 *
 * @package App\Models
 */
class Classification extends Model
{

    use  ModelFilterTraits;

	protected $table = 'classifications';

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

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function newonlinePush()
    {
        dispatch(new PushDataServer(['KAFKA_ACTION'=> KafkaAction::CREATE_CLASSIFICATION, 'KAFKA_TOPICS'=>KafkaTopics::GENERAL, 'action'=>'new','table'=>'classifications', 'endpoint' => 'classifications', 'data'=>$this->getBulkPushData()]));
    }

    public function updateonlinePush()
    {
        dispatch(new PushDataServer(['KAFKA_ACTION'=> KafkaAction::UPDATE_CLASSIFICATION, 'KAFKA_TOPICS'=>KafkaTopics::GENERAL, 'action'=>'update','table'=>'classifications', 'endpoint' => 'classifications' ,'data'=>$this->getBulkPushData()]));
    }


}
