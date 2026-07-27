<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Facades\Http;

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

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $this->restApiKey,
            'Content-Type' => 'application/json',
        ])->post('https://onesignal.com/api/v1/notifications', [
            'app_id' => $this->appId,
            'include_external_user_ids' => array_map('strval', $userIds),
            'headings' => ['en' => $title, 'ar' => $title],
            'contents' => ['en' => $message, 'ar' => $message],
            'data' => $data,
        ]);
    }

    public function sendToEmployee(int $employeeId, string $title, string $message, array $data = []): void
    {
        $employee = Employee::find($employeeId);
        if (!$employee || !$employee->user || !$employee->user->onesignal_player_id) {
            return;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $this->restApiKey,
            'Content-Type' => 'application/json',
        ])->post('https://onesignal.com/api/v1/notifications', [
            'app_id' => $this->appId,
            'include_external_user_ids' => [(string) $employee->user->id],
            'headings' => ['en' => $title, 'ar' => $title],
            'contents' => ['en' => $message, 'ar' => $message],
            'data' => $data,
        ]);
    }
}
