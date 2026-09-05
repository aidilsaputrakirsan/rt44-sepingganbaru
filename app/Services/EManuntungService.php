<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Ambil data titik layanan kota dari API publik eManuntung (Pemkot Balikpapan).
 *
 * Endpoint dipakai PWA https://pwa-emanuntung.balikpapan.go.id tanpa autentikasi,
 * jadi tidak perlu token/akun. Respons berisi campuran titik (CCTV, Wifi Gratis,
 * posyandu, dll) — dibedakan lewat kolom `status` / `keterangan`.
 *
 * Bentuk item mentah dari API:
 *   id, nama_kecamatan, nama_kelurahan, nama_koordinat, latitude, longitude,
 *   alamat, keterangan, status, distance (km dari titik acuan)
 *
 * Untuk CCTV, `keterangan` berisi URL HLS (.m3u8) yang bisa langsung diputar.
 */
class EManuntungService
{
    private const BASE_URL = 'https://emanuntung.balikpapan.go.id';

    /** Cache agak panjang: titik CCTV/wifi nyaris statis, hemat request ke server Pemkot. */
    private const CACHE_TTL = 1800; // 30 menit

    /** Akronim yang harus tetap kapital setelah judul di-title-case. */
    private const ACRONYMS = [
        'CCTV', 'PTZ', 'HD', 'SPBU', 'RT', 'RW', 'TK', 'SD', 'SMP', 'SMA', 'SMK',
        'RS', 'RSUD', 'PDAM', 'PLN', 'DPRD', 'BRI', 'BNI', 'KM', 'DAM',
    ];

    /**
     * Ambil semua titik di sekitar koordinat acuan, sudah dipisah per kategori.
     *
     * @return array{cctv: array<int, array>, wifi: array<int, array>, error: ?string}
     */
    public function points(?float $lat = null, ?float $lng = null, ?float $radiusKm = null): array
    {
        $lat = $lat ?? (float) config('services.emanuntung.lat');
        $lng = $lng ?? (float) config('services.emanuntung.lng');
        $radiusKm = $radiusKm ?? (float) config('services.emanuntung.radius_km');

        $raw = $this->nearby($lat, $lng);

        if ($raw === null) {
            return [
                'cctv' => [],
                'wifi' => [],
                'error' => 'Gagal mengambil data dari server eManuntung Balikpapan. Coba lagi nanti.',
            ];
        }

        $cctv = [];
        $wifi = [];

        foreach ($raw as $item) {
            // Server mengabaikan parameter radius, jadi saring jaraknya di sini.
            if ((float) ($item['distance'] ?? 0) > $radiusKm) {
                continue;
            }

            if ($this->isCctv($item)) {
                $cctv[] = $this->mapCctv($item);
            } elseif ($this->isWifi($item)) {
                $wifi[] = $this->mapWifi($item);
            }
        }

        // API bisa mengembalikan titik duplikat (mis. "WIFI GRATIS" dan nama posyandu
        // pada koordinat yang sama) — dedupe pakai lat/lng yang dibulatkan.
        $wifi = $this->dedupeByCoordinate($wifi);

        usort($cctv, fn ($a, $b) => $a['distance'] <=> $b['distance']);
        usort($wifi, fn ($a, $b) => $a['distance'] <=> $b['distance']);

        return ['cctv' => $cctv, 'wifi' => $wifi, 'error' => null];
    }

    /**
     * Panggil GET /api/maps/nearby (mengembalikan seluruh titik se-Balikpapan,
     * urut jarak, dengan kolom `distance` dalam kilometer).
     *
     * Mengembalikan null kalau request gagal.
     *
     * @return array<int, array>|null
     */
    private function nearby(float $lat, float $lng): ?array
    {
        $key = sprintf('emanuntung:nearby:%.5f:%.5f', $lat, $lng);

        return Cache::remember($key, self::CACHE_TTL, function () use ($lat, $lng) {
            try {
                $response = Http::timeout(15)
                    ->retry(2, 500, throw: false)
                    ->get(self::BASE_URL . '/api/maps/nearby', [
                        'latitude' => $lat,
                        'longitude' => $lng,
                    ]);

                if (!$response->successful() || $response->json('status') !== true) {
                    Log::warning('eManuntung nearby gagal', [
                        'http' => $response->status(),
                        'body' => $response->json('message'),
                    ]);

                    return null;
                }

                return $response->json('data') ?? [];
            } catch (\Throwable $e) {
                Log::warning('eManuntung nearby error: ' . $e->getMessage());

                return null;
            }
        });
    }

