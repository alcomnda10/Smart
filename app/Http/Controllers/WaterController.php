<?php

namespace App\Http\Controllers;

use App\Models\Water;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class WaterController extends Controller
{
    private $messaging;

    public function __construct()
    {
        try {
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
            Log::error("فشل تهيئة Firebase في WaterController: " . $e->getMessage());
            $this->messaging = null;
        }
    }

    /** جلب جميع تنبيهات الماء */
    public function index()
    {
        return response()->json(Water::latest()->get());
    }

    /** تخزين تنبيه ماء جديد */
    public function store(Request $request)
    {
        $request->validate([
            'sensor_id' => 'required|string|max:255',
        ]);

        $water = Water::create([
            'sensor_id' => $request->sensor_id,
            'type'      => 'water',
            'status'    => 'detected',
            'guidance'  => '💧 Shut off the water source immediately, disconnect the power supply, and contact a specialist.',
            'icon'      => asset('icon/famicons_water.png'),
        ]);

        if ($this->messaging) {
            $this->sendNotification("🚨 تسرب ماء!", "انذار ماء");
        }

        return response()->json([
            'message' => 'تم تسجيل تنبيه الماء بنجاح!',
            'water'   => $water,
        ]);
    }

    /** حذف تنبيه ماء */
    public function destroy($id)
    {
        Water::findOrFail($id)->delete();
        return response()->json(['message' => 'تم حذف التنبيه بنجاح']);
    }

    /** إرسال إشعار Firebase */
    private function sendNotification(string $title, string $body): void
    {
        try {
            $msg = CloudMessage::withTarget('topic', 'water')
                ->withNotification(Notification::create($title, $body));

            $this->messaging->send($msg);
            Log::info("تم إرسال إشعار Firebase بنجاح");
        } catch (\Throwable $e) {
            Log::error("فشل إرسال إشعار Firebase: " . $e->getMessage());
        }
    }
}
