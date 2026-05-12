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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class MemberGroup
 * 
 * @property int $id
 * @property string $name
 * @property string $label
 * @property string|null $color
 * @property string|null $bg_color
 * @property float $min_sales_amount
 * @property bool $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Customer[] $customers
 *
 * @package App\Models
 */
class MemberGroup extends Model
{
    use ModelFilterTraits;

	protected $table = 'member_groups';

	protected $casts = [
		'min_sales_amount' => 'float',
		'retail_min_sales_amount' => 'float',
		'member_discount' => 'float',
		'discount_until' => 'date',
		'status' => 'bool'
	];

	protected $fillable = [
		'name',
		'label',
		'color',
		'bg_color',
		'min_sales_amount',
		'retail_color',
		'retail_bg_color',
		'retail_min_sales_amount',
		'member_discount',
		'discount_until',
		'status'
	];

	public function customers()
	{
		return $this->hasMany(Customer::class);
	}


    public function getBulkPushData() : array{
        return [
            'id'=>$this->id,
            'name'=> $this->name,
            'label'=> $this->label,
            'color'=> $this->color,
            'bg_color'=> $this->bg_color,
            'min_sales_amount'=> $this->min_sales_amount,
            'retail_color'=> $this->retail_color,
            'retail_bg_color'=> $this->retail_bg_color,
            'retail_min_sales_amount'=> $this->retail_min_sales_amount,
            'member_discount'=> $this->member_discount,
            'discount_until'=> $this->discount_until,
            
            'status'=>$this->status,
        ];
    }


    public function newonlinePush()
    {
        dispatch(new PushDataServer(['KAFKA_ACTION'=> KafkaAction::CREATE_MEMBER_GROUP, 'KAFKA_TOPICS'=>KafkaTopics::GENERAL, 'action'=>'new','table'=>'member_groups', 'endpoint' => 'member_groups' ,'data'=>$this->getBulkPushData()]));
    }

    public function updateonlinePush()
    {
        dispatch(new PushDataServer(['KAFKA_ACTION'=> KafkaAction::UPDATE_MEMBER_GROUP, 'KAFKA_TOPICS'=>KafkaTopics::GENERAL,'action'=>'update','table'=>'member_groups', 'endpoint' => 'member_groups' ,'data'=>$this->getBulkPushData()]));
    }
}
