<?php

namespace App\Http\Middleware;

use App\Services\PusherService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserPresenceMiddleware
{
    protected $pusherService;

    public function __construct(PusherService $pusherService)
    {
        $this->pusherService = $pusherService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Update user's last seen timestamp
            $user->updateLastSeen();
            
            // If user was offline, broadcast that they came online
            if (!$user->is_online) {
                $this->pusherService->userCameOnline($user);
            }
        }

        return $next($request);
    }
}
