<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Domain;
use App\Models\DomainLicense;

class PluginController extends Controller
{
    /**
     * Look up an active domain by its name.
     *
     * @param  string  $domainName
     * @return Domain|null
     */
    private function getValidDomain(string $domainName): ?Domain
    {
        return Domain::where('name', $domainName)->where('status', 1)->first();
    }
    
    /**
     * Verify that the provided raw key matches the stored hash + salt + pepper.
     *
     * @param  string          $providedKey
     * @param  DomainLicense   $license
     * @return bool
     */
    private function verifyDomainKey(string $providedKey, DomainLicense $license): bool
    {
        $pepper  = config('license.license_code');
        if (empty($pepper)) {
            // purgeMissingPepper();
            return false;
        }
        $toCheck = $license->salt . $providedKey . $pepper;
        return Hash::check($toCheck, $license->key_hash);
    }

    public function verifyLicenseKey(Request $request)
    {
        $data = $request->validate([
            'domain_name' => 'required|string|max:100',
            'key' => 'required|string|max:200',
        ]);

        try {
            // 1) Fetch and validate domain
            $domain = $this->getValidDomain($data['domain_name']);
            if (! $domain) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthorized access.',
                ], 401);
            }

            // 2) Fetch the most recent unused license
            $license = DomainLicense::where('domain_id', $domain->id)
                        ->where('is_used', false)
                        ->latest('created_at')
                        ->first();

            // 3) Verify key or fail
            if (! $license || ! $this->verifyDomainKey($data['key'], $license)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthorized access.',
                ], 401);
            }

            // 4) Mark as used (one-time)
            $license->markUsed();

            // 5) Success
            return response()->json([
                'status'  => true,
                'message' => 'Key verified.',
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthorized access.',
            ], 500);
        }
    }
}
