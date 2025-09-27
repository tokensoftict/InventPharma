<?php

namespace App\Policies;

use App\Models\Stocktransfer;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StockTransferPolicy
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
     * @param  \App\Models\Stocktransfer  $stocktransfer
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Stocktransfer $stocktransfer)
    {

        return userCanView('transfer.show');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return userCanView('transfer.create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Stocktransfer  $stocktransfer
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Stocktransfer $stocktransfer)
    {
        if($stocktransfer->status_id === status('Approved')) return false;

        if(!userCanView('transfer.edit')) return  false;

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Stocktransfer  $stocktransfer
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Stocktransfer $stocktransfer)
    {
        if($stocktransfer->status_id === status('Approved')) return false;

        if(!userCanView('transfer.destroy')) return  false;

        return true;
    }


    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Stocktransfer  $stocktransfer
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function complete(User $user, Stocktransfer $stocktransfer)
    {
        if($stocktransfer->status_id === status('Approved')) return false;

        if(!userCanView('transfer.complete')) return  false;

        return true;
    }

}
