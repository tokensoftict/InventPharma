<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Enums\KafkaAction;
use App\Enums\KafkaTopics;
use App\Jobs\PushDataServer;
use App\Jobs\PushLocalDataServer;
use App\Traits\ModelFilterTraits;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Purchase
 *
 * @property int $id
 * @property int|null $status_id
 * @property int|null $user_id
 * @property int|null $completed_by
 * @property int|null $supplier_id
 * @property string $department
 * @property Carbon|null $date_created
 * @property Carbon|null $date_completed
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property User|null $user
 * @property Status|null $status
 * @property Supplier|null $supplier
 * @property Collection|Purchaseitem[] $purchaseitems
 *
 * @package App\Models
 */
class Purchase extends Model
{


    protected $table = 'purchases';

    use ModelFilterTraits;

    protected $casts = [
        'status_id' => 'int',
        'user_id' => 'int',
        'completed_by' => 'int',
        'supplier_id' => 'int',
        'date_created' => 'datetime',
        'date_completed' => 'datetime'
    ];

    protected $fillable = [
        'status_id',
        'user_id',
        'completed_by',
        'supplier_id',
        'department',
        'date_created',
        'date_completed',
        'created_by'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function complete_by()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function create_by()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseitems()
    {
        return $this->hasMany(Purchaseitem::class);
    }


    public function newonlinePush()
    {
        $this->performOnlinePush();
    }

    public function updateonlinePush()
    {
        $this->performOnlinePush();
    }


    public function performOnlinePush()
    {
        if ($this->status_id === status('Complete')) {

            $data = [];
            foreach ($this->purchaseitems as $purchaseitem) {
                if ($purchaseitem->stock->bulk_price > 0 || $purchaseitem->stock->retail_price > 0) {

                    if (isset($data[$purchaseitem->stock_id])) {
                        $data[$purchaseitem->stock_id]['qty'] += $purchaseitem->qty;
                    } else {
                        $data[$purchaseitem->stock_id] = [
                            'stock_id' => $purchaseitem->stock_id,
                            'po_id' => $purchaseitem->purchase->id,
                            'qty' => $purchaseitem->qty,
                        ];
                        dispatch(new PushLocalDataServer(['action' => 'update', 'table' => 'new_arrivals', 'data' => [
                            'stock_id' => $purchaseitem->stock_id,
                            'po_id' => $purchaseitem->purchase->id,
                            'qty' => $purchaseitem->qty,
                        ]]));
                    }
                }
            }

            $store = $this->department == "quantity" ? 5 : 6;
            dispatch(new PushDataServer(['KAFKA_ACTION' => KafkaAction::NEW_ARRIVAL, 'KAFKA_TOPICS' => KafkaTopics::GENERAL, 'store' => $store, 'action' => 'update', 'endpoint' => 'new_arrivals', 'table' => 'new_arrivals', 'data' => $data]));

        }
    }

    public static function pushMissedToLocalServer() {
        $purchases = Purchase::with(['purchaseitems'])->whereIn('id', range(23080, 23080))->get();
        foreach ($purchases as $purchase) {
            foreach ($purchase->purchaseitems as  $purchaseitem) {
                if ($purchaseitem->stock->bulk_price > 0 || $purchaseitem->stock->retail_price > 0) {
                    dispatch(new PushLocalDataServer(['action' => 'update', 'table' => 'new_arrivals', 'data' => [
                        'stock_id' => $purchaseitem->stock_id,
                        'po_id' => $purchaseitem->purchase->id,
                        'qty' => $purchaseitem->qty,
                    ]]));
                }
            }
        }
    }

}
