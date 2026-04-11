<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\MemberGroup;
use App\Models\MemberGroupHistory;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MemberGroupService
{
    protected $startDate = '2026-01-03';

    /**
     * Recalculate member groups for a specific customer.
     *
     * @param Customer $customer
     * @return void
     */
    public function recalculateForCustomer(Customer $customer)
    {
        $eligibleStatuses = [status("Paid"), status("Complete"), status("Dispatched")];

        // 1. Calculate totals for both buckets
        $totals = Invoice::where('customer_id', $customer->id)
            ->whereIn('status_id', $eligibleStatuses)
            ->where('invoice_date', '>=', $this->startDate)
            ->select('in_department', DB::raw('SUM(sub_total) as total'))
            ->groupBy('in_department')
            ->get();

        $retailTotal = 0;
        $otherTotal = 0;

        foreach ($totals as $total) {
            if (in_array($total->in_department, ['retail', 'retail_store'])) {
                $retailTotal += $total->total;
            } else {
                $otherTotal += $total->total;
            }
        }

        // 2. Fetch all groups sorted by threshold DESC to find the highest qualifying group
        $groups = MemberGroup::where('status', 1)->orderBy('min_sales_amount', 'desc')->get();
        $retailGroups = MemberGroup::where('status', 1)->orderBy('retail_min_sales_amount', 'desc')->get();

        $newOtherGroupId = $this->determineQualifyingGroup($otherTotal, $groups, 'min_sales_amount');
        $newRetailGroupId = $this->determineQualifyingGroup($retailTotal, $retailGroups, 'retail_min_sales_amount');

        // 3. Handle Other Member Group Change
        if ($customer->member_group_id != $newOtherGroupId) {
            $currentGroupThreshold = $customer->memberGroup ? $customer->memberGroup->min_sales_amount : -1;
            $newGroupThreshold = 0;
            if ($newOtherGroupId) {
                $newGroup = MemberGroup::find($newOtherGroupId);
                $newGroupThreshold = $newGroup ? $newGroup->min_sales_amount : 0;
            }

            $shouldUpdate = true;
            if ($customer->is_manual_member_group) {
                // If manual, ONLY allow upgrades (higher threshold)
                if ($newGroupThreshold <= $currentGroupThreshold) {
                    $shouldUpdate = false;
                }
            }

            if ($shouldUpdate) {
                $this->logHistory($customer, $customer->member_group_id, $newOtherGroupId, 'other', $otherTotal);
                $customer->member_group_id = $newOtherGroupId;
                // If automated calculation upgrades a manual user, we might want to keep it manual or reset? 
                // Requirement says "must not reduced with automated script if manually upgraded". 
                // Upgrading is fine.
            }
        }

        // 4. Handle Retail Member Group Change
        if ($customer->retail_member_group_id != $newRetailGroupId) {
            $currentRetailThreshold = $customer->retailMemberGroup ? $customer->retailMemberGroup->retail_min_sales_amount : -1;
            $newRetailThreshold = 0;
            if ($newRetailGroupId) {
                $newGroup = MemberGroup::find($newRetailGroupId);
                $newRetailThreshold = $newGroup ? $newGroup->retail_min_sales_amount : 0;
            }

            $shouldUpdateRetail = true;
            if ($customer->is_manual_retail_member_group) {
                if ($newRetailThreshold <= $currentRetailThreshold) {
                    $shouldUpdateRetail = false;
                }
            }

            if ($shouldUpdateRetail) {
                $this->logHistory($customer, $customer->retail_member_group_id, $newRetailGroupId, 'retail', $retailTotal);
                $customer->retail_member_group_id = $newRetailGroupId;
            }
        }

        if ($customer->isDirty(['member_group_id', 'retail_member_group_id'])) {
            $customer->saveQuietly();
            // Sync is handled by Customer model's boot/observer method usually, 
            // but we'll ensure push is triggered if needed.
            if (method_exists($customer, 'updateonlinePush')) {
                //$customer->updateonlinePush();
            }
        }
    }

    /**
     * Determine the highest qualifying group for a given amount.
     */
    protected function determineQualifyingGroup($amount, $groups, $thresholdField)
    {
        foreach ($groups as $group) {
            if ($amount >= $group->{$thresholdField}) {
                return $group->id;
            }
        }
        return null;
    }

    /**
     * Log the membership change in history.
     */
    public function logHistory(Customer $customer, $oldId, $newId, $type, $total, $isManual = false)
    {
        MemberGroupHistory::create([
            'customer_id' => $customer->id,
            'old_member_group_id' => $oldId,
            'new_member_group_id' => $newId,
            'type' => $type,
            'total_spent' => $total,
            'recalculation_date' => Carbon::now()->toDateString(),
            'is_manual' => $isManual,
        ]);
    }
}
