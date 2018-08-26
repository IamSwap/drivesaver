<?php

namespace App\Services;

use App\File;
use GuzzleHttp\Client;
use App\Events\Progress;
use Illuminate\Support\Facades\Storage;

class GoogleDrive
{
    /**
     * Google Client
     *
     * @var \Google_Client
     */
    protected $client;

    /**
     * GuzzleHttp Client
     *
     * @var \GuzzleHttp\Client
     */
    protected $httpClient;

    /**
     * Google Service Drive Client
     *
     * @var \Google_Service_Drive
     */
    protected $drive;

    /**
     * Google Http Media Upload
     *
     * @var \Google_Http_MediaFileUpload
     */
    protected $media;

    /**
     * Google Http Media Upload
     *
     * @var \Google_Service_Drive_DriveFile
     */
    protected $driveFile;

    /**
     * Download progress
     *
     * @var int
     */
    protected $downloadProgress = 0;

    /**
     * Download progress
     *
     * @var int
     */
    protected $uploadProgress = 0;

    /**
     * Download rate
     *
     * @var int
     */
    protected $downloadRate = 0;

    /**
     * Download rate
     *
     * @var int
     */
    protected $uploadRate = 0;

    /**
     * Downloaded bytes
     *
     * @var int
     */
    protected $downloadedBytes = 0;

    /**
     * Uploaded bytes
     *
     * @var int
     */
    protected $uploadedBytes = 0;

    /**
     * Download start time
     *
     * @var void
     */
    protected $startTime;

    /**
     * Prepare google client
     */
    public function __construct(File $file)
    {
        $this->client = new \Google_Client();

        $this->client->setAuthConfig([
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uris' => config('services.google.redirect_uri'),
        ]);

        $this->client->setDefer(true);

        $this->client->setAccessToken([
            'access_token' => $file->user->token,
            'expires_in' => $file->user->token_expires_in,
            'created' => $file->user->token_created,
            'refresh_token' => $file->user->refresh_token,
        ]);

        if ($this->client->isAccessTokenExpired()) {
            $r = $this->client->refreshToken($file->user->refresh_token);

            $file->user->update([
                'token' => $r['access_token'],
                'refresh_token' => $r['refresh_token'],
                'token_expires_in' => $r['expires_in'],
                'token_created' => $r['created'],
            ]);

            $this->client->setAccessToken([
                'access_token' => $file->user->token,
                'expires_in' => $file->user->token_expires_in,
                'created' => $file->user->token_created,
                'refresh_token' => $file->user->refresh_token,
            ]);
        }

        $this->drive = new \Google_Service_Drive($this->client);
        $this->driveFile = new \Google_Service_Drive_DriveFile();

        $this->httpClient = new Client();
        $this->startTime = microtime(true);

        $this->file = $file;
    }

    /**
     * Update file to Google Drive
     *
     * @param string $name
     * @param string $url
     * @return void
     */
    public function upload()
    {
        $file = $this->file;
        $name = $file->name;
        $url = $file->url;

        $fileID = $file->uuid;

        $res = $this->httpClient->request('GET', $url, [
            'progress' => function ($downloadTotal, $downloadedBytes, $uploadTotal, $uploadedBytes) use ($name, $url, $file, $fileID) {
                $progress = 0;

                if ($downloadTotal) {
                    $progress = round($downloadedBytes * 100 / $downloadTotal);
                    $downloadRate = $downloadedBytes / (microtime(true) - $this->startTime);
                    $this->downloadedBytes = $downloadedBytes;
                }

                if ($file->status == 'canceled' || $file->status == 'failed') {
                    if (Storage::disk('local')->exists('/files/' . $fileID)) {
                        Storage::disk('local')->delete('/files/' . $fileID);
                    }
                    die();
                }

                if ($downloadedBytes <= $downloadTotal) {
                    if ($this->downloadProgress != $progress) {
                        $data = [
                            'file_name' => $name,
                            'file_url' => $url,
                            'file_id' => $fileID,
                            'file_size' => $downloadTotal,
                            'downloaded' => $downloadedBytes,
                            'download_progress' => $progress,
                            'download_rate' => $downloadRate,
                            'uploaded' => 0,
                            'upload_progress' => 0,
                            'upload_rate' => $this->uploadRate,
                            'user_id' => $file->user->id,
                            'status' => $file->status
                        ];

                        broadcast(new Progress($data));
                        $this->downloadProgress = $progress;
                    }
                }
            },

            'sink' => storage_path('/app/files/' . $fileID)
        ]);

        if ($res->getStatusCode() == 200) {
            $file->update([
                'status' => 'uploading'
            ]);

            $storedFile = storage_path('/app/files/' . $fileID);
            $fileSize = filesize($storedFile);
            $chunkSizeBytes = 1 * 1024 * 1024;

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

                // $chunk = fread($handle, $chunkSizeBytes);
                $chunk = $this->readChunk($handle, $chunkSizeBytes);
                $status = $this->media->nextChunk($chunk);

                if ($this->uploadProgress != $progress) {
                    broadcast(new Progress([
                        'file_name' => $name,
                        'file_url' => $url,
                        'file_id' => $fileID,
                        'file_size' => $fileSize,
                        'downloaded' => $fileSize,
                        'download_progress' => 100,
                        'download_rate' => $this->downloadRate,
                        'uploaded' => $this->media->getProgress(),
                        'upload_progress' => $progress,
                        'upload_rate' => $this->uploadRate,
                        'user_id' => $file->user->id,
                        'status' => $file->status
                    ]));

                    $this->uploadProgress != $progress;
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
                        'download_rate' => $this->downloadRate,
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
