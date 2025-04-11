<?php

namespace App\Jobs; // تک بک اسلش

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue; // ---> اصلاح شده
use Illuminate\Foundation\Bus\Dispatchable; // ---> اصلاح شده
use Illuminate\Queue\InteractsWithQueue;    // ---> اصلاح شده
use Illuminate\Queue\SerializesModels;      // ---> اصلاح شده
use Illuminate\Support\Facades\Log;         // ---> اصلاح شده
use Illuminate\Support\Facades\Storage;     // ---> اصلاح شده
use Illuminate\Support\Str;                 // ---> اصلاح شده
use Symfony\Component\Process\Process;             // ---> اصلاح شده
use Symfony\Component\Process\Exception\ProcessTimedOutException; // ---> اصلاح شده
use Throwable;
use App\Models\ConversionJob;

class ProcessExcelFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 600; // 10 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(public int $jobId)
    {
        // Store the job ID
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting processing for job ID: {$this->jobId}");

        $job = ConversionJob::find($this->jobId);
        if (!$job) {
            Log::error("Job ID {$this->jobId} not found in handle start.");
            return;
        }

        // Prevent reprocessing completed/failed jobs
        if (in_array($job->status, ['completed', 'failed'])) {
            Log::warning("Job ID {$this->jobId} is already {$job->status}. Skipping.");
            return;
        }

        // Mark job as processing
        $job->status = 'processing';
        $job->error_message = null; // Clear previous errors
        $job->save();

        $pythonPath = config('app.python_path', 'python3');
        // مسیر اسکریپت پایتون در ریشه پروژه فرض شده
        $scriptPath = base_path('scripts/python/converter_core.py'); // <--- مسیر اسکریپت پایتون
        $inputPath = Storage::disk('local')->path($job->input_filepath); // مسیر مطلق ورودی

        // Construct output paths
        $outputDir = "outputs/{$job->user_id}";
        Storage::disk('local')->makeDirectory($outputDir); // Ensure directory exists
        // مسیر نسبی برای دیتابیس و چک کردن‌ها
        $relativeOutputPath = "{$outputDir}/{$job->id}.{$job->output_format}";
        // مسیر مطلق برای دستور Process
        $absoluteOutputPath = Storage::disk('local')->path($relativeOutputPath);

        Log::debug("Job ID {$this->jobId} Paths: Python=[{$pythonPath}], Script=[{$scriptPath}], Input=[{$inputPath}], Output(Abs)=[{$absoluteOutputPath}]");

        // Check if core script exists
        if (!file_exists($scriptPath)) {
            Log::error("Python script not found at {$scriptPath} for job ID {$this->jobId}.");
            $job = ConversionJob::find($this->jobId); // Re-fetch job
            if ($job && $job->status === 'processing') { // Check status
                $job->status = 'failed';
                $job->error_message = 'Configuration Error: Conversion script missing.';
                $job->save();
            }
            return;
        }

        try {
            $process = new Process([
                $pythonPath,
                $scriptPath,
                $inputPath,
                $absoluteOutputPath, // ارسال مسیر مطلق به اسکریپت
                (string)$job->id,     // ارسال شناسه کار به عنوان رشته
                $job->output_format // ارسال فرمت خروجی
            ]);
            $process->setTimeout($this->timeout - 30); // تنظیم تایم‌اوت
            $process->setWorkingDirectory(base_path()); // اجرای اسکریپت از ریشه پروژه
            $process->run();

            // --- Process Finished ---

            if (!$process->isSuccessful()) {
                // Process failed (non-zero exit code)
                $errorOutput = $process->getErrorOutput();
                Log::error("Process failed for job ID {$this->jobId}. Exit Code: {$process->getExitCode()}. Error: {$errorOutput}");
                $job = ConversionJob::find($this->jobId); // Re-fetch job
                if ($job && $job->status === 'processing') { // Check status
                    $job->status = 'failed';
                    $job->error_message = 'Script execution failed: ' . Str::limit($errorOutput, 180);
                    $job->save();
                }
                return; // Stop further processing
            }

            // Process reported success (zero exit code)
            Log::info("Process reported success for job ID {$this->jobId}. Output: {$process->getOutput()}");

            // تاخیر کوتاه برای اینکه آپدیت دیتابیس توسط پایتون (اگر انجام شده) اعمال شود
            sleep(2);
            $job = ConversionJob::find($this->jobId); // خواندن دوباره وضعیت کار

            // بررسی Fallback: اگر اسکریپت پایتون وضعیت را آپدیت نکرده بود
            if ($job && $job->status === 'processing') {
                Log::warning("Job ID {$this->jobId} status still 'processing' after script completion. Performing fallback check.");
                if (Storage::disk('local')->exists($relativeOutputPath)) {
                    // فایل وجود دارد، احتمالاً اسکریپت موفق بوده ولی دیتابیس را آپدیت نکرده
                    Log::info("Fallback: Output file found for job ID {$this->jobId} at {$relativeOutputPath}. Marking as completed.");
                    $job->status = 'completed';
                    $job->output_filepath = $relativeOutputPath; // ---> حیاتی: مسیر فایل خروجی را ذخیره کن
                    $job->error_message = null;
                    $job->save();
                } else {
                    // فایل وجود ندارد، احتمالاً اسکریپت بعد از اعلام موفقیت، به مشکل خورده
                    Log::error("Fallback: Output file NOT found for job ID {$this->jobId} at {$relativeOutputPath}. Marking as failed.");
                    $job->status = 'failed';
                    $job->error_message = 'Fallback: Processing finished, but output file is missing.';
                    $job->save();
                }
            } elseif ($job && $job->status === 'completed') {
                Log::info("Job ID {$this->jobId} successfully updated to 'completed' (likely by Python script).");
            } elseif ($job && $job->status === 'failed') {
                Log::warning("Job ID {$this->jobId} was updated to 'failed' (likely by Python script). Error: {$job->error_message}");
            } elseif (!$job) {
                Log::error("Job ID {$this->jobId} not found after process completion.");
            }


        } catch (ProcessTimedOutException $e) {
            Log::error("Process timed out for job ID {$this->jobId}. " . $e->getMessage());
            $job = ConversionJob::find($this->jobId); // Re-fetch job
            if ($job && $job->status === 'processing') { // Check status
                $job->status = 'failed';
                $job->error_message = 'Processing timed out after ' . ($this->timeout - 30) . ' seconds.';
                $job->save();
            }
        } catch (Throwable $e) { // گرفتن هر نوع خطای دیگری
            Log::error("Unhandled error processing job ID {$this->jobId}: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
            $job = ConversionJob::find($this->jobId); // Re-fetch job
            if ($job && $job->status === 'processing') { // Check status
                $job->status = 'failed';
                $job->error_message = 'An unexpected server error occurred: ' . Str::limit($e->getMessage(), 150);
                $job->save();
            }
        }
    }
}