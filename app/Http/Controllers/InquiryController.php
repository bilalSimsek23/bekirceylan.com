<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use App\Models\Setting;
use App\Models\Tour;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    public function store(Request $request)
    {
        // Basit bot tuzağı: gizli alan dolu ise sessizce geç
        if (filled($request->input('website'))) {
            return $request->expectsJson() ? response()->json(['ok' => true]) : back();
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'min:10', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'tour_id' => ['nullable', 'integer', 'exists:tours,id'],
            'interest' => ['nullable', 'string', 'max:120'],
            'message' => ['nullable', 'string', 'max:2000'],
            'kvkk' => ['accepted'],
        ], [
            'kvkk.accepted' => 'Devam etmek için KVKK aydınlatma metnini onaylamalısınız.',
        ]);

        unset($data['kvkk']);
        $inquiry = Inquiry::query()->create($data + ['ip' => $request->ip(), 'source' => 'website']);

        $tour = $inquiry->tour_id ? Tour::find($inquiry->tour_id) : null;
        $text = "Merhaba, Bekir Ceylan Turizm'in tur programları hakkında bilgi almak istiyorum.\n\n"
            ."Ad Soyad: {$inquiry->name}\nTelefon: {$inquiry->phone}\n"
            .'İlgilendiğim Tur: '.($tour?->title ?? $inquiry->interest ?? '-')."\n"
            .($inquiry->message ? "Mesaj: {$inquiry->message}" : '');

        $whatsappUrl = 'https://wa.me/'.Setting::get('whatsapp').'?text='.rawurlencode(trim($text));

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'whatsapp' => $whatsappUrl]);
        }

        return back()->with('inquiry_sent', true)->with('whatsapp_url', $whatsappUrl);
    }
}
