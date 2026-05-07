<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Service;
use App\Models\ProviderSlotMapping;
use Carbon\Carbon;

class BookingSlotService
{
    /**
     * Get complete slot information for a specific date
     * 
     * @param string $date Date in Y-m-d format
     * @param int $serviceId Service ID (required)
     * @return array Array with date, booked_slots, available_slots, and counts
     */
    public function getSlotInfo($date, $serviceId)
    {
        $masterSlots = $this->getProviderSlots($serviceId, $date);
        $bookedSlots = $this->getBookedSlots($date, $serviceId);
        $availableSlots = array_values(array_diff($masterSlots, $bookedSlots));

        return [
            'date' => $date,
            'service_id' => $serviceId,
            'booked_slots' => $bookedSlots,
            'available_slots' => $availableSlots,
            'total_slots' => count($masterSlots),
            'booked_count' => count($bookedSlots),
            'available_count' => count($availableSlots)
        ];
    }

    /**
     * Get available time slots for a specific date
     * 
     * @param string $date Date in Y-m-d format
     * @param int $serviceId Service ID (required)
     * @return array Array of available time slots
     */
    public function getAvailableSlots($date, $serviceId)
    {
        $masterSlots = $this->getProviderSlots($serviceId, $date);
        
        if (empty($masterSlots)) {
            return [];
        }

        $bookedSlots = $this->getBookedSlots($date, $serviceId);
        
        return array_values(array_diff($masterSlots, $bookedSlots));
    }

    /**
     * Get booked time slots for a specific date
     * 
     * @param string $date Date in Y-m-d format
     * @param int $serviceId Service ID (required)
     * @return array Array of booked time slots in HH:MM format
     */
    public function getBookedSlots($date, $serviceId)
    {
        try {
            return Booking::whereDate('date', $date)
                ->where('service_id', $serviceId)
                ->whereNotNull('booking_slot')
                ->pluck('booking_slot')
                ->map(fn($slot) => Carbon::parse($slot)->format('H:i'))
                ->unique()
                ->values()
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }


    /**
     * Get provider's available time slots from provider_slot_mappings table
     * 
     * @param int $serviceId Service ID
     * @param string $date Date in Y-m-d format
     * @return array Array of time slots in HH:MM format
     */
    protected function getProviderSlots($serviceId, $date)
    {
        try {
            $service = Service::find($serviceId);
            
            if (!$service || $service->is_slot == 0) {
                return [];
            }

            $dayOfWeek = strtolower(Carbon::parse($date)->format('D'));

            return ProviderSlotMapping::where('provider_id', $service->provider_id)
                ->where('days', $dayOfWeek)
                ->where('status', 1)
                ->orderBy('start_at', 'asc')
                ->get()
                ->map(fn($slot) => Carbon::parse($slot->start_at)->format('H:i'))
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }
}
