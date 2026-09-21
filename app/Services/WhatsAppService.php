<?php

namespace App\Services;

use App\Models\WaNotificationLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppService
{
    protected string $url;
    protected string $token;

    public function __construct()
    {
        $this->url = config('services.waghub.url', 'https://waghub.mekayastudio.com/api/v1/messages');
        $this->token = config('services.waghub.token', '');
    }

    /**
     * Send a WhatsApp message via WagHub API.
     *
     * @param string $phone
     * @param string $message
     * @param string $notifType (RENT, INSURANCE, REQUEST_SETTING, TEST)
     * @param int|null $referenceId
     * @param string $purpose
     * @return array
     */
    public function sendMessage(
        string $phone,
        string $message,
        string $notifType = 'SYSTEM',
        ?int $referenceId = null,
        string $purpose = 'notification'
    ): array {
        // Clean phone number (strip whitespace, dashes, plus sign)
        $cleanPhone = preg_replace('/[^0-9]/', '', trim($phone));

        if (empty($cleanPhone)) {
            return [
                'success' => false,
                'message' => 'Nomor telepon tujuan tidak valid.',
            ];
        }

        if (empty($this->token)) {
            return [
                'success' => false,
                'message' => 'WAG_TOKEN belum dikonfigurasi di file .env.',
            ];
        }

        // Unique idempotency key based on type, ref, date, and phone
        $idempotencyKey = sprintf(
            'sumo-%s-%s-%s-%s',
            strtolower($notifType),
            $referenceId ?? 'gen',
            now()->format('Ymd'),
            substr($cleanPhone, -4)
        );

        // For test messages, append timestamp to allow repeated tests
        if ($notifType === 'TEST') {
            $idempotencyKey .= '-' . time();
        }

        $clientRef = sprintf('sumo-%s-%s', strtolower($notifType), $referenceId ?? time());

        $payload = [
            'recipient' => [
                'type' => 'phone',
                'value' => $cleanPhone,
            ],
            'message' => [
                'type' => 'text',
                'text' => $message,
            ],
            'purpose' => $purpose,
            'mode' => 'sync',
            'route_key' => 'default',
            'client_reference' => $clientRef,
        ];

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->token,
                'Idempotency-Key' => $idempotencyKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(15)
            ->post($this->url, $payload);

            $responseData = $response->json();
            $isSuccess = $response->successful() && !isset($responseData['error']);

            // Save to logs
            WaNotificationLog::create([
                'notif_type' => $notifType,
                'reference_id' => $referenceId,
                'phone' => $cleanPhone,
                'sent_date' => now()->format('Y-m-d'),
                'status' => $isSuccess ? 'SUCCESS' : 'FAILED',
                'message_preview' => mb_substr($message, 0, 250),
                'response_payload' => json_encode($responseData ?? ['status' => $response->status()]),
            ]);

            return [
                'success' => $isSuccess,
                'status_code' => $response->status(),
                'message' => $isSuccess ? 'Pesan berhasil dikirim.' : ($responseData['message'] ?? 'Gagal mengirim pesan via WagHub.'),
                'data' => $responseData,
            ];
        } catch (Throwable $e) {
            Log::error('WhatsAppService Error: ' . $e->getMessage(), [
                'phone' => $cleanPhone,
                'payload' => $payload,
            ]);

            WaNotificationLog::create([
                'notif_type' => $notifType,
                'reference_id' => $referenceId,
                'phone' => $cleanPhone,
                'sent_date' => now()->format('Y-m-d'),
                'status' => 'FAILED',
                'message_preview' => mb_substr($message, 0, 250),
                'response_payload' => json_encode(['error' => $e->getMessage()]),
            ]);

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan koneksi ke server WagHub: ' . $e->getMessage(),
            ];
        }
    }
}
