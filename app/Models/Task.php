<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Traits\ModelFilterTraits;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Task
 *
 * @property int $id
 * @property int $module_id
 * @property int $parent_task_id
 * @property string $route
 * @property string $name
 * @property string|null $description
 * @property bool $status
 * @property bool $visibility
 * @property int $order
 * @property string|null $icon
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Module $module
 * @property Collection|Permission[] $permissions
 *
 * @package App\Models
 */
class Task extends Model
{

    use  ModelFilterTraits;

	protected $table = 'tasks';

	protected $casts = [
		'module_id' => 'int',
		'parent_task_id' => 'int',
		'status' => 'bool',
		'visibility' => 'bool',
		'order' => 'int'
	];

	protected $fillable = [
		'module_id',
		'parent_task_id',
		'route',
		'name',
		'description',
		'status',
		'visibility',
		'order',
		'icon'
	];

	public function module()
	{
		return $this->belongsTo(Module::class);
	}

	public function permissions()
	{
		return $this->hasMany(Permission::class);
	}
}
