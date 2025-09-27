<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Traits\ModelFilterTraits;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Permission
 *
 * @property int $id
 * @property int $usergroup_id
 * @property int $task_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Task $task
 * @property Usergroup $usergroup
 *
 * @package App\Models
 */
class Permission extends Model
{

    use  ModelFilterTraits;

	protected $table = 'permissions';

	protected $casts = [
		'usergroup_id' => 'int',
		'task_id' => 'int'
	];

	protected $fillable = [
		'usergroup_id',
		'task_id'
	];

	public function task()
	{
		return $this->belongsTo(Task::class);
	}

	public function usergroup()
	{
		return $this->belongsTo(Usergroup::class);
	}
}
