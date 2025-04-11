<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail; // اگر نیاز به تایید ایمیل دارید، این خط را از کامنت خارج کنید
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany; // اطمینان از وجود این خط
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // برای استفاده از API Tokens لاراول

class User extends Authenticatable // ---> شروع تعریف کلاس
{ // <--- آکولاد باز برای شروع بدنه کلاس

    use HasApiTokens, HasFactory, Notifiable; // Trait ها باید داخل کلاس باشند

    /**
     * The attributes that are mass assignable.
     * این ستون‌ها می‌توانند بصورت گروهی مقداردهی شوند.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'jobs_processed_today', // ستون مربوط به محدودیت روزانه
        'last_processed_at',   // ستون زمان آخرین پردازش
    ];

    /**
     * The attributes that should be hidden for serialization.
     * این ستون‌ها در هنگام تبدیل مدل به آرایه یا JSON نمایش داده نمی‌شوند.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     * نوع داده ستون‌ها برای کار راحت‌تر با آن‌ها مشخص می‌شود.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime', // برای قابلیت تایید ایمیل (اگر فعال باشد)
        'password' => 'hashed', // پسورد بصورت خودکار هش می‌شود
        'last_processed_at' => 'datetime', // تبدیل به آبجکت Carbon برای کار با تاریخ و زمان
    ];

    /**
     * تعریف رابطه یک به چند با مدل ConversionJob.
     * هر کاربر می‌تواند چندین کار تبدیل داشته باشد.
     */
    public function conversionJobs(): HasMany
    {
        // تعریف می‌کند که این مدل (User) با مدل ConversionJob رابطه HasMany دارد
        return $this->hasMany(ConversionJob::class);
    }

} // <--- آکولاد بسته برای انتهای بدنه کلاس