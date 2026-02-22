<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setting;
use App\Models\House;
use App\Models\Due;
use App\Services\FonnteService;
use Carbon\Carbon;

class SendAutoReminders extends Command
{
    protected $signature = 'reminders:send-auto';
    protected $description = 'Automatically send WhatsApp reminders to residents with unpaid dues.';

    public function handle()
    {
        // 1. Check if feature is enabled
        $setting = Setting::find('auto_reminder');
        if (!$setting || $setting->value !== '1') {
            $this->info('Auto-reminder feature is disabled in settings. Skipping.');
            return;
        }

        $this->info('Starting Auto-Reminder process...');

        $year = Carbon::today()->year;
        
        // Target: Houses that are NOT subsidized, have an owner, and owner has a phone number
        $houses = House::with('owner')
            ->where('is_subsidized', false)
            ->whereNotNull('owner_id')
            ->get();

        $fonnte = new FonnteService();
        $sentCount = 0;

        foreach ($houses as $house) {
            $owner = $house->owner;
            if (!$owner || empty($owner->phone_number)) continue;

            $result = $this->buildReminderMessage($house, $year);

            if ($result) {
                // Send WA
                $this->info("Sending reminder to {$house->blok}/{$house->nomor} ({$owner->name})...");
                $sendResult = $fonnte->send($owner->phone_number, $result['message']);
                
                if ($sendResult['success']) {
                    $sentCount++;
                } else {
                    $this->error("Failed to send to {$owner->phone_number}: " . $sendResult['message']);
                }

                // Sleep to prevent rate limit
                sleep(2);
            }
        }

        $this->info("Auto-Reminder process completed. Sent {$sentCount} reminders.");
    }

    private function buildReminderMessage(House $house, int $year): ?array
    {
        $owner = $house->owner;
        if (!$owner) return null;

        $today = Carbon::today();
        if ($today->year == $year) {
            $cutoffMonth = $today->day >= 5 ? $today->month : $today->month - 1;
        } else if ($today->year > $year) {
            $cutoffMonth = 12;
        } else {
            $cutoffMonth = 0;
        }

        if ($cutoffMonth < 1) return null;

        $cutoffDate = Carbon::create($year, $cutoffMonth, 1)->endOfMonth();

        $unpaidDues = Due::with('payments')
            ->where('house_id', $house->id)
            ->whereIn('status', ['unpaid', 'overdue'])
            ->whereYear('period', $year)
            ->where('period', '<=', $cutoffDate)
            ->orderBy('period', 'asc')
            ->get();

        if ($unpaidDues->isEmpty()) return null;

        $details = [];
        $totalAmount = 0;
        foreach ($unpaidDues as $due) {
            $paidAmount = $due->payments->where('status', 'verified')->sum('amount_wajib');
            $remaining = max(0, $due->amount - $paidAmount);
            if ($remaining <= 0) continue;

            $monthName = Carbon::parse($due->period)->translatedFormat('F');
            $remainingStr = "Rp " . number_format($remaining, 0, ',', '.');

            if ($paidAmount > 0) {
                $details[] = "• *{$monthName}*: {$remainingStr} (sisa)";
            } else {
                $details[] = "• *{$monthName}*: {$remainingStr}";
            }
            $totalAmount += $remaining;
        }

        if (empty($details)) return null;

        $totalStr = "Rp " . number_format($totalAmount, 0, ',', '.');
        $detailText = implode("\n", $details);
        $bulanCount = count($unpaidDues);

        $message = "Assalamu'alaikum Bapak/Ibu {$owner->name},\n\n"
            . "Sistem *Otomatis* RT-44 menginformasikan tagihan iuran tahun {$year} untuk rumah {$house->blok}/{$house->nomor} yang belum lunas.\n\n"
            . "📌 *Rincian Tagihan ({$bulanCount} bulan):*\n"
            . "{$detailText}\n\n"
            . "💰 *Total: {$totalStr}*\n\n"
            . "Mohon untuk segera melakukan pembayaran. Abaikan pesan ini jika Bapak/Ibu sudah/sedang melakukan pembayaran hari ini.\n\n"
            . "Terima kasih atas partisipasi dan kerja samanya. 🙏\n\n"
            . "Salam,\n"
            . "*Ketua RT 44*";

        return [
            'message' => $message,
            'unpaid_count' => $bulanCount,
            'total_amount' => $totalAmount,
        ];
    }
}
