<?php

namespace App\Jobs;

use App\File;
use App\Services\GoogleDrive;
use Illuminate\Bus\Queueable;
use App\Events\DownloadFailed;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class DownloadFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $file;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(File $file)
    {
        $this->file = $file;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $drive = new GoogleDrive($this->file);

        $drive->upload();
    }

    /**
    * The job failed to process.
    *
    * @param  Exception  $exception
    * @return void
    */
    public function failed(Exception $exception)
    {
        // Send user notification of failure, etc...
        $this->file()->update([
            'status' => 'failed'
        ]);

        if (Storage::disk('local')->exists('/files/' . $this->file->uuid)) {
            Storage::disk('local')->delete('/files/' . $this->file->uuid);
        }

        broadcast(new DownloadFailed($this->file));

        $this->delete();
    }
}
