<?php

namespace App\Policies;

use App\Models\SupplierCreditPaymentHistory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupplierPaymentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {

    }


    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        if(!userCanView('supplier.payment.create')) return false;

        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SupplierCreditPaymentHistory  $supplierCreditPaymentHistory
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, SupplierCreditPaymentHistory $supplierCreditPaymentHistory)
    {
        return $this->edit($user, $supplierCreditPaymentHistory);
    }



    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Expense  $expense
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function edit(User $user, SupplierCreditPaymentHistory $supplierCreditPaymentHistory)
    {
        if(!userCanView('supplier.payment.edit')) return false;

        if($supplierCreditPaymentHistory->type === "CREDIT") return false;

        return true;
    }


    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SupplierCreditPaymentHistory  $supplierCreditPaymentHistory
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, SupplierCreditPaymentHistory $supplierCreditPaymentHistory)
    {
        if(!userCanView('supplier.payment.destroy')) return false;

        if($supplierCreditPaymentHistory->type === "CREDIT") return false;

        return true;
    }

}
