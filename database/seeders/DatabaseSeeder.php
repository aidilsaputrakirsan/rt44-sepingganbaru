<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\House;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin / Bendahara
        User::factory()->create([
            'name' => 'Bendahara RT-44',
            'email' => 'admin@rt44.com',
            'role' => 'admin',
            'no_rumah' => 'Kantor RT', // Special ID for Admin
            'password' => bcrypt('password'),
        ]);

        // Seed Houses G1/1 - G1/10
        for ($i = 1; $i <= 10; $i++) {
            House::create([
                'blok' => 'G1',
                'nomor' => (string)$i,
                'status_huni' => 'berpenghuni', // Default occupied
            ]);
        }

        // Seed Houses H1/1 - H1/5
        for ($i = 1; $i <= 5; $i++) {
            House::create([
                'blok' => 'H1',
                'nomor' => (string)$i,
                'status_huni' => 'berpenghuni',
            ]);
        }

        // Create some residents and assign to houses
        $resident = User::factory()->create([
            'name' => 'Warga Contoh',
            'email' => 'warga@rt44.com',
            'role' => 'warga',
            'no_rumah' => 'G1/1',
            'password' => bcrypt('password'),
        ]);

        $house = House::where('blok', 'G1')->where('nomor', '1')->first();
        $house->update(['owner_id' => $resident->id]);

        // Example: Empty House
        House::where('blok', 'G1')->where('nomor', '2')->update(['status_huni' => 'kosong']);

        // Example: Connected House
        $owner2 = User::factory()->create(['name' => 'Sultan RT', 'email' => 'sultan@rt44.com', 'password' => bcrypt('password')]);
        // G1/3 & G1/4 connected, 2 meters
        House::where('blok', 'G1')->where('nomor', '3')->update(['owner_id' => $owner2->id, 'is_connected' => true, 'meteran_count' => 1]);
        House::where('blok', 'G1')->where('nomor', '4')->update(['owner_id' => $owner2->id, 'is_connected' => true, 'meteran_count' => 1]);
        // Note: For logic, we need to handle connected houses carefully.
        // If 2 connected houses have 2 meters total (1 each), they pay 320k?
        // Rules:
        // - 2 Rumah Tersambung (2 Meteran Air): Rp 320.000
        // - 2 Rumah Tersambung (1 Meteran Air): Rp 270.000
    }
}
