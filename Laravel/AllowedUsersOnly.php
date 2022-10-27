<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AllowedUsersOnly
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = \Auth::guard('api')->user();

        if($user == null){
            return response(
                [
                    "message" => "Unauthenticated.",
                ],
                401
            );
        }

        if ($user['user_type_id'] == 6) { // this is a super admin no need to check anything
            return $next($request);
        }

        $isAllowed = $user->isAllowed(null);

        if ($isAllowed) {
            return $next($request);
        } else {
            return response(
                [
                    "message" => "Unauthenticated.",
                ],
                401
            );
        }
    }
}
