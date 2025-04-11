<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('conversion_jobs', function (Blueprint $table) {
            $table->id(); // ستون شناسه اصلی، عددی و افزایشی
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // کلید خارجی به جدول users (مهم!)
            $table->string('original_filename'); // نام فایل اکسل اصلی که کاربر آپلود کرده
            $table->string('input_filepath'); // مسیر ذخیره فایل اکسل ورودی (نسبی به storage/app)
            $table->string('output_filepath')->nullable(); // مسیر فایل خروجی HTML/SQL (نسبی)، می‌تواند خالی باشد
            $table->enum('output_format', ['html', 'sql'])->default('html'); // فرمت درخواستی کاربر
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending'); // وضعیت فعلی کار
            $table->text('error_message')->nullable(); // پیغام خطا در صورت شکست
            $table->timestamps(); // ستون‌های created_at و updated_at

            $table->index('user_id'); // ایندکس برای بهبود سرعت کوئری بر اساس کاربر
            $table->index('status'); // ایندکس برای بهبود سرعت کوئری بر اساس وضعیت
        });
    }

    /**
     * Reverse the migrations.
     * این متد برای زمانی است که می‌خواهید مایگریشن را لغو کنید (rollback).
     */
    public function down(): void
    {
        Schema::dropIfExists('conversion_jobs'); // جدول را حذف می‌کند
    }
};