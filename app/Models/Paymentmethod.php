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
 * Class Paymentmethod
 *
 * @property int $id
 * @property string|null $name
 * @property bool|null $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Collection|Creditpaymentlog[] $creditpaymentlogs
 * @property Collection|Paymentmethoditem[] $paymentmethoditems
 *
 * @package App\Models
 */
class Paymentmethod extends Model
{
    use  ModelFilterTraits;

	protected $table = 'paymentmethods';

	protected $casts = [
		'status' => 'bool'
	];

	protected $fillable = [
		'name',
		'status'
	];

	public function creditpaymentlogs()
	{
		return $this->hasMany(Creditpaymentlog::class);
	}

	public function paymentmethoditems()
	{
		return $this->hasMany(Paymentmethoditem::class);
	}
}
