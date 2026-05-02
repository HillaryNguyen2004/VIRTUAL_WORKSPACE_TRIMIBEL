<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \App\Models\Document::class => \App\Policies\DocumentPolicy::class,
        \App\Models\AIWorkspace::class => \App\Policies\AIWorkspacePolicy::class,
        \App\Models\AIWorkspaceFile::class => \App\Policies\AIWorkspaceFilePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::before(function ($user, string $ability) {
            // Only apply to dotted permission strings (e.g. 'staff.substaff.create')
            // Skip policy method names like 'assignRole', 'syncPermissions', etc.
            if (!str_contains($ability, '.')) {
                return null; // let the policy / Spatie handle it normally
            }

            // Only apply department-based permissions to user/staff
            $role = $user->roles()->select('id', 'name')->first();
            if (!$role || !in_array($role->name, ['user', 'staff'], true)) {
                return null; // fallback to Spatie normal behavior
            }

            if (empty($user->department_id)) {
                return false;
            }

            // department_role_permissions is the source of truth for user/staff
            $allowed = DB::table('department_role_permissions as drp')
                ->join('permissions as p', 'p.id', '=', 'drp.permission_id')
                ->where('drp.department_id', $user->department_id)
                ->where('drp.role_id', $role->id)     // role_id
                ->where('p.name', $ability)
                ->exists();

            return $allowed; // overrides @can()
        });
    }
}
