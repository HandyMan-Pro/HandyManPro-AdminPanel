<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ProviderZoneMapping;
use App\Providers\RouteServiceProvider;
use App\Traits\NotificationTrait;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\ProviderDocument;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;


class RegisteredUserController extends Controller
{
    use NotificationTrait;
    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $othersetting = \App\Models\Setting::where('type', 'OTHER_SETTING')->first();
        $nearby_provider = 0;
        if ($othersetting) {
            $decoded = json_decode($othersetting->value, true);
            $nearby_provider = $decoded['nearby_provider'] ?? 0;
        }
        return view('auth.register', compact('nearby_provider'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $othersetting = \App\Models\Setting::where('type', 'OTHER_SETTING')->first();
        $nearby_provider = 0;
        if ($othersetting) {
            $decoded = json_decode($othersetting->value, true);
            $nearby_provider = $decoded['nearby_provider'] ?? 0;
        }

        $baseRules = [
            'username'  => 'required|string|max:255|unique:users',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone_number' => 'required|unique:users,contact_number',
            'password' => ['required', 'string', 'min:8', 'max:12', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,12}$/'],
            'zone_id' => 'nullable|required_if:usertype,provider|array',
            'zone_id.*' => 'exists:service_zones,id',
            'address' => ($nearby_provider) ? 'required_if:usertype,provider' : 'nullable',
            'longitude' => ($nearby_provider) ? 'required_if:usertype,provider' : 'nullable',
            'latitude' => ($nearby_provider) ? 'required_if:usertype,provider' : 'nullable',
            // 'country_id' => ($nearby_provider) ? 'required_if:usertype,provider' : 'nullable',
            // 'state_id' => ($nearby_provider) ? 'required_if:usertype,provider' : 'nullable',
            // 'city_id' => ($nearby_provider) ? 'required_if:usertype,provider' : 'nullable',
        ];

        // Check if there are any active provider documents
        if ($request->usertype === 'provider') {
            $activeDocuments = \App\Models\Documents::where('type', 'provider_document')
                ->where('status', 1)
                ->exists();
            
            if ($activeDocuments) {
                $baseRules['document_id'] = 'required|array';
                $baseRules['document_id.*'] = 'exists:documents,id';
            } else {
                $baseRules['document_id'] = 'nullable|array';
                $baseRules['document_id.*'] = 'exists:documents,id';
            }
        }


        $documentIds = $request->input('document_id', []);
        $attributeNames = [];
        if ($request->usertype === 'provider') {
            foreach ($documentIds as $i => $docId) {
                $document = \App\Models\Documents::where('status',1)->find($docId);
                if ($document) {
                    $docName = strtolower(preg_replace('/\s+/', '_', $document->name));
                    $name = 'provider ' . strtolower($document->name);
                    $field = "provider_document_{$i}";
                    $attributeNames[$field] = $name;
                    if ($document->is_required) {
                        $baseRules[$field] = 'required|file|mimes:jpeg,jpg,png,pdf';
                    } else {
                        $baseRules[$field] = 'nullable|file|mimes:jpeg,jpg,png,pdf';
                    }
                }
            }
        }

        $validator = Validator::make($request->all(), $baseRules, [
            'password.regex' => __('messages.password_must_contain'),
        ]);
        $validator->setAttributeNames($attributeNames);
        $validator->validate();

        if (!empty($request->usertype)) {
            $userType = $request->usertype;
        } else {
            $userType = 'user';
        }

        if (!empty($request->designation)) {
            $designation = $request->designation;
        } else {
            $designation = Null;
        }
        $email = $request->email;
        $username = $request->username;
        $user = User::withTrashed()
            ->where(function ($query) use ($email, $username) {
                $query->where('email', $email)->orWhere('username', $username);
            })
            ->first();
        if ($user) {
            $message = trans('messages.login_form');
            return redirect()->back()->withErrors(['message' => $message]);
        } else {
            $user = User::create([
                'username' => $username ?? null,
                'first_name' => $request->first_name ?? null,
                'last_name' => $request->last_name ?? null,
                'contact_number' => $request->phone_number ?? null,
                'user_type' => $userType,
                'display_name' => $request->first_name . " " . $request->last_name ?? null,
                'email' => $email ?? null,
                'password' => Hash::make($request->password) ?? null,
                'designation' => $request->designation,
                "usertype" => $request->usertype,
                "provider_id" => $request->provider_id,
                "providertype_id" => $request->providertype_id,
                "handymantype_id" => $request->handymantype_id,
                'address' => $request->address,
                'country_id' => $request->country_id,
                'state_id' => $request->state_id,
                'city_id' => $request->city_id,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'status' => ($userType === 'provider' || $userType === 'handyman') ? 0 : 1,
            ]);

            if ($request->profile_image != null) {
                storeMediaFile($user, [$request->profile_image], 'profile_image');
            }

            if (
                $user->user_type === 'provider' &&
                $request->has('document_id') &&
                is_array($request->document_id)
            ) {
                foreach ($request->document_id as $index => $docId) {
                    $fileKey = "provider_document_$index";
                    $file = $request->file($fileKey);

                    if (!empty($docId) && $file) {
                        $providerDoc = ProviderDocument::create([
                            'provider_id' => $user->id,
                            'document_id' => $docId,
                        ]);

                        storeMediaFile($providerDoc, [$file], 'provider_document');
                    }
                }
            }
            // Create zone mapping for provider
            if ($userType === 'provider' && $request->zone_id && is_array($request->zone_id)) {
                foreach ($request->zone_id as $zoneId) {
                    ProviderZoneMapping::create([
                        'provider_id' => $user->id,
                        'zone_id' => $zoneId
                    ]);
                }
            }

            if ($user->user_type == 'user' || $user->user_type == 'provider' || $user->user_type == 'handyman') {
                $user->assignRole($user->user_type);
                $verificationLink = route('verify', ['id' =>  Crypt::encrypt($user->id)]);
                try {
                    $this->sendNotification([
                        'activity_type'    => 'email_verification',
                        'user_id'           => $user->id,
                        'user_type'         => $user->user_type,
                        'user_name'         => $user->display_name,
                        'user_email'        => $user->email,
                        'verification_link' => $verificationLink,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Registration verification notification failed: ' . $e->getMessage(), ['user_id' => $user->id, 'email' => $user->email]);
                }
                return redirect()->route('auth.login');
            }
        }
        event(new Registered($user));
        if (!empty($userType)) {
            $user->assignRole($userType);
        } else {
            $user->assignRole('user');
        }
        if ($request->register === 'user_register') {
            return redirect(RouteServiceProvider::FRONTEND);
        } else {
            return redirect(route('auth.login'));
        }
    }
}
