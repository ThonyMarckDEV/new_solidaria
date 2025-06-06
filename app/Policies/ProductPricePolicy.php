<?php

namespace App\Policies;

use App\Models\Productprice;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductPricePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('ver producto_precio');
        
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Productprice $productprice): bool
    {
        return $user->can('ver producto_precio');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('crear producto_precio');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Productprice $productprice): bool
    {
        return $user->can('editar producto_precio');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Productprice $productprice): bool
    {
        return $user->can('eliminar producto_precio');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Productprice $productprice): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Productprice $productprice): bool
    {
        return false;
    }
}
