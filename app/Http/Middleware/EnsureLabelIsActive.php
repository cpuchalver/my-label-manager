<?php

namespace App\Http\Middleware;

use App\Models\Label;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLabelIsActive
{
    /**
     * Block every request made inside a suspended label.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $label = Filament::getTenant();

        if ($label instanceof Label && $label->isSuspended()) {
            abort(Response::HTTP_FORBIDDEN, __('This label is suspended.'));
        }

        return $next($request);
    }
}
