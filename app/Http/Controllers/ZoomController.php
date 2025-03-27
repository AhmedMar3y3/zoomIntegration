<?php

namespace App\Http\Controllers;

use App\Http\Requests\storeMeeting;
use App\Services\ZoomService;
use Illuminate\Http\Request;
use App\Models\Meeting;

class ZoomController extends Controller
{
    protected ZoomService $zoomService;

    public function __construct(ZoomService $zoomService)
    {
        $this->zoomService = $zoomService;
    }

    public function createMeeting(storeMeeting $request)
    {
        try {
            $meetingData = $this->zoomService->createMeeting($request->validated());
            
            $meeting = Meeting::create([
                'zoom_meeting_id' => $meetingData['id'],
                'password' => $meetingData['password'],
            ]);
            
            return response()->json(['meeting_id' => $meeting->id]);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function getMeetingData(Request $request, $meetingId)
    {
        $meeting = Meeting::findOrFail($meetingId);
        $role = $request->input('role');

        if (!in_array($role, [0, 1])) {
            return response()->json(['error' => 'Invalid role. Use 0 for participant, 1 for host'], 400);
        }

        $signature = $this->zoomService->generateSignature($meeting->zoom_meeting_id, $role);

        return response()->json([
            'meetingNumber' => $meeting->zoom_meeting_id,
            'password' => $meeting->password,
            'signature' => $signature,
        ]);
    }

    // public function getMeetingDetails(Request $request, $meetingId)
    // {
    //     $meeting = Meeting::findOrFail($meetingId);
    //     return response()->json([
    //         'meetingNumber' => $meeting->zoom_meeting_id,
    //         'password' => $meeting->password,
    //     ]);
    // }

    // public function getSignature(Request $request, $meetingId)
    // {
    //     $meeting = Meeting::findOrFail($meetingId);
    //     $role = $request->input('role');
        
    //     if (!in_array($role, [0, 1])) {
    //         return response()->json(['error' => 'Invalid role'], 400);
    //     }
        
    //     $signature = $this->zoomService->generateSignature($meeting->zoom_meeting_id, $role);
        
    //     return response()->json(['signature' => $signature]);
    // }

    public function listMeetings(Request $request)
    {
        $user = $request->user();

        if (!$user->zoom_user_id) {
            return response()->json(['error' => 'Zoom user ID not found for this user'], 404);
        }

        try {
            $meetings = $this->zoomService->listMeetings($user->zoom_user_id);
            return response()->json($meetings);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
}