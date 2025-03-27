<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Log;

class ZoomService
{
    protected $accountId;
    protected $clientId;
    protected $clientSecret;

    public function __construct()
    {
        $this->accountId = env('ZOOM_ACCOUNT_ID');
        $this->clientId = env('ZOOM_CLIENT_ID');
        $this->clientSecret = env('ZOOM_CLIENT_SECRET');
    }

    public function getAccessToken(): string
    {
        try {
            $base64String = base64_encode("{$this->clientId}:{$this->clientSecret}");
            $response = Http::withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => "Basic {$base64String}",
            ])->post("https://zoom.us/oauth/token?grant_type=account_credentials&account_id={$this->accountId}");

            if ($response->successful()) {
                return $response->json()['access_token'];
            }

            throw new \Exception('Failed to obtain Zoom access token: ' . $response->body());
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function createMeeting(array $data): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'Content-Type' => 'application/json',
            ])->post('https://api.zoom.us/v2/users/me/meetings', [
                'topic' => $data['title'], // Changed 'title' to 'topic' for Zoom API
                'type' => 2,
                'start_time' => Carbon::parse($data['start_time'])->toIso8601String(),
                'duration' => $data['duration'],
                'timezone' => $data['timezone'],
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception('Failed to create Zoom meeting: ' . $response->body());
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function listMeetings(string $userId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'Content-Type' => 'application/json',
            ])->get("https://api.zoom.us/v2/users/{$userId}/meetings");

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception('Failed to list Zoom meetings: ' . $response->body());
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function generateSignature(string $meetingNumber, int $role): string
    {
        $sdkKey = env('ZOOM_SDK_KEY');
        $sdkSecret = env('ZOOM_SDK_SECRET');

        $iat = time() - 30; 
        $exp = $iat + 60 * 60 * 2;


        $payload = [
            'sdkKey' => $sdkKey,
            'mn' => $meetingNumber,
            'role' => $role,
            'iat' => $iat,
            'exp' => $exp,
            'tokenExp' => $exp,
        ];

        $signature = JWT::encode($payload, $sdkSecret, 'HS256');


        return rtrim(strtr(base64_encode($signature), '+/', '-'), '=');    }
}