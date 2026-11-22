<?php

namespace App\Providers;

use App\Models\OAuthClient;
use App\Models\User;
use App\Policies\RolePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passport::useClientModel(OAuthClient::class);
        // Only the first-party admin client exists and it skips consent, so there is no consent screen to show.
        Passport::authorizationView(fn () => abort(403, 'Only first-party clients are supported.'));
        Passport::tokensExpireIn(now()->addHour());
        Passport::refreshTokensExpireIn(now()->addDays(30));

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(10)->by($request->ip().'|'.strtolower((string) $request->input('email'))));

        // Signed-in API calls are limited per account, anonymous reads per address (the api vhost passes the real client address on).
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(300)->by($request->user()?->getAuthIdentifier() ?? $request->ip()));
        RateLimiter::for('public', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));

        Gate::policy(Role::class, RolePolicy::class);

        // Superadmin passes every gate check (permissions, policies, can: middleware).
        // Deleting or suspending an account is the exception: UserPolicy protects superadmins from everyone, superadmins included.
        Gate::before(fn (User $user, string $ability, array $args) => $user->isSuperadmin()
            && ! (in_array($ability, ['delete', 'suspend'], true) && ($args[0] ?? null) instanceof User) ? true : null);
    }
}
