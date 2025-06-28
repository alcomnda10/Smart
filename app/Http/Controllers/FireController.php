<?php

namespace App\Http\Controllers;

use App\Models\Flame;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FireController extends Controller
{
    private $messaging;

    public function __construct()
    {
        try {
            // قراءة بيانات الاعتماد من متغير البيئة بعد فك تشفير base64
            $firebaseJson = base64_decode(env('FIREBASE_CREDENTIALS_B64'));

            if (!$firebaseJson) {
                throw new \Exception("تعذر فك تشفير متغير FIREBASE_CREDENTIALS_B64");
            }

            $serviceAccount = json_decode($firebaseJson, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("JSON غير صالح في بيانات اعتماد Firebase");
            }

            $this->messaging = (new Factory)
                ->withServiceAccount($serviceAccount)
                ->createMessaging();
        } catch (\Throwable $e) {
            Log::error("فشل تهيئة Firebase في FireController: " . $e->getMessage());
            $this->messaging = null;
        }
    }

    public function index()
    {
        return response()->json(Flame::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'sensor_id' => 'required|string|max:255',
        ]);

        $icon = asset('icon/mdi_fire.png');
        $guidance = "🚒 Evacuate the area immediately, use the nearest fire extinguisher, and call civil defense at 180.";

        $flame = Flame::create([
            'sensor_id' => $request->sensor_id,
            'type' => 'fire',
            'status' => 'detected',
            'guidance' => $guidance,
            'icon' => $icon,
        ]);

        // إرسال إشعار إذا كانت Firebase مفعّلة
        if ($this->messaging) {
            $this->sendNotification("🚨 Fire Alert", "تحذير حريق");
        }

        return response()->json(['message' => 'تم تسجيل تنبيه الحريق بنجاح!']);
    }

    public function destroy($id)
    {
        $flame = Flame::find($id);
        if (!$flame) {
            return response()->json(['message' => 'التنبيه غير موجود'], 404);
        }

        $flame->delete();
        return response()->json(['message' => 'تم حذف التنبيه بنجاح']);
    }

    private function sendNotification(string $title, string $body)
    {
        try {
            $message = CloudMessage::withTarget('topic', 'flame')
                ->withNotification(Notification::create($title, $body));

            $this->messaging->send($message);
            Log::info("تم إرسال إشعار Firebase بنجاح");
        } catch (\Throwable $e) {
            Log::error("فشل إرسال إشعار Firebase: " . $e->getMessage());
        }
    }
}
