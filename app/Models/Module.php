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
 * Class Module
 *
 * @property int $id
 * @property string $name
 * @property string|null $label
 * @property string|null $description
 * @property bool $status
 * @property bool $visibility
 * @property int $order
 * @property string|null $icon
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Collection|Task[] $tasks
 *
 * @package App\Models
 */
class Module extends Model
{

    use  ModelFilterTraits;

	protected $table = 'modules';

	protected $casts = [
		'status' => 'bool',
		'visibility' => 'bool',
		'order' => 'int'
	];

	protected $fillable = [
		'name',
		'label',
		'description',
		'status',
		'visibility',
		'order',
		'icon'
	];

	public function tasks()
	{
		return $this->hasMany(Task::class);
	}
}
