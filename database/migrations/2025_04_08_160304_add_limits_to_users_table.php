<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * این متد ستون‌های جدید را به جدول users اضافه می‌کند.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // اضافه کردن ستون شمارنده روزانه، از نوع عدد صحیح بدون علامت، با مقدار پیش‌فرض 0
            // و قرار دادن آن بعد از ستون remember_token (محل قرارگیری اختیاری است ولی مرتب‌تر است)
            $table->unsignedInteger('jobs_processed_today')->default(0)->after('remember_token');

            // اضافه کردن ستون زمان آخرین پردازش، از نوع timestamp، قابل قبول بودن مقدار null
            // و قرار دادن آن بعد از ستون jobs_processed_today
            $table->timestamp('last_processed_at')->nullable()->after('jobs_processed_today');
        });
    }

    /**
     * Reverse the migrations.
     * این متد در صورت نیاز به لغو مایگریشن (rollback)، ستون‌های اضافه شده را حذف می‌کند.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // حذف ستون‌های اضافه شده
            $table->dropColumn(['jobs_processed_today', 'last_processed_at']);
        });
    }
};