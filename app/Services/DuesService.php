<?php

namespace App\Services;

use App\Models\House;

class DuesService
{
    /**
     * Calculate the due amount for a house based on Pasal 5.
     * 
     * Rules:
     * - Berpenghuni: 160.000
     * - Kosong: 110.000
     * - Connected (2 houses, 1 meter): Total 270.000 (135.000 each)
     * - Connected (2 houses, 2 meters): Total 320.000 (160.000 each - same as normal)
     */
    public static function calculate(House $house): int
    {
        // Check for Connected Houses Logic first
        if ($house->is_connected && $house->owner_id) {
            // Find all connected houses for this owner
            $connectedHouses = House::where('owner_id', $house->owner_id)
                                    ->where('is_connected', true)
                                    ->get();
            
            // Logic only applies if there are at least 2 connected houses
            if ($connectedHouses->count() >= 2) {
                $totalMeters = $connectedHouses->sum('meteran_count');

                // Case: 2 connected houses, 1 meter -> Total 270k
                if ($totalMeters == 1) {
                    return 135000; // Split evenly
                }
                
                // Case: 2 connected houses, 2 meters -> Total 320k (160k each)
                // This falls through to standard logic if status is 'berpenghuni', but technically connected usually implies one unit visually.
                // If they are connected and both occupied status, 160k each.
                // If one is empty? Connected houses are usually treated as one unit.
                // Let's assume connected implies 'berpenghuni' for the combo? 
                // "2 Rumah Tersambung" usually means joined.
                
                // If specific rule says "270k" for 1 meter, we return 135k.
                // If 2 meters, it returns 160k (standard).
            }
        }

        // Standard Logic
        if ($house->status_huni === 'kosong') {
            return 110000;
        }

        return 160000; // Berpenghuni
    }
}
