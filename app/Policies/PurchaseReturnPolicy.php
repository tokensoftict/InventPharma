<?php

namespace App\Policies;

use App\Models\PurchaseReturn;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurchaseReturnPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user) { return false; }

    public function view(User $user, PurchaseReturn $return)
    {
        return userCanView('purchasereturn.show');
    }

    public function create(User $user)
    {
        return userCanView('purchasereturn.create');
    }

    public function update(User $user, PurchaseReturn $return)
    {
        if (!userCanView('purchasereturn.edit')) return false;

        return !$return->isComplete();
    }

    public function edit(User $user, PurchaseReturn $return)
    {
        return $this->update($user, $return);
    }

    public function delete(User $user, PurchaseReturn $return)
    {
        if (!userCanView('purchasereturn.destroy')) return false;

        return !$return->isComplete();
    }

    public function complete(User $user, PurchaseReturn $return)
    {
        if (!userCanView('purchasereturn.complete')) return false;

        return !$return->isComplete();
    }
}
