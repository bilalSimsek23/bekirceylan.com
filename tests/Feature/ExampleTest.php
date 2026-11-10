<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\Tour;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Http::fake(['*' => Http::response('', 500)]);
        $this->seed(DatabaseSeeder::class);
    }

    public function test_public_pages_render(): void
    {
        $tour = Tour::query()->first();

        $this->get('/')->assertOk()->assertSee('Öne Çıkan Tur Programları')->assertSee($tour->title);
        $this->get('/turlar')->assertOk();
        $this->get('/turlar?kategori=umre')->assertOk()->assertSee('Umre Turları');
        $this->get('/turlar/'.$tour->slug)->assertOk()->assertSee($tour->title);
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml');
    }

    public function test_draft_tour_is_hidden(): void
    {
        $tour = Tour::query()->first();
        $tour->update(['status' => 'draft']);

        $this->get('/turlar/'.$tour->slug)->assertNotFound();
    }

    public function test_inquiry_form_saves_and_returns_whatsapp_link(): void
    {
        $tour = Tour::query()->first();

        $this->postJson('/bilgi-talebi', [
            'name' => 'Test Kişi', 'phone' => '05321112233', 'tour_id' => $tour->id,
            'message' => 'Bilgi istiyorum', 'kvkk' => '1',
        ])->assertOk()->assertJsonPath('ok', true)
            ->assertJson(fn ($json) => $json->where('whatsapp', fn ($url) => str_starts_with($url, 'https://wa.me/905321341341'))->etc());

        $this->assertDatabaseHas('inquiries', ['name' => 'Test Kişi', 'tour_id' => $tour->id, 'status' => 'new']);
    }

    public function test_inquiry_requires_kvkk_consent(): void
    {
        $this->postJson('/bilgi-talebi', ['name' => 'A', 'phone' => '05321112233'])
            ->assertStatus(422)->assertJsonValidationErrors('kvkk');

        $this->assertSame(0, Inquiry::query()->count());
    }

    public function test_admin_requires_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/cikti/tur/1/yolcu-listesi')->assertRedirect('/admin/login');
    }
}
