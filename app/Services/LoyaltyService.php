<?php

namespace App\Services;

use App\Classes\Settings;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\LoyaltyTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LoyaltyService
{
    /**
     * @param Customer $customer
     * @param Invoice $invoice
     * @param string|null $reference
     * @return float|int
     * @throws \Throwable
     */
    public final function earnPoints(Customer $customer, Invoice $invoice,  string $reference = null)
    {



        $pointsRate = app(Settings::class)->store();
        $points = floor($invoice->sub_total / $pointsRate->point_rate ?? 10);

        if ($points <= 0) {
            return 0;
        }

        DB::transaction(function () use ($customer, $points, $reference, $invoice) {

            $transactionExist = LoyaltyTransaction::query()
                ->where([
                    'action_type' => get_class($invoice),
                    'action_id' => $invoice->id,
                    'type' => 'earn'
                ])->first();

            if($transactionExist) {

                $customer->decrement('loyalty_points', $transactionExist->points);// remove the point that was there before
                $transactionExist->points = $points;
                $transactionExist->customer_id = $customer->id;
                $transactionExist->save();
                $customer->increment('loyalty_points', $points);

            } else {

                if($customer->id !== 1) {
                    $customer->increment('loyalty_points', $points);
                    LoyaltyTransaction::create([
                        'customer_id' => $customer->id,
                        'points' => $points,
                        'type' => 'earn',
                        'reference' => $reference,
                        'description' => 'Points earned from invoice paid',
                    ]);
                }

            }
        });

        return $points;
    }

    /**
     * @param Customer $customer
     * @param Invoice $invoice
     * @param int $points
     * @param string|null $reference
     * @return void
     * @throws \Throwable
     */
    public final function redeemPoints(Customer $customer,  Invoice $invoice, int $points, string $reference = null)
    {
        if ($customer->loyalty_points < $points) {
            throw new \Exception('Insufficient loyalty points.');
        }

        DB::transaction(function () use ($customer, $points, $reference) {
            $customer->decrement('loyalty_points', $points);

            LoyaltyTransaction::create([
                'customer_id' => $customer->id,
                'points' => -$points,
                'type' => 'redeem',
                'reference' => $reference,
                'description' => 'Points redeemed',
            ]);
        });
    }

    /**
     * @param Customer $customer
     * @param User $user
     * @param int $points
     * @param string $reason
     * @return void
     * @throws \Throwable
     */
    public final function adjustPoints(Customer $customer, User $user, int $points, string $reason)
    {
        DB::transaction(function () use ($customer, $points, $reason) {
            $customer->increment('loyalty_points', $points);

            LoyaltyTransaction::create([
                'customer_id' => $customer->id,
                'points' => $points,
                'type' => 'adjust',
                'description' => $reason,
            ]);
        });
    }
}
