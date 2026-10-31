<?php

namespace App\Http\Middleware;

use App\Models\Label;
use App\Models\User;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyLabelPermissions
{
    /**
     * Resolve roles and permissions against the current label.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $label = Filament::getTenant();

        if (! $label instanceof Label) {
            return $next($request);
        }

        setPermissionsTeamId($label);

        $user = $request->user();

        if ($user instanceof User) {
            $user->unsetRelation('roles')->unsetRelation('permissions');
        }

        return $next($request);
    }
}
