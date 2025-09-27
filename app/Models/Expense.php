<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Traits\ModelFilterTraits;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Expense
 * 
 * @property int $id
 * @property float $amount
 * @property string|null $department_id
 * @property int|null $expenses_type_id
 * @property int|null $user_id
 * @property Carbon $expense_date
 * @property string|null $purpose
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property ExpensesType|null $expenses_type
 * @property User|null $user
 * @property Department|null $department
 * @package App\Models
 */
class Expense extends Model
{

    use  ModelFilterTraits;

	protected $table = 'expenses';

	protected $casts = [
		'amount' => 'float',
		'expenses_type_id' => 'int',
		'user_id' => 'int',
		'expense_date' => 'datetime'
	];

	protected $fillable = [
		'amount',
		'department_id',
		'expenses_type_id',
		'user_id',
		'expense_date',
		'purpose'
	];

	public function expenses_type()
	{
		return $this->belongsTo(ExpensesType::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
}
