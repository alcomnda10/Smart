<?php

namespace App\Http\Controllers;

use App\Models\Gas;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Http\Request;

class GasController extends Controller
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
            Log::error("فشل تهيئة Firebase: " . $e->getMessage());
            $this->messaging = null;
        }
    }

    public function index()
    {
        $gasses = Gas::all();
        return response()->json($gasses);
    }

    public function store(Request $request)
    {
        $request->validate([
            'sensor_id' => 'required|string|max:255',
        ]);

        $guidance = "⚠️ Evacuate the area immediately, avoid any open flames, and contact the emergency services.";
        $icon = asset('icon/game-icons_gas-stove.png');

        // تسجيل التنبيه
        Gas::create([
            'sensor_id' => $request->sensor_id,
            'type' => 'gas',
            'status' => 'detected',
            'guidance' => $guidance,
            'icon' => $icon,
        ]);

        // إرسال إشعار إذا كانت Firebase مفعلة
        if ($this->messaging) {
            $this->sendNotification("🚨 انذار غاز", "  تسرب غاز");
        }

        return response()->json(['message' => 'تم تسجيل تنبيه الغاز بنجاح!']);
    }

    public function destroy($id)
    {
        $gas = Gas::find($id);

        if (!$gas) {
            return response()->json(['message' => 'التنبيه غير موجود'], 404);
        }

        $gas->delete();

        return response()->json(['message' => 'تم حذف التنبيه بنجاح']);
    }

    private function sendNotification(string $title, string $body)
    {
        try {
            $message = CloudMessage::withTarget('topic', 'gas')
                ->withNotification(Notification::create($title, $body));

            $this->messaging->send($message);
            Log::info("تم إرسال إشعار Firebase بنجاح");
        } catch (\Throwable $e) {
            Log::error("فشل إرسال إشعار Firebase: " . $e->getMessage());
        }
    }
}
