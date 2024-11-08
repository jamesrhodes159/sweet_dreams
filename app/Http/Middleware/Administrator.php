<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Administrator
{
    protected $auth;

    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, $permission = null)
    {
         if($this->auth->user()->user_type == 'admin') {
            return $next($request);
        } else {
            if($permission != null && ($permission_parts = explode("|", $permission))) {
                $action_permission_array = array_filter($permission_parts, function ($value) use ($request) {
                   return ($parts = explode("-", $value)) && $parts[0] == $request->route()->getActionMethod();
                });
                if($action_permission_array && count($action_permission_array)) {
                    $action_permission = array_pop($action_permission_array);
                    $parts = explode("-", $action_permission);
                    if(isset($parts[1]) && $this->auth->user()->can($parts[1])) {
                        return $next($request);
                    }
                }
            }
        }
        return redirect('/admin/forbidden');
    }
}
