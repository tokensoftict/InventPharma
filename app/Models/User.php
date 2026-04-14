<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\ModelFilterTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{

    use  ModelFilterTraits;

    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';

    protected $casts = [
        'email_verified_at' => 'datetime',
        'usergroup_id' => 'int',
        'status' => 'bool',
        'department_id' => 'int'
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'usergroup_id',
        'status',
        'phone',
        'username',
        'department_id',
        'remember_token'
    ];

    public static array $profile_fields = ['department', 'name', 'email', 'username', 'password',  'salary', 'status', 'group_id'];

    //protected $with = ['department', 'usergroup'];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function usergroup()
    {
        return $this->belongsTo(Usergroup::class);
    }

    public function getBulkPushData(): array
    {
        return [
            'local_id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'username' => $this->username,
            'status' => $this->status,
            'department_id' => $this->department_id,
        ];
    }

    public function newonlinePush()
    {
        dispatch(new \App\Jobs\PushDataServer([
            'KAFKA_ACTION' => \App\Enums\KafkaAction::SYNC_STAFF,
            'KAFKA_TOPICS' => \App\Enums\KafkaTopics::GENERAL,
            'data' => $this->getBulkPushData()
        ]));
    }

    public function updateonlinePush()
    {
        dispatch(new \App\Jobs\PushDataServer([
            'KAFKA_ACTION' => \App\Enums\KafkaAction::SYNC_STAFF,
            'KAFKA_TOPICS' => \App\Enums\KafkaTopics::GENERAL,
            'data' => $this->getBulkPushData()
        ]));
    }
}
