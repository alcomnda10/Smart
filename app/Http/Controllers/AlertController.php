<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Alert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class AlertController extends Controller
{
    // جلب جميع التنبيهات
    public function index()
    {
        $alerts = Alert::all();
        return response()->json($alerts);
    }

    // استقبال تنبيه الحساس
    public function store(Request $request)
    {
        $request->validate([
            'sensor_id' => 'required|string|max:255',
        ]);

        $guidance = '💧 Shut off the water source immediately, disconnect the power supply, and contact a specialist.';

        try {
            $alert = Alert::create([
                'sensor_id' => $request->sensor_id,
                'type' => 'water',
                'status' => 'detected',
                'guidance' => $guidance,
            ]);

            if ($alert) {
                $this->sendNotification("🚨 تسرب ماء!", "المستشعر: {$request->sensor_id}");
                return response()->json(['message' => 'تم تسجيل تنبيه التسرب بنجاح!']);
            }
        } catch (\Exception $e) {
            Log::error("خطأ أثناء تسجيل التنبيه: " . $e->getMessage());
            return response()->json(['message' => 'حدث خطأ أثناء تسجيل التنبيه'], 500);
        }
    }

    // حذف تنبيه معين
    public function destroy($id)
    {
        $alert = Alert::findOrFail($id);
        $alert->delete();

        return response()->json(['message' => 'تم حذف التنبيه بنجاح']);
    }

    // إرسال إشعار عبر Firebase
    private function sendNotification($title, $body)
    {
        try {
            $url = config('services.fcm.url'); // استدعاء عنوان FCM من ملف الإعدادات

            $response = Http::withHeaders([
                'Authorization' => 'key=' . config('services.fcm.server_key'),
                'Content-Type'  => 'application/json',
            ])->post($url, [
                "to" => "/topics/water_alerts",
                "notification" => [
                    "title" => $title,
                    "body"  => $body,
                    "sound" => "default"
                ]
            ]);

            if ($response->successful()) {
                Log::info("تم إرسال الإشعار بنجاح: " . $title);
            } else {
                Log::error("FCM Error: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("خطأ أثناء إرسال الإشعار: " . $e->getMessage());
        }
    }
}
