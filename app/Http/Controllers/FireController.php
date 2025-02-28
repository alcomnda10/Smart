<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Flame;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

use Illuminate\Http\Request;

class FireController extends Controller
{
    public function index()
    {
        $flams = Flame::all();
        return response()->json($flams);
    }

    // استقبال تنبيه الحساس
    public function store(Request $request)
    {
        $request->validate([
            'sensor_id' => 'required|string|max:255',
        ]);

        $guidance = "🚒 Evacuate the area immediately, use the nearest fire extinguisher, and call civil defense at 180.";

        // تسجيل التنبيه
        Flame::create([
            'sensor_id' => $request->sensor_id,
            'type' => 'water',
            'status' => 'detected',
            'guidance' => $guidance, 
        ]);

        // إرسال إشعار للتطبيق
        $this->sendNotification("🚨 انذار حريق", "المستشعر: {$request->sensor_id}");

        return response()->json(['message' => 'تم تسجيل تنبيه الحريق بنجاح!']);
    }
    public function destroy($id)
    {
        $flams = Flame::find($id);

        if (!$flams) {
            return response()->json(['message' => 'التنبيه غير موجود'], 404);
        }

        $flams->delete();

        return response()->json(['message' => 'تم حذف التنبيه بنجاح']);
    }

    // إرسال إشعار عبر Firebase
    private function sendNotification($title, $body)
    {
        $response = Http::withHeaders([
            'Authorization' => 'key=' . env('FCM_SERVER_KEY'),
            'Content-Type'  => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', [
            "to" => "/topics/flame",
            "notification" => [
                "title" => $title,
                "body"  => $body,
                "sound" => "default"
            ]
        ]);

        if ($response->failed()) {
            Log::error("FCM Error: " . $response->body());
        }
    }
}
