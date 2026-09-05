<?php

namespace App\Http\Controllers;

use App\Services\EManuntungService;
use Inertia\Inertia;

/**
 * Peta layanan kota: CCTV live & titik Wifi Gratis di sekitar RT-44,
 * bersumber dari API publik eManuntung Pemkot Balikpapan.
 */
class PetaKotaController extends Controller
{
    public function __construct(private EManuntungService $emanuntung)
    {
    }

    public function index()
    {
        $points = $this->emanuntung->points();

        return Inertia::render('PetaKota/Index', [
            'cctv' => $points['cctv'],
            'wifi' => $points['wifi'],
            'error' => $points['error'],
            'center' => [
                'lat' => (float) config('services.emanuntung.lat'),
                'lng' => (float) config('services.emanuntung.lng'),
            ],
        ]);
    }
}
