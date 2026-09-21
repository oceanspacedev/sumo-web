<?php

namespace App\Http\Controllers;

use App\Models\WaNotificationSetting;
use App\Models\WaNotificationLog;
use App\Services\ReminderPhoneReplacer;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Throwable;

class NotificationSettingController extends Controller
{
    /**
     * Display notification settings page.
     */
    public function index(ReminderPhoneReplacer $replacer)
    {
        $setting = WaNotificationSetting::getSettings();
        $logs = WaNotificationLog::orderBy('id', 'desc')->paginate(15);

        return view('settings.notification.index', [
            'setting' => $setting,
            'logs' => $logs,
            'phoneGroups' => $replacer->groups(),
        ]);
    }

    /**
     * Update notification settings.
     */
    public function update(Request $request)
    {
        try {
            $setting = WaNotificationSetting::getSettings();

            $setting->update([
                'is_enabled' => $request->has('is_enabled'),
                'target_phones' => $request->input('target_phones', ''),
                'send_time' => $request->input('send_time', '08:00'),
                'remind_rent' => $request->has('remind_rent'),
                'rent_days_before' => $request->input('rent_days_before', '30,14,7,1'),
                'remind_insurance' => $request->has('remind_insurance'),
                'insurance_days_before' => $request->input('insurance_days_before', '30,14,7,1'),
                'remind_request_cutoff' => $request->has('remind_request_cutoff'),
                'request_cutoff_days_before' => $request->input('request_cutoff_days_before', '3,1'),
            ]);

            return redirect('/notification-settings')->with('success', 'Pengaturan notifikasi WhatsApp berhasil diperbarui!');
        } catch (Throwable $e) {
            return redirect('/notification-settings')->with('error', 'Gagal memperbarui pengaturan: ' . $e->getMessage());
        }
    }

    /**
     * Send test message to a specific phone number.
     */
    public function testSend(Request $request, WhatsAppService $waService)
    {
        $request->validate([
            'test_phone' => 'required|string',
        ]);

        $phone = $request->input('test_phone');
        $message = "🔔 *UJI COBA NOTIFIKASI WHATSAPP - SUMO*\n\nHalo Admin,\nIni adalah pesan uji coba dari Sistem Pengingat Jatuh Tempo (SUMO).\n\n• *Status:* Terkoneksi ke WagHub API\n• *Waktu:* " . now()->locale('id')->isoFormat('dddd, D MMMM YYYY HH:mm') . " WIB\n\nJika pesan ini diterima, berarti integrasi WhatsApp telah berjalan normal! ✅";

        $result = $waService->sendMessage($phone, $message, 'TEST', null, 'notification');

        if ($result['success']) {
            return redirect('/notification-settings')->with('success', 'Pesan uji coba berhasil dikirim ke ' . $phone . '!');
        }

        return redirect('/notification-settings')->with('error', 'Gagal mengirim pesan: ' . ($result['message'] ?? 'Periksa koneksi WagHub atau nomor HP.'));
    }

    /**
     * Replace or remove one reminder number that the user picked from the existing list.
     */
    public function replacePhones(Request $request, ReminderPhoneReplacer $replacer)
    {
        $oldPhone = $this->singlePhone($request->input('old_phone'));
        $action = $request->input('action');

        if ($oldPhone === null) {
            return redirect('/notification-settings')->with('error', 'Pilih satu nomor dari daftar data yang sudah punya nomor pengingat.');
        }

        if ($action === 'delete') {
            $result = $replacer->remove($oldPhone);

            return redirect('/notification-settings')->with(
                'success',
                'Nomor '.$oldPhone.' dihapus dari '.$result['rent'].' perjanjian sewa dan '.$result['insurance'].' polis asuransi.'
            );
        }

        if ($action !== 'replace') {
            return redirect('/notification-settings')->with('error', 'Pilih aksi ganti atau hapus.');
        }

        $newPhone = $this->singlePhone($request->input('new_phone'));

        if ($newPhone === null) {
            return redirect('/notification-settings')->with('error', 'Isi satu nomor baru. Contoh: 081234567890.');
        }

        if ($oldPhone === $newPhone) {
            return redirect('/notification-settings')->with('error', 'Nomor lama dan nomor baru tidak boleh sama.');
        }

        $result = $replacer->replace($oldPhone, $newPhone);

        return redirect('/notification-settings')->with(
            'success',
            'Berhasil mengganti nomor '.$oldPhone.' menjadi '.$newPhone.' pada '.$result['rent'].' perjanjian sewa dan '.$result['insurance'].' polis asuransi.'
        );
    }

    protected function singlePhone(?string $raw): ?string
    {
        $phones = WaNotificationSetting::parsePhoneList($raw);

        return count($phones) === 1 ? $phones[0] : null;
    }
}
