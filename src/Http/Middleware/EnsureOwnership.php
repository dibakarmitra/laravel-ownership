<?php

namespace Dibakar\Ownership\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOwnership
{
    public function handle(Request $request, Closure $next, string $param = 'model'): Response
    {
        $model = $request->route($param);
        if (!$model || !method_exists($model, 'isOwnedBy') || $model->isOwnedBy(auth()->user())) {
            return $next($request);
        }
        abort(403, 'You do not own this resource.');
    }
}
