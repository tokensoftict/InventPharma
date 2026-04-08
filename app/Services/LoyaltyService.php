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
     * @return false|float|int
     * @throws \Throwable
     */
    public final function earnPoints(Customer $customer, Invoice $invoice,  string $reference = null) : float|int|bool
    {
        $pointsRateSetting = app(Settings::class)->store();
        
        // Use in_department to distinguish loyalty type
        $isRetail = in_array($invoice->in_department, ['retail', 'retail_store']);
        $loyaltyType = $isRetail ? 'retail' : 'other';
        $pointRateField = $isRetail ? 'point_rate_retail' : 'point_rate';
        $customerPointsField = $isRetail ? 'retail_loyalty_points' : 'loyalty_points';
        
        $point_rate = $pointsRateSetting->{$pointRateField} ?? 0;

        if($point_rate == 0) return false;

        $points = floor($invoice->sub_total / $point_rate);

        if ($points <= 0) {
            return 0;
        }

        DB::transaction(function () use ($customer, $points, $reference, $invoice, $loyaltyType, $customerPointsField) {

            $transactionExist = LoyaltyTransaction::query()
                ->where([
                    'action_type' => get_class($invoice),
                    'action_id' => $invoice->id,
                    'type' => 'earn'
                ])->first();

            if($transactionExist) {

                // remove the old points from whichever bucket they were in
                $oldField = $transactionExist->loyalty_type === 'retail' ? 'retail_loyalty_points' : 'loyalty_points';
                $customer->decrement($oldField, $transactionExist->points);
                
                $transactionExist->points = $points;
                $transactionExist->customer_id = $customer->id;
                $transactionExist->action_type = get_class($invoice);
                $transactionExist->action_id = $invoice->id;
                $transactionExist->loyalty_type = $loyaltyType;
                $transactionExist->save();
                
                $customer->increment($customerPointsField, $points);

            } else {

                if($customer->id !== 1) {
                    $customer->increment($customerPointsField, $points);
                    LoyaltyTransaction::create([
                        'customer_id' => $customer->id,
                        'points' => $points,
                        'action_type' => get_class($invoice),
                        'action_id' => $invoice->id,
                        'loyalty_type' => $loyaltyType,
                        'type' => 'earn',
                        'reference' => $reference,
                        'description' => 'Points earned from invoice paid (' . $loyaltyType . ')',
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
     * @param string $loyaltyType (retail or other)
     * @return void
     * @throws \Throwable
     */
    public final function redeemPoints(Customer $customer,  Invoice $invoice, int $points, string $reference = null, string $loyaltyType = 'other')
    {
        $field = $loyaltyType === 'retail' ? 'retail_loyalty_points' : 'loyalty_points';

        if ($customer->{$field} < $points) {
            throw new \Exception('Insufficient ' . $loyaltyType . ' loyalty points.');
        }

        DB::transaction(function () use ($customer, $points, $reference, $loyaltyType, $field) {
            $customer->decrement($field, $points);

            LoyaltyTransaction::create([
                'customer_id' => $customer->id,
                'points' => -$points,
                'type' => 'redeem',
                'loyalty_type' => $loyaltyType,
                'reference' => $reference,
                'description' => 'Points redeemed (' . $loyaltyType . ')',
            ]);
        });
    }

    /**
     * @param Customer $customer
     * @param User $user
     * @param int $points
     * @param string $reason
     * @param string $loyaltyType (retail or other)
     * @return void
     * @throws \Throwable
     */
    public final function adjustPoints(Customer $customer, User $user, int $points, string $reason, string $loyaltyType = 'other')
    {
        $field = $loyaltyType === 'retail' ? 'retail_loyalty_points' : 'loyalty_points';

        DB::transaction(function () use ($customer, $points, $reason, $loyaltyType, $field) {
            $customer->increment($field, $points);

            LoyaltyTransaction::create([
                'customer_id' => $customer->id,
                'points' => $points,
                'type' => 'adjust',
                'loyalty_type' => $loyaltyType,
                'description' => $reason . ' (' . $loyaltyType . ')',
            ]);
        });
    }


    public final function deletePoint(Customer $customer, Invoice $invoice,  string $reference = null)
    {
        DB::transaction(function () use ($customer, $reference, $invoice) {

            $transactionExist = LoyaltyTransaction::query()
                ->where([
                    'action_type' => get_class($invoice),
                    'action_id' => $invoice->id,
                    'type' => 'earn'
                ])->first();

            if($transactionExist) {
                $field = $transactionExist->loyalty_type === 'retail' ? 'retail_loyalty_points' : 'loyalty_points';
                $customer->decrement($field, $transactionExist->points);
                $transactionExist->delete();
            }
        });

    }

}
