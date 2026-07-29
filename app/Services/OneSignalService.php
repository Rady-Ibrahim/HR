<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OneSignalService
{
    protected string $appId;
    protected string $restApiKey;

    public function __construct()
    {
        $this->appId = config('services.onesignal.app_id', '');
        $this->restApiKey = config('services.onesignal.rest_api_key', '');
    }

    public function isEnabled(): bool
    {
        return !empty($this->appId) && !empty($this->restApiKey);
    }

    public function sendNotification(array $userIds, string $title, string $message, array $data = []): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        try {
            Http::withHeaders([
                'Authorization' => 'Basic ' . $this->restApiKey,
                'Content-Type' => 'application/json',
            ])->post('https://onesignal.com/api/v1/notifications', [
                'app_id' => $this->appId,
                'include_external_user_ids' => array_map('strval', $userIds),
                'headings' => ['en' => $title, 'ar' => $title],
                'contents' => ['en' => $message, 'ar' => $message],
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('OneSignal notification failed', [
                'user_ids' => $userIds,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
