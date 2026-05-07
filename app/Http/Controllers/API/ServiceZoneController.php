<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ServiceZone;

class ServiceZoneController extends Controller
{
    public function getZonesForDropdown(Request $request)
    {
        $perPage = $request->get('per_page', 5);
        
        $zones = ServiceZone::where('status', 1)
            ->select('id', 'name')
            ->orderBy('name', 'asc')
            ->paginate($perPage);

        return comman_custom_response([
            'data' => $zones
        ]);
    }

    public function saveZone(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:service_zones,name',
                'coordinates' => ['required', function ($attribute, $value, $fail) {
                    // Handle if coordinates is already an array
                    $decoded = is_array($value) ? $value : json_decode($value, true);
                    
                    if (!is_array($decoded) || count($decoded) < 3) {
                        $fail('Please provide valid zone coordinates with at least 3 points.');
                    }
                    
                    // Validate each coordinate has lat and lng
                    foreach ($decoded as $coord) {
                        if (!isset($coord['lat']) || !isset($coord['lng'])) {
                            $fail('Each coordinate must have lat and lng values.');
                            break;
                        }
                    }
                }],
                'status' => 'nullable|in:0,1'
            ]);

            // Prepare coordinates - ensure it's JSON string for storage
            $coordinates = $request->coordinates;
            if (is_array($coordinates)) {
                $coordinates = json_encode($coordinates);
            }

            // Create zone only
            $zone = ServiceZone::create([
                'name' => $request->name,
                'coordinates' => $coordinates,
                'status' => $request->status ?? 1,
            ]);

            return response()->json([
                'status' => true,
                'message' => __('messages.save_form', ['form' => __('messages.servicezone')]),
                'data' => [
                    'id' => $zone->id,
                    'name' => $zone->name,
                    'coordinates' => $zone->coordinates,
                    'status' => $zone->status
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error saving service zone via API: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while saving the zone. Please try again.'
            ], 500);
        }
    }

    public function getZoneDetail(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|integer|exists:service_zones,id'
            ]);

            $zone = ServiceZone::find($request->id);

            if (!$zone) {
                return response()->json([
                    'status' => false,
                    'message' => __('messages.not_found_entry', ['name' => __('messages.servicezone')])
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => [
                    'id' => $zone->id,
                    'name' => $zone->name,
                    'coordinates' => $zone->coordinates,
                    'status' => $zone->status
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error fetching service zone detail via API: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while fetching the zone detail. Please try again.'
            ], 500);
        }
    }
} 