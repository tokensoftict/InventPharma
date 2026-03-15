<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Prescriber
 * 
 * @property int $id
 * @property string $name
 * @property string|null $phone
 * @property bool|null $status
 * @property string|null $company
 * @property string|null $address
 * @property float $amount
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Invoice[] $invoices
 *
 * @package App\Models
 */
class Prescriber extends Model
{
	protected $table = 'prescribers';

	protected $casts = [
		'status' => 'bool',
		'amount' => 'float'
	];

	protected $fillable = [
		'name',
		'phone',
		'status',
		'company',
		'address',
		'amount'
	];

	public function invoices()
	{
		return $this->hasMany(Invoice::class);
	}
}
