<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Traits\VerifyDomain;
use Illuminate\Support\Facades\Artisan;

class DomainMiddleware
{
    use VerifyDomain;

    /**
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {

        $db_var = "verify_user";
        if (!defined($db_var)) {
            $this->purgeChace();
        }

        $checkVal = constant('verify_user');
        $expectedDomain = decrypt(config("license.$checkVal"));

        if ($_SERVER['HTTP_HOST'] !== $expectedDomain && $_SERVER['HTTP_HOST'] !== 'localhost') {
            $this->purgeChace();
        }

        return $next($request);
    }

    /**
     *
     * Run Optimize Clear
     */
    protected function purgChace()
    {
        Artisan::call('optimize:clear');
    }
}
