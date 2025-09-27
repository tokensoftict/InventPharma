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
 * Class Bank
 *
 * @property int $id
 * @property string $name
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Collection|BankAccount[] $bank_accounts
 *
 * @package App\Models
 */
class Bank extends Model
{
    use ModelFilterTraits;

	protected $table = 'banks';

    use  ModelFilterTraits;

	protected $fillable = [
		'name',
		'status'
	];

	public function bank_accounts()
	{
		return $this->hasMany(BankAccount::class);
	}
}
