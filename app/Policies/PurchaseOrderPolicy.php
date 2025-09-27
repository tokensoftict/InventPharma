<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurchaseOrderPolicy
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
        //
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Purchase $purchase)
    {
        return userCanView("purchase.show");
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return userCanView("purchase.create");
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Purchase $purchase)
    {
        if(!userCanView("purchase.edit")) return false;

        if($purchase->status_id !== status("Draft")) return false;

        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function edit(User $user, Purchase $purchase)
    {
        return $this->update($user, $purchase);
    }


    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Purchase $purchase)
    {
        if(!userCanView("purchase.destroy")) return false;

        if($purchase->status_id !== status("Draft")) return false;

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function complete(User $user, Purchase $purchase)
    {
        if(!userCanView("purchase.complete")) return false;

        if($purchase->status_id === status("Complete")) return false;

        return true;
    }

}
