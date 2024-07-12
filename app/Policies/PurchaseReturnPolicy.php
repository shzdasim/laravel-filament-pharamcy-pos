<?php

namespace App\Policies;

use App\Models\PurchaseReturn;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurchaseReturnPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any purchase invoices.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_purchase::return');
    }

    /**
     * Determine whether the user can view the purchase invoice.
     */
    public function view(User $user, PurchaseReturn $purchaseReturn): bool
    {
        return $user->can('view_purchase::return');
    }

    /**
     * Determine whether the user can create purchase invoices.
     */
    public function create(User $user): bool
    {
        return $user->can('create_purchase::return');
    }

    /**
     * Determine whether the user can update the purchase invoice.
     */
    public function update(User $user, PurchaseReturn $purchaseReturn): bool
    {
        return $user->can('update_purchase::return');
    }

    /**
     * Determine whether the user can delete the purchase invoice.
     */
    public function delete(User $user, PurchaseReturn $purchaseReturn): bool
    {
        return $user->can('delete_purchase::return');
    }

    /**
     * Determine whether the user can bulk delete purchase invoices.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_purchase::return');
    }

    /**
     * Determine whether the user can permanently delete the purchase invoice.
     */
    public function forceDelete(User $user, PurchaseReturn $purchaseReturn): bool
    {
        return $user->can('force_delete_purchase::return');
    }

    /**
     * Determine whether the user can permanently bulk delete purchase invoices.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_purchase::return');
    }

    /**
     * Determine whether the user can restore the purchase invoice.
     */
    public function restore(User $user, PurchaseReturn $purchaseReturn): bool
    {
        return $user->can('restore_purchase::return');
    }

    /**
     * Determine whether the user can bulk restore purchase invoices.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_purchase::return');
    }

    /**
     * Determine whether the user can replicate the purchase invoice.
     */
    public function replicate(User $user, PurchaseReturn $purchaseReturn): bool
    {
        return $user->can('replicate_purchase::return');
    }

    /**
     * Determine whether the user can reorder purchase invoices.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_purchase::return');
    }
}
