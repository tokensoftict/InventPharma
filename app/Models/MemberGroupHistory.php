<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberGroupHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'old_member_group_id',
        'new_member_group_id',
        'type',
        'total_spent',
        'recalculation_date',
        'is_manual'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function oldGroup()
    {
        return $this->belongsTo(MemberGroup::class, 'old_member_group_id');
    }

    public function newGroup()
    {
        return $this->belongsTo(MemberGroup::class, 'new_member_group_id');
    }
}
