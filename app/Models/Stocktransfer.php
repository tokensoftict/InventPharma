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
 * Class Stocktransfer
 *
 * @property int $id
 * @property Carbon $transfer_date
 * @property int|null $user_id
 * @property string|null $from
 * @property string|null $to
 * @property int $status_id
 * @property string|null $note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Status $status
 * @property User|null $user
 * @property Collection|Stocktransferitem[] $stocktransferitems
 *
 * @package App\Models
 */
class Stocktransfer extends Model
{

    use ModelFilterTraits;

	protected $table = 'stocktransfers';

	protected $casts = [
		'transfer_date' => 'datetime',
		'user_id' => 'int',
		'status_id' => 'int'
	];

	protected $fillable = [
		'transfer_date',
		'user_id',
		'from',
		'to',
		'status_id',
		'note'
	];


	public function status()
	{
		return $this->belongsTo(Status::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function stocktransferitems()
	{
		return $this->hasMany(Stocktransferitem::class);
	}
}
