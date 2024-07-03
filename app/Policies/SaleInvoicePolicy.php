<?php
namespace App\Policies;

use App\Models\SaleInvoice;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SaleInvoicePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any sale invoices.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_sale::invoice');
    }

    /**
     * Determine whether the user can view the sale invoice.
     */
    public function view(User $user, SaleInvoice $saleInvoice): bool
    {
        return $user->can('view_sale::invoice');
    }

    /**
     * Determine whether the user can create sale invoices.
     */
    public function create(User $user): bool
    {
        return $user->can('create_sale::invoice');
    }

    /**
     * Determine whether the user can update the sale invoice.
     */
    public function update(User $user, SaleInvoice $saleInvoice): bool
    {
        return $user->can('update_sale::invoice');
    }

    /**
     * Determine whether the user can delete the sale invoice.
     */
    public function delete(User $user, SaleInvoice $saleInvoice): bool
    {
        return $user->can('delete_sale::invoice');
    }

    /**
     * Determine whether the user can bulk delete sale invoices.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_sale::invoice');
    }

    /**
     * Determine whether the user can permanently delete the sale invoice.
     */
    public function forceDelete(User $user, SaleInvoice $saleInvoice): bool
    {
        return $user->can('force_delete_sale::invoice');
    }

    /**
     * Determine whether the user can permanently bulk delete sale invoices.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_sale::invoice');
    }

    /**
     * Determine whether the user can restore the sale invoice.
     */
    public function restore(User $user, SaleInvoice $saleInvoice): bool
    {
        return $user->can('restore_sale::invoice');
    }

    /**
     * Determine whether the user can bulk restore sale invoices.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_sale::invoice');
    }

    /**
     * Determine whether the user can replicate the sale invoice.
     */
    public function replicate(User $user, SaleInvoice $saleInvoice): bool
    {
        return $user->can('replicate_sale::invoice');
    }

    /**
     * Determine whether the user can reorder sale invoices.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_sale::invoice');
    }
}
