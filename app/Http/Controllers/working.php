<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Http\Requests\storeMeeting;
use Illuminate\Support\Facades\Http;

class working extends Controller
{

    public function createMeeting(storeMeeting $request): array
    {
        $validated = $request->validated();

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' .self::generateToken(),
                'Content-Type' => 'application/json',
            ])->post("https://api.zoom.us/v2/users/me/meetings", [
                'topic' => $validated['title'],
                'type' => 2,
                'start_time' => Carbon::parse($validated['start_date_time'])->toIso8601String(),
                'duration' => $validated['duration_in_minute'],
            ]);
            
            return $response->json();

        } catch (\Throwable $th) {
            throw $th;
        }

    }

    protected function generateToken(): string
    {
        try {
            $base64String = base64_encode(env('ZOOM_CLIENT_ID') . ':' . env('ZOOM_CLIENT_SECRET'));
            $accountId = env('ZOOM_ACCOUNT_ID');

            $responseToken = Http::withHeaders([
                "Content-Type"=> "application/x-www-form-urlencoded",
                "Authorization"=> "Basic {$base64String}"
            ])->post("https://zoom.us/oauth/token?grant_type=account_credentials&account_id={$accountId}");

            return $responseToken->json()['access_token'];

        } catch (\Throwable $th) {
            throw $th;
        }
    }
} 