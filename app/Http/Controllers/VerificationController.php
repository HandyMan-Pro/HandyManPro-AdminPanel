<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Traits\NotificationTrait;
use Illuminate\Contracts\Encryption\DecryptException;

class VerificationController extends Controller
{
    use NotificationTrait;

    public function verify(Request $request, $id)
    {
        try{
            $user_id = decrypt($id);
        }catch(DecryptException $e){
            abort(404);
        }
        $user = User::find($user_id);
        if (!$user) {
            abort(404);
        }
        $user->is_email_verified = 1;
        $user->email_verified_at = now();
        $user->save();

        // Send "New User Registration" notification after email is verified (user/provider/handyman only)
        if (in_array($user->user_type, ['user', 'provider', 'handyman'])) {
            try {
                $this->sendNotification([
                    'activity_type' => 'register',
                    'user_id'       => $user->id,
                    'user_type'     => $user->user_type,
                    'user_email'    => $user->email,
                    'user_name'     => $user->display_name,
                ]);
            } catch (\Throwable $e) {
                // Log but do not block redirect
                \Illuminate\Support\Facades\Log::error('Register notification after verification failed: ' . $e->getMessage(), ['user_id' => $user->id]);
            }
        }

        // Send free plan assigned notification if provider already has free plan assigned during registration
        if ($user->user_type === 'provider' && default_earning_type() === 'subscription') {
            $existingFreePlan = \App\Models\ProviderSubscription::where('user_id', $user->id)
                ->where('identifier', 'free')
                ->where('status', 'active')
                ->first();

            if ($existingFreePlan) {
                $freePlan = \App\Models\Plans::find($existingFreePlan->plan_id);
                if ($freePlan) {
                    try {
                        $this->sendNotification([
                            'activity_type'  => 'provider_free_plan_assigned',
                            'user_id'        => $user->id,
                            'provider_name'  => $user->display_name ?? $user->username ?? '',
                            'plan_title'     => $freePlan->title,
                            'plan_duration'  => $freePlan->duration ?? '',
                            'plan_type'      => $freePlan->type ?? '',
                            'plan_description' => $freePlan->description ?? '',
                        ]);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error('Provider free plan assigned notification failed: ' . $e->getMessage(), ['user_id' => $user->id]);
                    }
                }
            }
        }

        return redirect()->route('verify-success')->with([
            'verified' => true,
            'user_type' => $user->user_type
        ]);
    }


    public function verifySuccess()
    {
         $userType = session('user_type');
        return view('verification.verify-success');
    }
}
