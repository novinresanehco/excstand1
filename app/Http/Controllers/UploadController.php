<?php

 

use App\Jobs\ProcessExcelFile;
use App\Models\ConversionJob;
use Carbon\Carbon; // برای چک کردن isToday()
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException; // برای مدیریت خطای محدودیت
use Illuminate\View\View; // برای type hint خروجی create
use Illuminate\Http\RedirectResponse; // برای type hint خروجی store

class UploadController extends Controller
{ // ---> آکولاد باز کلاس

    /**
     * Show the form for uploading a new Excel file.
     * نمایش فرم آپلود
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        return view("upload.create");
    }

    /**
     * Store a newly uploaded Excel file and dispatch the conversion job.
     * ذخیره فایل آپلود شده و ارسال کار به صف
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        // 1. اعتبارسنجی ورودی‌ها
        $validated = $request->validate([
            'excel_file' => [
                'required', 'file', 'mimes:xlsx,xls', 'max:5120' // 5MB
            ],
            'output_format' => 'required|in:html,sql',
        ]);

        $user = Auth::user();

        // 2. بررسی محدودیت روزانه کاربر
        if ($user->last_processed_at === null || !$user->last_processed_at->isToday()) {
            $user->jobs_processed_today = 0; // ریست شمارنده اگر لازم بود
        }
        $dailyLimit = config('app.free_tier_daily_limit', 5);
        if ($user->jobs_processed_today >= $dailyLimit) {
            Log::warning("User {$user->id} daily limit reached ({$dailyLimit}).");
            // استفاده از withErrors برای نمایش خطا در فرم
            return back()->withErrors(['limit' => 'شما به محدودیت پردازش روزانه خود ('.$dailyLimit.' فایل) رسیده‌اید.'])->withInput();
        }

        // 3. ذخیره فایل آپلود شده
        try {
            $file = $validated['excel_file'];
            $originalName = $file->getClientOriginalName();
            $relativePathDir = 'uploads/' . $user->id;
            $filename = now()->format('YmdHis') . '_' . Str::slug(pathinfo($originalName, PATHINFO_FILENAME), '_') . '.' . $file->getClientOriginalExtension();

            // ذخیره فایل در دیسک local (storage/app)
            $storedPath = $file->storeAs($relativePathDir, $filename, 'local');

            if (!$storedPath) {
                Log::error("Failed to store file '{$originalName}' for user {$user->id}. Check storage permissions.");
                return back()->with('error', 'خطا در ذخیره فایل. لطفا مجددا تلاش کنید.')->withInput();
            }

            Log::info("File stored for user {$user->id} at '{$storedPath}'.");

            // 4. ایجاد رکورد در دیتابیس
            $conversionJob = ConversionJob::create([
                'user_id' => $user->id,
                'original_filename' => $originalName,
                'input_filepath' => $storedPath, // مسیر نسبی ذخیره شده
                'output_format' => $validated['output_format'],
                'status' => 'pending',
            ]);

            // 5. ارسال کار به صف
            ProcessExcelFile::dispatch($conversionJob->id);
            Log::info("Dispatched Job ID: {$conversionJob->id} for user {$user->id}.");

            // 6. آپدیت شمارنده کاربر (فقط در صورت موفقیت مراحل قبل)
            $user->increment('jobs_processed_today');
            $user->last_processed_at = now();
            $user->save();

            // 7. بازگشت به داشبورد با پیام موفقیت
            return redirect()->route('dashboard')->with('success', 'فایل با موفقیت آپلود شد! پردازش شروع شده است.');

        } catch (\Throwable $e) { // گرفتن هر نوع خطایی در مراحل ذخیره، دیتابیس یا صف
            Log::error("Upload process error for user {$user->id}: " . $e->getMessage());
            return back()->with('error', 'خطای پیش‌بینی نشده در حین آپلود. لطفا دوباره تلاش کنید.')->withInput();
        }
    }

} // ---> آکولاد بسته کلاس