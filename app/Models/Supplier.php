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
 * Class Supplier
 * 
 * @property int $id
 * @property string|null $name
 * @property string|null $address
 * @property string|null $email
 * @property string|null $phonenumber
 * @property bool $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Movingstock[] $movingstocks
 * @property Collection|Nearoutofstock[] $nearoutofstocks
 * @property Collection|Purchase[] $purchases
 * @property Collection|Retailnearoutofstock[] $retailnearoutofstocks
 * @property Collection|Stockbatch[] $stockbatches
 * @property Collection|Stockbincard[] $stockbincards
 * @property Collection|Stockopening[] $stockopenings
 * @property Collection|SupplierCreditPaymentHistory[] $supplier_credit_payment_histories
 *
 * @package App\Models
 */
class Supplier extends Model
{

    use  ModelFilterTraits;

	protected $table = 'suppliers';

	protected $casts = [
		'status' => 'bool'
	];

	protected $fillable = [
		'name',
		'address',
		'email',
		'phonenumber',
		'status'
	];
    


    public function getCreditBalanceAttribute(){

        return $this->supplier_credit_payment_histories->sum('amount');
    }

    public function payment()
    {
        return $this->hasOne(SupplierCreditPaymentHistory::class)->ofMany(['id' => 'MAX'], function($query){
            $query->where('amount', '>', 0);
        });
    }
	public function movingstocks()
	{
		return $this->hasMany(Movingstock::class);
	}

	public function nearoutofstocks()
	{
		return $this->hasMany(Nearoutofstock::class);
	}

	public function purchases()
	{
		return $this->hasMany(Purchase::class);
	}

	public function retailnearoutofstocks()
	{
		return $this->hasMany(Retailnearoutofstock::class);
	}

	public function stockbatches()
	{
		return $this->hasMany(Stockbatch::class);
	}

	public function stockbincards()
	{
		return $this->hasMany(Stockbincard::class);
	}

	public function stockopenings()
	{
		return $this->hasMany(Stockopening::class);
	}

	public function supplier_credit_payment_histories()
	{
		return $this->hasMany(SupplierCreditPaymentHistory::class);
	}
}
