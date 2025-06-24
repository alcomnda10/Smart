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
        $this->messaging = (new Factory)
            ->withServiceAccount(storage_path('app/firebase_credentials.json'))->createMessaging();
    }
    public function index()
    {
        $gasses = Gas::all();
        return response()->json($gasses);
    }

    // استقبال تنبيه الحساس
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

        // إرسال إشعار للتطبيق
        $this->sendNotification("🚨 انذار غاز", "امشي من المكان هتتخنق");

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

    // إرسال إشعار عبر Firebase
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
