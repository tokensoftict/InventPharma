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
 * Class CustomerGroup
 *
 * @property int $id
 * @property string|null $name
 * @property bool $status
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class CustomerGroup extends Model
{

    use ModelFilterTraits;

	protected $table = 'customer_groups';

	protected $casts = [
		'status' => 'bool'
	];

	protected $fillable = [
		'name',
		'status',
		'description'
	];

    public function getBulkPushData() : array{
        return [
            'id'=> $this->id,
            'name'=> $this->name,
            'status'=> $this->status,
            'description'=> $this->description,
        ];
    }

    public function newonlinePush()
    {
        dispatch(new PushDataServer(['KAFKA_ACTION'=> KafkaAction::CREATE_CUSTOMER_GROUP, 'KAFKA_TOPICS'=>KafkaTopics::GENERAL, 'action'=>'new','table'=>'customer_groups','data'=>$this->getBulkPushData()]));
    }

    public function updateonlinePush()
    {
        dispatch(new PushDataServer(['KAFKA_ACTION'=> KafkaAction::UPDATE_CUSTOMER_GROUP, 'KAFKA_TOPICS'=>KafkaTopics::GENERAL, 'action'=>'update','table'=>'customer_groups','data'=>$this->getBulkPushData()]));
    }

    public function deleteonlinePush()
    {
        dispatch(new PushDataServer(['KAFKA_ACTION'=> KafkaAction::DELETE_CUSTOMER_GROUP, 'KAFKA_TOPICS'=>KafkaTopics::GENERAL, 'action'=>'delete','table'=>'customer_groups','data'=>$this->getBulkPushData()]));
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'customer_group_id');
    }
}
