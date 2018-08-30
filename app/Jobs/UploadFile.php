<?php

namespace App\Jobs;

use App\File;
use App\Events\Progress;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UploadFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $file;
    protected $client;
    protected $media;
    protected $drive;
    protected $driveFile;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(File $file, $client, $media, $drive, $driveFile)
    {
        $this->file = $file;
        $this->client = $client;
        $this->media = $media;
        $this->drive =  $drive;
        $this->driveFile = $driveFile;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $file = $this->file;
        $fileID = $this->file->uuid;

        $file->update([
            'status' => 'uploading'
        ]);

        $storedFile = storage_path('/app/files/' . $fileID);
        $fileSize = filesize($storedFile);
        $chunkSizeBytes = 100 * 1024 * 1024;

        $this->driveFile->name = $name;
        $request = $this->drive->files->create($this->driveFile);

        $this->media = new \Google_Http_MediaFileUpload(
            $this->client,
            $request,
            Storage::disk('local')->mimeType('/files/' . $fileID),
            null,
            true,
            $chunkSizeBytes
        );

        $this->media->setFileSize($fileSize);

        $status = false;
        $handle = fopen($storedFile, "rb");

        $startTime = microtime(true);

        while (!$status && !feof($handle)) {
            $progress = round($this->media->getProgress() * 100 / $fileSize);

            $this->uploadRate = $this->media->getProgress() / (microtime(true) - $startTime);
            $this->uploadedBytes = $this->media->getProgress();

            // TODO: Dispatch Job per upload chunk
            // instead of sending chunk data through job. send handle & chunkbyte size.

            $chunk = fread($handle, $chunkSizeBytes);
            //$chunk = $this->readChunk($handle, $chunkSizeBytes);
            $status = $this->media->nextChunk($chunk);

            if ($this->uploadProgress != $progress) {
                broadcast(new Progress([
                    'file_name' => $name,
                    'file_url' => $url,
                    'file_id' => $fileID,
                    'file_size' => $fileSize,
                    'downloaded' => $fileSize,
                    'download_progress' => 100,
                    'download_rate' => 0,
                    'uploaded' => $this->media->getProgress(),
                    'upload_progress' => $progress,
                    'upload_rate' => $this->uploadRate,
                    'user_id' => $file->user->id,
                    'status' => $file->status
                ]));
            }

            if ($status) {
                $progress = 100;

                sleep(5); // make sure we tell user file 100% downloaded

                $file->update([
                    'status' => 'finished'
                ]);

                broadcast(new Progress([
                    'file_name' => $name,
                    'file_url' => $url,
                    'file_id' => $fileID,
                    'file_size' => $fileSize,
                    'downloaded' => $fileSize,
                    'download_progress' => 100,
                    'download_rate' => 0,
                    'uploaded' => $fileSize,
                    'upload_progress' => 100,
                    'upload_rate' => 0,
                    'user_id' => $file->user->id,
                    'status' => $file->status
                ]));

                $this->uploadProgress = $progress;

                if (Storage::disk('local')->exists('/files/' . $fileID)) {
                    Storage::disk('local')->delete('/files/' . $fileID);
                }

                if ($file->status == 'canceled' || $file->status == 'failed') {
                    if (Storage::disk('local')->exists('/files/' . $fileID)) {
                        Storage::disk('local')->delete('/files/' . $fileID);
                    }
                    break;
                }
            }
        }

        fclose($handle);

        $this->client->setDefer(false);
    }


    private function readChunk($handle, $chunkSize)
    {
        $byteCount = 0;
        $giantChunk = "";

        while (!feof($handle)) {
            // fread will never return more than 8192 bytes if the stream is read buffered and it does not represent a plain file
            $chunk = fread($handle, 8192);
            $byteCount += strlen($chunk);
            $giantChunk .= $chunk;
            if ($byteCount >= $chunkSize) {
                return $giantChunk;
            }
        }
        return $giantChunk;
    }
}
