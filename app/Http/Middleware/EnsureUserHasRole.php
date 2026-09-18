<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $allowedRoles = collect($roles)
            ->map(fn (string $role) => UserRole::tryFrom($role)?->value)
            ->filter()
            ->all();

        abort_unless(in_array($user->role->value, $allowedRoles, true), 403);

        // Deactivated scanners lose access to the scanner area. Admins are never
        // gated by is_active.
        abort_if($user->isScanner() && ! $user->is_active, 403);

        return $next($request);
    }
}
