<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kasa ve banka hesapları
        Schema::create('cash_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 20)->default('cash'); // cash | bank | pos | other
            $table->string('currency', 3)->default('TRY');
            $table->string('bank_name')->nullable();
            $table->string('iban', 40)->nullable();
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Tedarikçiler (otel, havayolu, rehber, transfer, vize acentesi ...)
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 30)->nullable();
            $table->string('contact_person')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('tax_office')->nullable();
            $table->string('tax_no', 20)->nullable();
            $table->string('iban', 40)->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Tüm para hareketleri: müşteri tahsilatı, iade, tur gideri, genel gider, virman
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10);                       // income | expense
            $table->string('category', 30);                   // tour_payment, refund, hotel, flight ...
            $table->foreignId('cash_account_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 14, 2);                 // hesabın para biriminde
            $table->string('currency', 3);
            $table->decimal('exchange_rate', 12, 4)->default(1); // 1 birim = ? TL
            $table->decimal('amount_try', 14, 2)->default(0);
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('booking_amount', 14, 2)->nullable(); // rezervasyon para biriminde karşılığı
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tour_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method', 20)->nullable();         // cash | transfer | credit_card | other
            $table->date('date');
            $table->string('document_no')->nullable();        // makbuz / fatura no
            $table->string('description')->nullable();
            $table->string('attachment')->nullable();
            $table->string('transfer_group')->nullable();     // virman çiftini bağlar
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('cash_accounts');
    }
};
