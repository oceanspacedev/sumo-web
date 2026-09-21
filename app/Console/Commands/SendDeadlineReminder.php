<?php

namespace App\Console\Commands;

use App\Models\Insurance;
use App\Models\Rent;
use App\Models\RequestSetting;
use App\Models\WaNotificationLog;
use App\Models\WaNotificationSetting;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendDeadlineReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminder:whatsapp {--dry-run : Simulate without sending actual messages}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Periksa jatuh tempo sewa, asuransi, dan cutoff pengajuan, lalu kirim notifikasi via WhatsApp WagHub.';

    /**
     * Execute the console command.
     */
    public function handle(WhatsAppService $waService): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        $this->info('Memulai pemeriksaan jatuh tempo pengingat WhatsApp...');

        $setting = WaNotificationSetting::getSettings();

        if (!$setting->is_enabled) {
            $this->warn('Notifikasi WhatsApp berstatus NONAKTIF di sistem. Proses dibatalkan.');
            return Command::SUCCESS;
        }

        $phones = $setting->phone_list;

        if (empty($phones)) {
            $this->warn('Belum ada nomor WhatsApp global. Data sewa atau asuransi tanpa nomor sendiri akan dilewati.');
        }

        $totalSent = 0;

        // 1. REMINDER SEWA (RENTS)
        if ($setting->remind_rent) {
            $totalSent += $this->processRentReminders($setting, $phones, $waService, $isDryRun);
        }

        // 2. REMINDER ASURANSI (INSURANCES)
        if ($setting->remind_insurance) {
            $totalSent += $this->processInsuranceReminders($setting, $phones, $waService, $isDryRun);
        }

        // 3. REMINDER CUTOFF PENGAJUAN BARANG (REQUEST SETTINGS)
        if ($setting->remind_request_cutoff) {
            $totalSent += $this->processRequestCutoffReminders($setting, $phones, $waService, $isDryRun);
        }

        $this->info("Pemeriksaan selesai. Total pengingat dikirim: {$totalSent}");

        return Command::SUCCESS;
    }

    /**
     * Process Rent Reminders.
     */
    protected function processRentReminders(WaNotificationSetting $setting, array $phones, WhatsAppService $waService, bool $isDryRun): int
    {
        $sentCount = 0;
        $milestones = array_map('intval', array_filter(explode(',', $setting->rent_days_before)));

        $rents = Rent::with(['rent_update' => function ($q) {
            $q->whereNotIn('status', ['TUTUP', 'REFUND'])->latest('expired_date');
        }])
        ->whereNotIn('status', ['TUTUP', 'REFUND'])
        ->get();

        foreach ($rents as $rent) {
            $expiredDate = $rent->rent_update->isNotEmpty()
                ? $rent->rent_update->first()->expired_date
                : $rent->expired_date;

            if (!$expiredDate) {
                continue;
            }

            $diffInDays = (int) Carbon::today()->diffInDays(Carbon::parse($expiredDate)->startOfDay(), false);

            // Check if matches configured milestones (e.g. 30, 14, 7, 1) or is expiring today (0)
            $shouldRemind = in_array($diffInDays, $milestones, true) || $diffInDays === 0;

            if (!$shouldRemind) {
                continue;
            }

            $sisaTeks = $diffInDays === 0 ? '⚠️ *JATUH TEMPO HARI INI*' : "⚠️ *{$diffInDays} Hari Lagi*";
            $formattedExp = Carbon::parse($expiredDate)->locale('id')->isoFormat('DD MMMM YYYY');
            $rentCode = $rent->rent_update->isNotEmpty() ? $rent->rent_update->first()->rent_code : $rent->rent_code;

            $message = "🔔 *PENGINGAT JATUH TEMPO SEWA (SUMO)*\n\n"
                . "Halo Admin, kontrak sewa berikut mendekati masa berakhir:\n\n"
                . "• *Kode Sewa:* {$rentCode}\n"
                . "• *Objek Sewa:* {$rent->rented_detail}\n"
                . "• *Pihak Kedua:* {$rent->second_party}\n"
                . "• *Tgl Berakhir:* {$formattedExp}\n"
                . "• *Sisa Waktu:* {$sisaTeks}\n\n"
                . "Mohon segera koordinasikan perpanjangan kontrak atau konfirmasi status sewa.\n"
                . "Buka sistem: " . url('/rent');

            $targetPhones = $this->resolvePhones($rent->reminder_phones, $phones);

            if (empty($targetPhones)) {
                $this->line("Sewa [{$rentCode}] tidak punya nomor tujuan. Lewati.");
                continue;
            }

            foreach ($targetPhones as $phone) {
                if (WaNotificationLog::hasBeenSentToday('RENT', $rent->id, $phone)) {
                    $this->line("Sewa [{$rentCode}] sudah dikirimkan ke {$phone} hari ini. Lewati.");
                    continue;
                }

                if ($isDryRun) {
                    $this->info("[DRY-RUN] Akan mengirim notifikasi Sewa [{$rentCode}] ke {$phone} (Sisa {$diffInDays} hari)");
                    $sentCount++;
                } else {
                    $res = $waService->sendMessage($phone, $message, 'RENT', $rent->id);
                    if ($res['success']) {
                        $this->info("Notifikasi Sewa [{$rentCode}] terkirim ke {$phone}");
                        $sentCount++;
                    } else {
                        $this->error("Gagal kirim Sewa [{$rentCode}] ke {$phone}: " . ($res['message'] ?? 'Error'));
                    }
                }
            }
        }

        return $sentCount;
    }

    /**
     * Process Insurance Reminders.
     */
    protected function processInsuranceReminders(WaNotificationSetting $setting, array $phones, WhatsAppService $waService, bool $isDryRun): int
    {
        $sentCount = 0;
        $milestones = array_map('intval', array_filter(explode(',', $setting->insurance_days_before)));

        $insurances = Insurance::with(['insurance_update' => function ($q) {
            $q->whereNotIn('status', ['TUTUP', 'REFUND'])->latest('expired_date');
        }])
        ->whereNotIn('status', ['TUTUP', 'REFUND'])
        ->get();

        foreach ($insurances as $insurance) {
            $expiredDate = $insurance->insurance_update->isNotEmpty()
                ? $insurance->insurance_update->first()->expired_date
                : $insurance->expired_date;

            if (!$expiredDate) {
                continue;
            }

            $diffInDays = (int) Carbon::today()->diffInDays(Carbon::parse($expiredDate)->startOfDay(), false);

            $shouldRemind = in_array($diffInDays, $milestones, true) || $diffInDays === 0;

            if (!$shouldRemind) {
                continue;
            }

            $sisaTeks = $diffInDays === 0 ? '⚠️ *JATUH TEMPO HARI INI*' : "⚠️ *{$diffInDays} Hari Lagi*";
            $formattedExp = Carbon::parse($expiredDate)->locale('id')->isoFormat('DD MMMM YYYY');
            $policyNumber = $insurance->insurance_update->isNotEmpty()
                ? $insurance->insurance_update->first()->policy_number
                : $insurance->policy_number;

            $message = "🔔 *PENGINGAT JATUH TEMPO ASURANSI (SUMO)*\n\n"
                . "Halo Admin, polis asuransi berikut mendekati masa kedaluwarsa:\n\n"
                . "• *No. Polis:* {$policyNumber}\n"
                . "• *Tertanggung:* {$insurance->insured_name}\n"
                . "• *Objek/Gudang:* {$insurance->insured_detail}\n"
                . "• *Tgl Berakhir:* {$formattedExp}\n"
                . "• *Sisa Waktu:* {$sisaTeks}\n\n"
                . "Mohon segera proses pembaharuan polis atau konfirmasi perpanjangan asuransi.\n"
                . "Buka sistem: " . url('/insurance');

            $targetPhones = $this->resolvePhones($insurance->reminder_phones, $phones);

            if (empty($targetPhones)) {
                $this->line("Asuransi [{$policyNumber}] tidak punya nomor tujuan. Lewati.");
                continue;
            }

            foreach ($targetPhones as $phone) {
                if (WaNotificationLog::hasBeenSentToday('INSURANCE', $insurance->id, $phone)) {
                    $this->line("Asuransi [{$policyNumber}] sudah dikirimkan ke {$phone} hari ini. Lewati.");
                    continue;
                }

                if ($isDryRun) {
                    $this->info("[DRY-RUN] Akan mengirim notifikasi Asuransi [{$policyNumber}] ke {$phone} (Sisa {$diffInDays} hari)");
                    $sentCount++;
                } else {
                    $res = $waService->sendMessage($phone, $message, 'INSURANCE', $insurance->id);
                    if ($res['success']) {
                        $this->info("Notifikasi Asuransi [{$policyNumber}] terkirim ke {$phone}");
                        $sentCount++;
                    } else {
                        $this->error("Gagal kirim Asuransi [{$policyNumber}] ke {$phone}: " . ($res['message'] ?? 'Error'));
                    }
                }
            }
        }

        return $sentCount;
    }

    /**
     * Process Request Settings Cutoff Reminders.
     */
    protected function processRequestCutoffReminders(WaNotificationSetting $setting, array $phones, WhatsAppService $waService, bool $isDryRun): int
    {
        $sentCount = 0;
        $milestones = array_map('intval', array_filter(explode(',', $setting->request_cutoff_days_before)));

        $requestSettings = RequestSetting::all();

        foreach ($requestSettings as $rs) {
            if (!$rs->closed_date) {
                continue;
            }

            $diffInDays = (int) Carbon::today()->diffInDays(Carbon::parse($rs->closed_date)->startOfDay(), false);

            $shouldRemind = in_array($diffInDays, $milestones, true) || $diffInDays === 0;

            if (!$shouldRemind) {
                continue;
            }

            $sisaTeks = $diffInDays === 0 ? '⚠️ *PENUTUPAN HARI INI*' : "⚠️ *{$diffInDays} Hari Lagi*";
            $formattedClosed = Carbon::parse($rs->closed_date)->locale('id')->isoFormat('DD MMMM YYYY');
            $bulan = Carbon::parse($rs->request_month)->locale('id')->isoFormat('MMMM YYYY');

            $message = "⚠️ *PENGINGAT BATAS AKHIR PENGAJUAN BARANG (SUMO)*\n\n"
                . "Halo Admin & Tim,\n"
                . "Periode pengajuan barang untuk *{$rs->request_detail}* (Bulan {$bulan}) akan segera ditutup:\n\n"
                . "• *Tanggal Penutupan:* {$formattedClosed}\n"
                . "• *Sisa Waktu:* {$sisaTeks}\n\n"
                . "Mohon pastikan seluruh divisi telah menyelesaikan pengajuan sebelum batas waktu tersebut.\n"
                . "Buka sistem: " . url('/request');

            foreach ($phones as $phone) {
                if (WaNotificationLog::hasBeenSentToday('REQUEST_SETTING', $rs->id, $phone)) {
                    $this->line("Pengingat Cutoff [{$rs->id}] sudah dikirimkan ke {$phone} hari ini. Lewati.");
                    continue;
                }

                if ($isDryRun) {
                    $this->info("[DRY-RUN] Akan mengirim notifikasi Cutoff Pengajuan [{$rs->request_detail}] ke {$phone}");
                    $sentCount++;
                } else {
                    $res = $waService->sendMessage($phone, $message, 'REQUEST_SETTING', $rs->id);
                    if ($res['success']) {
                        $this->info("Notifikasi Cutoff Pengajuan terkirim ke {$phone}");
                        $sentCount++;
                    } else {
                        $this->error("Gagal kirim Cutoff Pengajuan ke {$phone}: " . ($res['message'] ?? 'Error'));
                    }
                }
            }
        }

        return $sentCount;
    }

    /**
     * Use the record's own numbers when set, otherwise the global list.
     */
    protected function resolvePhones(?string $recordPhones, array $fallback): array
    {
        $own = WaNotificationSetting::parsePhoneList($recordPhones);

        return $own !== [] ? $own : $fallback;
    }
}
