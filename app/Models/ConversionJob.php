<?php

namespace App\Models; // ---> مطمئن شوید این خط صحیح است

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // برای رابطه با User

class ConversionJob extends Model
{ // ---> آکولاد باز کلاس
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'original_filename',
        'input_filepath',
        'output_filepath',
        'output_format',
        'status',
        'error_message',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        // 'output_format' => 'string', // نیازی به کست enum نیست معمولا
        // 'status' => 'string',      // نیازی به کست enum نیست معمولا
    ];

    /**
     * Get the user that owns the job.
     * تعریف رابطه چند به یک با مدل User
     */
    public function user(): BelongsTo
    {
        // تعریف می‌کند که این مدل (ConversionJob) به مدل User تعلق دارد
        return $this->belongsTo(User::class);
    }

} // ---> آکولاد بسته کلاس