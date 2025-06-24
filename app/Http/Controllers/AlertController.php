<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Alert;
use Illuminate\Support\Facades\Log; 
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class AlertController extends Controller
{
    private $messaging;
    public function __construct()
    {
        $this->messaging = (new Factory)
            ->withServiceAccount(storage_path('app/firebase_credentials.json'))->createMessaging();
    }
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

        $icon = asset('icon/famicons_water.png');

        $guidance = '💧 Shut off the water source immediately, disconnect the power supply, and contact a specialist.';


        $alert = Alert::create([
            'sensor_id' => $request->sensor_id,
            'type' => 'water',
            'status' => 'detected',
            'guidance' => $guidance,
            'icon' => $icon,
        ]);


        $this->sendNotification("🚨 تسرب ماء!", "الحق هتغرق");
        return response()->json(['message' => 'تم تسجيل تنبيه التسرب بنجاح!']);
    }

    // حذف تنبيه معين
    public function destroy($id)
    {
        $alert = Alert::findOrFail($id);
        $alert->delete();

        return response()->json(['message' => 'تم حذف التنبيه بنجاح']);
    }

    // إرسال إشعار عبر Firebase
    private function sendNotification(string $title, string $body)
    {
        try {
            $message = CloudMessage::withTarget('topic', 'water')
                ->withNotification(Notification::create($title, $body));

            $this->messaging->send($message);
            Log::info("تم إرسال إشعار Firebase بنجاح");
        } catch (\Throwable $e) {
            Log::error("فشل إرسال إشعار Firebase: " . $e->getMessage());
        }
    }
}
