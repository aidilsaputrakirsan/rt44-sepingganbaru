<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Satu-satunya pintu keluar aplikasi ke OpenRouter (format OpenAI-compatible, dengan tool calling).
 *
 * PRIVASI — dipaksa di setiap permintaan, tidak bergantung pengaturan akun:
 *   - provider.data_collection = "deny" : hanya penyedia yang tidak menyimpan/melatih data;
 *   - provider.zdr = true               : hanya endpoint Zero Data Retention;
 *   - provider.require_parameters       : hanya penyedia yang mendukung tools;
 * bila tidak ada penyedia yang memenuhi, permintaan GAGAL (tidak dialihkan diam-diam).
 * Di rt44 dipakai untuk membaca foto KK/KTP yang sengaja difoto/diunggah admin di Data Warga
 * (PembacaKtp, PembacaKk) — hanya foto itu yang dikirim, bukan data warga dari database.
 */
class OpenRouterClient
{
    public const KEBIJAKAN_PENYEDIA = [
        'data_collection' => 'deny',
        'zdr' => true,
        'require_parameters' => true,
        'sort' => 'price',
    ];

    public function aktif(): bool
    {
        return $this->palsu() || filled(config('services.openrouter.api_key'));
    }

    public function palsu(): bool
    {
        return (bool) config('services.openrouter.fake');
    }

    public function model(): string
    {
        return (string) config('services.openrouter.model');
    }

    /**
     * Satu putaran chat completion. Mengembalikan pesan asisten (content dan/atau tool_calls).
     *
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<int, array<string, mixed>>  $tools
     * @param  ?string  $urutPenyedia  ganti cara memilih penyedia (price|latency|throughput); syarat privasi tetap
     * @param  ?string  $model  ganti model untuk panggilan ini (mis. model vision); bawaan = services.openrouter.model
     * @return array{content: ?string, tool_calls: array<int, array<string, mixed>>, message: array<string, mixed>}
     *
     * @throws AiTidakTersedia
     */
    public function chat(array $messages, array $tools = [], ?int $userId = null, string $fitur = 'tanya_data', float $temperature = 0.2, ?string $urutPenyedia = null, ?string $model = null): array
    {
        $body = [
            'model' => $model ?? $this->model(),
            'messages' => $messages,
            'temperature' => $temperature,
            'provider' => $urutPenyedia ? ['sort' => $urutPenyedia] + self::KEBIJAKAN_PENYEDIA : self::KEBIJAKAN_PENYEDIA,
            'usage' => ['include' => true],
        ];
        if ($tools) {
            $body['tools'] = $tools;
            $body['tool_choice'] = 'auto';
        }

        try {
            $response = Http::withToken((string) config('services.openrouter.api_key'))
                ->withHeaders(['HTTP-Referer' => config('app.url'), 'X-Title' => config('app.name')])
                ->timeout((int) config('services.openrouter.timeout', 60))
                ->post(rtrim((string) config('services.openrouter.base_url'), '/') . '/chat/completions', $body);
        } catch (ConnectionException $e) {
            Log::warning('Koneksi ke layanan AI gagal/timeout', ['error' => $e->getMessage()]);
            throw new AiTidakTersedia('Layanan AI tidak menjawab tepat waktu.');
        }

        if ($response->failed()) {
            Log::warning('Layanan AI error', ['status' => $response->status(), 'body' => str($response->body())->limit(1500)->toString()]);
            throw new AiTidakTersedia(match (true) {
                $response->status() === 429 => 'Layanan AI sedang sibuk.',
                in_array($response->status(), [401, 402, 403], true) => 'Konfigurasi atau saldo layanan AI bermasalah.',
                $response->status() === 404 => 'Tidak ada penyedia AI yang memenuhi syarat privasi (ZDR) untuk model ini.',
                default => 'Layanan AI sedang gangguan.',
            });
        }

        $message = $response->json('choices.0.message') ?? [];
        $this->catatPemakaian($userId, $fitur, $response->json('model') ?? $model, $response->json('provider'), $response->json('usage'));

        return [
            'content' => is_string($message['content'] ?? null) ? $message['content'] : null,
            'tool_calls' => $message['tool_calls'] ?? [],
            'message' => array_filter([
                'role' => 'assistant',
                'content' => $message['content'] ?? null,
                'tool_calls' => $message['tool_calls'] ?? null,
            ], fn ($v) => $v !== null),
        ];
    }

    /** Non-fatal: kegagalan pencatatan tidak boleh menggagalkan jawaban. */
    private function catatPemakaian(?int $userId, string $fitur, ?string $model, ?string $provider, ?array $usage): void
    {
        if (! $usage) {
            return;
        }
        try {
            DB::table('ai_usages')->insert([
                'user_id' => $userId,
                'fitur' => $fitur,
                'model' => $model ?? $this->model(),
                'provider' => $provider,
                'prompt_tokens' => (int) ($usage['prompt_tokens'] ?? 0),
                'completion_tokens' => (int) ($usage['completion_tokens'] ?? 0),
                'total_tokens' => (int) ($usage['total_tokens'] ?? 0),
                'biaya_usd' => isset($usage['cost']) ? (float) $usage['cost'] : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Gagal mencatat pemakaian AI', ['error' => $e->getMessage()]);
        }
    }
}