    private function isCctv(array $item): bool
    {
        return strcasecmp((string) ($item['status'] ?? ''), 'CCTV') === 0
            && str_contains((string) ($item['keterangan'] ?? ''), '.m3u8');
    }

    private function isWifi(array $item): bool
    {
        $haystack = ($item['keterangan'] ?? '') . ' ' . ($item['nama_koordinat'] ?? '');

        return stripos($haystack, 'wifi') !== false;
    }

    private function mapCctv(array $item): array
    {
        return [
            'id' => (string) $item['id'],
            // `alamat` yang justru berisi nama lokasi kamera, `nama_koordinat` selalu "CCTV".
            'nama' => $this->titleCase($item['alamat'] ?? 'CCTV'),
            'kelurahan' => $this->titleCase($item['nama_kelurahan'] ?? ''),
            'kecamatan' => $this->titleCase($item['nama_kecamatan'] ?? ''),
            'lat' => (float) $item['latitude'],
            'lng' => (float) $item['longitude'],
            'stream_url' => (string) $item['keterangan'],
            'distance' => round((float) ($item['distance'] ?? 0), 2),
        ];
    }

    private function mapWifi(array $item): array
    {
        // Untuk titik wifi, `nama_koordinat` kadang cuma "WIFI GRATIS" — pakai
        // `alamat` sebagai nama kalau lebih deskriptif.
        $nama = $item['nama_koordinat'] ?? '';
        if (stripos($nama, 'wifi') !== false && !empty($item['alamat'])) {
            $nama = $item['alamat'];
        }

        return [
            'id' => (string) $item['id'],
            'nama' => $this->titleCase($nama ?: 'Wifi Gratis'),
            'alamat' => $this->titleCase($item['alamat'] ?? ''),
            'kelurahan' => $this->titleCase($item['nama_kelurahan'] ?? ''),
            'kecamatan' => $this->titleCase($item['nama_kecamatan'] ?? ''),
            'lat' => (float) $item['latitude'],
            'lng' => (float) $item['longitude'],
            'distance' => round((float) ($item['distance'] ?? 0), 2),
        ];
    }

    /**
     * Buang titik dengan koordinat sama, simpan yang namanya paling deskriptif.
     */
    private function dedupeByCoordinate(array $items): array
    {
        $byCoord = [];

        foreach ($items as $item) {
            $key = sprintf('%.5f,%.5f', $item['lat'], $item['lng']);
            $existing = $byCoord[$key] ?? null;

            // Nama generik "Wifi Gratis" kalah dari nama lokasi yang spesifik.
            if ($existing === null || stripos($existing['nama'], 'wifi gratis') === 0) {
                $byCoord[$key] = $item;
            }
        }

        return array_values($byCoord);
    }

    /**
     * Data dari API banyak yang HURUF BESAR SEMUA — rapikan agar enak dibaca.
     */
    private function titleCase(string $value): string
    {
        $value = trim($value);

        if ($value === '' || $value !== mb_strtoupper($value)) {
            return $value;
        }

        $titled = mb_convert_case(mb_strtolower($value), MB_CASE_TITLE, 'UTF-8');

        // Kembalikan akronim yang umum dipakai di nama lokasi Pemkot ke huruf besar.
        return preg_replace_callback(
            '/\b(' . implode('|', self::ACRONYMS) . ')\b/i',
            fn ($m) => mb_strtoupper($m[1]),
            $titled
        );
    }
}
