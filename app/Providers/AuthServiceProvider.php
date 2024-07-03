<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;

use App\Models\Blog\Category as BlogPostCategory;
use App\Models\Blog\Post as BlogPost;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\SaleInvoice;
use App\Models\Supplier;
use App\Models\User;
use App\Policies\ActivityPolicy;
use App\Policies\Blog\CategoryPolicy as BlogPostCategoryPolicy;
use App\Policies\Blog\PostPolicy as BlogPostPolicy;
use App\Policies\BrandPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\ExceptionPolicy;
use App\Policies\ProductPolicy;
use App\Policies\PurchaseInvoicePolicy;
use App\Policies\SaleInvoicePolicy;
use App\Policies\SupplierPolicy;
use App\Policies\UserPolicy;
use BezhanSalleh\FilamentExceptions\Models\Exception;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Spatie\Activitylog\Models\Activity;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Activity::class => ActivityPolicy::class,
        Brand::class => BrandPolicy::class,
        Customer::class => CustomerPolicy::class,
        Product::class => ProductPolicy::class,
        Supplier::class => SupplierPolicy::class,
        User::class => UserPolicy::class,
        Exception::class => ExceptionPolicy::class,
        SaleInvoice::class => SaleInvoicePolicy::class,
        PurchaseInvoice::class => PurchaseInvoicePolicy::class,
        'Spatie\Permission\Models\Role' => 'App\Policies\RolePolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
