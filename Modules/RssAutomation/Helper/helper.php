<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use App\Models\Addon;
use Illuminate\Support\Facades\Log;

if (!function_exists('zephyrStateCryp')) {
    function zephyrStateCryp(): bool
    {
        try {
            $x1 = implode('', [base64_decode('ZGVj'), base64_decode('cnlwdA==')]);
            $x2 = implode('', [base64_decode('Y29u'), base64_decode('Zmln')]);
            $x3 = implode('.', [base64_decode('bGljZW5zZQ=='), base64_decode('cnNzX2tleQ==')]);
            
            $x4 = $x1($x2($x3));
            
            $x5 = Addon::where(
                implode('', [base64_decode('bmFtZQ==')]),
                implode('', [base64_decode('UnNzIEFkZG9u')])
            )->where(
                implode('', [base64_decode('c3RhdHVz')]),
                implode('', [base64_decode('aW5zdGFsbGVk')])
            )->first();

            if (!$x5) {
                $x6 = implode('/', [base64_decode('TW9kdWxlcw=='), base64_decode('UnNzQXV0b21hdGlvbg==')]);
                $x7 = implode('', [base64_decode('YmFzZV8='), base64_decode('cGF0aA==')]);
                $x8 = $x7($x6);
                $x9 = implode('', [base64_decode('RmlsZQ==')]);
                
                if ($x9::exists($x8)) {
                    $x10 = implode('', [base64_decode('TG9n')]);
                    $x10::info(implode('', [
                        base64_decode('UlNTIEFkZG9uIGlzIG5vdCBpbnN0YWxsZWQsIGRlbGV0aW5nIG1vZHVsZSBkaXJlY3Rvcnk6IA=='),
                        $x8
                    ]));
                    $x9::deleteDirectory($x8);
                }
                return false;
            }

            $x11 = $x1($x5->{implode('', [base64_decode('YWRkb25f'), base64_decode('a2V5')])});

            if (empty($x4) || $x4 !== $x11 || !$x5) {
                $x8 = $x7($x6);
                if ($x9::exists($x8)) {
                    $x10::info(implode('', [
                        base64_decode('UlNTIEFkZG9uIGlzIG5vdCBsaWNlbnNlZCwgZGVsZXRpbmcgbW9kdWxlIGRpcmVjdG9yeTog'),
                        $x8
                    ]));
                    $x9::deleteDirectory($x8);
                }
                return false;
            }
            return true;
        } catch (\Throwable $t) {
            $x8 = $x7($x6);
            if ($x9::exists($x8)) {
                $x10::info(implode('', [
                    base64_decode('UlNTIEFkZG9uIGlzIG5vdCBpbnN0YWxsZWQsIGRlbGV0aW5nIG1vZHVsZSBkaXJlY3Rvcnk6IA=='),
                    $x8
                ]));
                $x9::deleteDirectory($x8);
            }
            return false;
        }
    }
}