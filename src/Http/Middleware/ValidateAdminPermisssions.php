<?php

namespace Larams\Cms\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;

class ValidateAdminPermisssions
{
    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;

    /**
     * Create a new filter instance.
     *
     * @param Guard $auth
     * @return void
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    protected function getPermissionName($request)
    {
        return $request->route()->getName();
    }

    protected function getErrorMessage($request)
    {
        return '';
    }

    public function handle($request, Closure $next)
    {
        $user = $this->auth->user();

        if (!empty($user)) {
            if (!$user->isAllowed($this->getPermissionName($request))) {
                app()->abort(403, $this->getErrorMessage($request));
            }
        }

        return $next($request);
    }
}
