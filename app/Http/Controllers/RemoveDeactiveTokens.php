<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PushSubscriptionHead;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RemoveDeactiveTokens extends Controller
{
    public function index()
    {
        $inactiveCount = PushSubscriptionHead::where('status', 0)
            ->with(['meta', 'payload', 'domain'])
            ->count();

        return view('deactive_token.index', compact('inactiveCount'));
    }

    public function removeToken(Request $request)
    {
        // Strict validation
        $validator = Validator::make($request->all(), [
            'confirmation' => [
                'required',
                'string',
                'size:6', // "DELETE" is 6 characters
                function ($attribute, $value, $fail) {
                    if ($value !== 'DELETE') {
                        $fail('The confirmation text must be exactly "DELETE".');
                    }
                }
            ]
        ]);

        if ($validator->fails()) {
            return redirect()->route('deactive.index')
                ->with('status', 'error')
                ->with('message', 'Invalid confirmation. Please try again.');
        }

        try {
            DB::transaction(function () {
                // Get inactive tokens (status = 0)
                $inactiveTokens = PushSubscriptionHead::where('status', 0)->get();

                // Delete related records first to maintain referential integrity
                foreach ($inactiveTokens as $token) {
                    $token->meta()->delete();
                    $token->payload()->delete();
                    $token->sends()->delete();
                    $token->delete();
                }
            });

            return redirect()->route('deactive.index')
                ->with('status', 'success')
                ->with('message', 'All inactive tokens have been permanently deleted.');
                
        } catch (\Exception $e) {
            return redirect()->route('deactive.index')
                ->with('status', 'error')
                ->with('message', 'Failed to delete tokens: ' . $e->getMessage());
        }
    }
}