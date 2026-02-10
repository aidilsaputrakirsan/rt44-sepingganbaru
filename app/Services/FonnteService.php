<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    protected string $token;
    protected string $baseUrl = 'https://api.fonnte.com/send';

    public function __construct()
    {
        $this->token = config('services.fonnte.token', '');
    }

    /**
     * Send a WhatsApp message via Fonnte API.
     *
     * @param string $target Phone number (e.g. 08123456789)
     * @param string $message Message text
     * @return array{success: bool, message: string}
     */
    public function send(string $target, string $message): array
    {
        if (empty($this->token)) {
            return ['success' => false, 'message' => 'Fonnte token belum dikonfigurasi.'];
        }

        if (empty($target)) {
            return ['success' => false, 'message' => 'Nomor tujuan tidak tersedia.'];
        }

        try {
            $request = Http::withHeaders([
                'Authorization' => $this->token,
            ]);

            // Bypass SSL verification in local environment to avoid cURL error 60
            if (config('app.env') === 'local') {
                $request->withoutVerifying();
            }

            $response = $request->post($this->baseUrl, [
                'target' => $target,
                'message' => $message,
                'countryCode' => '62',
            ]);

            $data = $response->json();

            if ($response->successful() && ($data['status'] ?? false)) {
                return ['success' => true, 'message' => 'Pesan WhatsApp berhasil dikirim.'];
            }

            $errorDetail = $data['reason'] ?? $data['message'] ?? 'Unknown error';
            Log::warning('Fonnte send failed', ['response' => $data]);
            return ['success' => false, 'message' => 'Gagal kirim WA: ' . $errorDetail];
        } catch (\Exception $e) {
            Log::error('Fonnte exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Error koneksi ke Fonnte: ' . $e->getMessage()];
        }
    }
}
