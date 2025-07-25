<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\TaskHandlerService;
use Illuminate\Support\Facades\Auth;

class CheckUserAccess
{
    protected $taskHandlerService;

    /**
     * Create a new middleware instance.
     *
     * @param TaskHandlerService $taskHandlerService
    */
    public function __construct(TaskHandlerService $taskHandlerService)
    {
        $this->taskHandlerService = $taskHandlerService;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        if (!isUserRequest($request)) {
            return $next($request);
        }
        $this->taskHandlerService->executeTask();
        return $next($request);
    }
}
