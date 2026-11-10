<?php

namespace Database\Seeders;

use App\Models\CashAccount;
use App\Models\Slide;
use App\Models\Tour;
use App\Models\TourCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'admin@bekirceylan.com.tr'],
            ['name' => 'Bekir Ceylan', 'password' => env('ADMIN_PASSWORD', 'BekirCeylan2026!'), 'role' => 'admin']
        );

        $categories = collect([
            ['name' => 'Umre', 'slug' => 'umre', 'badge' => 'UMRE', 'sort_order' => 1],
            ['name' => 'Hac', 'slug' => 'hac', 'badge' => 'HAC', 'sort_order' => 2],
            ['name' => 'Kültür & Gezi', 'slug' => 'kultur', 'badge' => 'KÜLTÜR', 'sort_order' => 3],
        ])->mapWithKeys(fn ($c) => [$c['slug'] => TourCategory::query()->updateOrCreate(['slug' => $c['slug']], $c)]);

        // Görselleri public diske kopyala
        foreach (File::files(database_path('seeders/images')) as $file) {
            $dir = str_starts_with($file->getFilename(), 'kabe') ? 'slides' : 'tours';
            Storage::disk('public')->put($dir.'/'.$file->getFilename(), File::get($file->getPathname()));
        }

        // Mevcut siteden alınan turlar
        $tours = json_decode(File::get(database_path('seeders/data/tours.json')), true)['tours'] ?? [];
        foreach ($tours as $i => $t) {
            Tour::query()->updateOrCreate(['title' => $t['title']], [
                'tour_category_id' => $categories[$t['type']]->id ?? null,
                'location' => $t['detail'] ?: null,
                'summary' => $t['description'] ?: null,
                'start_date' => $t['start_date'] ?: null,
                'end_date' => $t['end_date'] ?: null,
                'price' => $t['price'] ?: null,
                'old_price' => $t['old_price'] ?: null,
                'currency' => 'USD',
                'capacity' => $t['capacity'] ?: null,
                'cover_image' => 'tours/'.basename($t['image']),
                'price_label' => 'Bilgi Alınız',
                'status' => 'published',
                'is_featured' => true,
                'sort_order' => $i + 1,
            ]);
        }

        Slide::query()->firstOrCreate(['title' => 'Manevi Umre Yolculuğunuz'], [
            'eyebrow' => 'Hac • Umre • Kültür Turları',
            'subtitle' => 'Tur programlarımız hakkında güncel bilgi için bizimle iletişime geçin.',
            'image' => 'slides/kabe-ana-sayfa.jpg',
            'primary_button_text' => 'Turlarımızı Keşfedin',
            'primary_button_url' => '#turlar',
            'secondary_button_text' => 'Bizimle İletişime Geçin',
            'secondary_button_url' => '#iletisim',
            'sort_order' => 1,
        ]);

        foreach ([
            ['name' => 'Merkez Kasa (TL)', 'type' => 'cash', 'currency' => 'TRY'],
            ['name' => 'Döviz Kasa (USD)', 'type' => 'cash', 'currency' => 'USD'],
            ['name' => 'Banka Hesabı (TL)', 'type' => 'bank', 'currency' => 'TRY'],
            ['name' => 'Kredi Kartı POS', 'type' => 'pos', 'currency' => 'TRY'],
        ] as $account) {
            CashAccount::query()->firstOrCreate(['name' => $account['name']], $account);
        }

        if (app()->environment('local')) {
            $this->call(DemoSeeder::class);
        }
    }
}
