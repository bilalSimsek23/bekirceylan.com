<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('admin')->after('email'); // admin | staff
            $table->string('phone', 30)->nullable()->after('role');
            $table->boolean('is_active')->default(true)->after('phone');
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->timestamps();
        });

        Schema::create('tour_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('badge')->nullable(); // kartta görünen kısa etiket (UMRE, KULTUR)
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('location')->nullable();          // Mekke & Medine
            $table->string('summary', 500)->nullable();      // kart açıklaması
            $table->longText('description')->nullable();     // detay (rich text)
            $table->json('itinerary')->nullable();           // gün gün program
            $table->json('included')->nullable();            // fiyata dahil
            $table->json('excluded')->nullable();            // fiyata dahil değil
            $table->json('hotels')->nullable();              // konaklama
            $table->json('room_prices')->nullable();         // 2/3/4 kişilik oda fiyatları
            $table->string('cover_image')->nullable();
            $table->json('gallery')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('duration_text')->nullable();     // 4 Gece 5 Gün
            $table->string('departure_city')->nullable();
            $table->string('transport')->nullable();         // Uçak / Otobüs
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('old_price', 12, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->unsignedInteger('capacity')->nullable();
            $table->string('price_label')->nullable();       // fiyat yoksa: "Bilgi Alınız"
            $table->string('status', 20)->default('published'); // draft | published | completed | cancelled
            $table->boolean('is_featured')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('slides', function (Blueprint $table) {
            $table->id();
            $table->string('eyebrow')->nullable();
            $table->string('title');
            $table->string('subtitle', 500)->nullable();
            $table->string('image')->nullable();
            $table->string('primary_button_text')->nullable();
            $table->string('primary_button_url')->nullable();
            $table->string('secondary_button_text')->nullable();
            $table->string('secondary_button_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->foreignId('tour_id')->nullable()->constrained()->nullOnDelete();
            $table->string('interest')->nullable();
            $table->text('message')->nullable();
            $table->string('status', 20)->default('new'); // new | contacted | converted | closed
            $table->foreignId('customer_id')->nullable(); // dönüştürülen müşteri
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('source', 30)->default('website');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiries');
        Schema::dropIfExists('slides');
        Schema::dropIfExists('tours');
        Schema::dropIfExists('tour_categories');
        Schema::dropIfExists('settings');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['role', 'phone', 'is_active']));
    }
};
