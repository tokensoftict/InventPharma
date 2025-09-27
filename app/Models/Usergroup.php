<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Traits\ModelFilterTraits;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Class Usergroup
 *
 * @property int $id
 * @property string $name
 * @property bool $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Collection|Permission[] $permissions
 * @property Collection|User[] $users
 *
 * @package App\Models
 */
class Usergroup extends Model
{

    use  ModelFilterTraits;

	protected $table = 'usergroups';

	protected $casts = [
		'status' => 'bool'
	];

	protected $fillable = [
		'name',
		'status'
	];



	public function permissions()
	{
		return $this->hasMany(Permission::class);
	}

	public function users()
	{
		return $this->hasMany(User::class);
	}

    public function tasks()  : belongsToMany
    {
        return $this->belongstoMany(Task::class, 'permissions');
    }

    public function group_tasks() : belongsToMany
    {
        return $this->belongsToMany(Task::class, 'permissions')->withTimestamps();
    }
}
