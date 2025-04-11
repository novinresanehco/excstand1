<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ConversionJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\RedirectResponse;

class DownloadController extends Controller
{
    /**
     * Handle the download request for a completed conversion job.
     *
     * @param  \App\Models\ConversionJob  $job
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\RedirectResponse
     */
    public function download(ConversionJob $job)
    {
        // 1. Check Ownership
        if (Auth::id() !== $job->user_id) {
            Log::warning("Unauthorized download attempt for job {$job->id} by user " . Auth::id());
            abort(403, 'Unauthorized access.');
        }

        // 2. Check Status
        if ($job->status !== 'completed') {
            Log::info("Download attempt for non-completed job {$job->id} (status: {$job->status}) by user {$job->user_id}.");
            abort(404, 'File not ready or processing failed.');
        }

        // 3. Check File Existence
        if (empty($job->output_filepath) || !Storage::disk('local')->exists($job->output_filepath)) {
            Log::error("Output file missing for completed job {$job->id}. Path: {$job->output_filepath}.");
            abort(404, 'Output file not found.');
        }

        // 4. Construct Safe Download Filename
        $originalFilename = $job->original_filename ?? 'download';
        $outputExtension = pathinfo($job->output_filepath, PATHINFO_EXTENSION);
        $baseName = Str::slug(pathinfo($originalFilename, PATHINFO_FILENAME)); // Sanitize original base name
        $downloadName = "{$baseName}.{$outputExtension}";

        // 5. Log and Attempt Download
        Log::info("User {$job->user_id} downloading file for job {$job->id}. Path: {$job->output_filepath}, Download Name: {$downloadName}");

        try {
            return Storage::disk('local')->download($job->output_filepath, $downloadName);
        } catch (\Throwable $e) {
            Log::error("Download failed for job {$job->id}: " . $e->getMessage());
            abort(500, 'Could not download the file due to a server error.');
        }
    }
}
