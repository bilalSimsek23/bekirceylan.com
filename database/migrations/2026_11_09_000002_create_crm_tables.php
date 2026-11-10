<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            // Kimlik
            $table->string('first_name');
            $table->string('last_name');
            $table->string('tc_no', 11)->nullable()->unique();
            $table->string('gender', 10)->nullable();        // male | female
            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('nationality')->default('TC');
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('marital_status', 20)->nullable();
            $table->string('occupation')->nullable();
            $table->string('blood_type', 10)->nullable();
            // İletişim
            $table->string('phone', 30)->nullable();
            $table->string('phone_secondary', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->text('address')->nullable();
            // Pasaport
            $table->string('passport_type', 20)->nullable(); // umuma | hususi | hizmet
            $table->string('passport_no')->nullable();
            $table->date('passport_issue_date')->nullable();
            $table->date('passport_expiry_date')->nullable();
            $table->string('passport_issue_place')->nullable();
            // Acil durum & sağlık
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_relation')->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();
            $table->text('health_notes')->nullable();       // kronik hastalık, ilaç, engellilik
            $table->boolean('needs_wheelchair')->default(false);
            // Tercihler
            $table->string('room_preference', 20)->nullable();
            $table->string('meal_preference')->nullable();
            $table->string('source', 30)->nullable();       // nereden ulaştı
            $table->string('photo')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('kvkk_consent')->default(false);
            $table->boolean('sms_consent')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['last_name', 'first_name']);
            $table->index('phone');
        });

        Schema::create('customer_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);   // passport | id_card | photo | vaccine | visa | contract | other
            $table->string('title')->nullable();
            $table->string('file');
            $table->date('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_no')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tour_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('pending'); // pending | confirmed | cancelled | completed
            $table->string('group_code')->nullable();          // aile / grup eşleştirme
            $table->string('room_type', 20)->nullable();       // single | double | triple | quad
            $table->string('roommate_notes')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('extra_charges', 12, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('visa_status', 20)->nullable();     // not_required | pending | applied | approved | rejected
            $table->string('seat_no')->nullable();
            $table->date('registered_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tour_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('customer_documents');
        Schema::dropIfExists('customers');
    }
};
