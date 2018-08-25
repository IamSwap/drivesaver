<?php

namespace App\Services;

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
     * Prepare google client
     */
    public function __construct()
    {
        $this->client = new \Google_Client();

        $this->client->setAuthConfig([
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uris' => config('services.google.redirect_uri'),
        ]);

        $this->client->setDefer(true);

        $this->client->setAccessToken(auth()->user()->token);

        if ($client->isAccessTokenExpired()) {
            $r = $this->client->refreshToken(auth()->user()->refresh_token);

            auth()->user()->update([
                'token' => $r['access_token'],
                'refresh_token' => $r['refresh_token'],
            ]);

            $this->client->setAccessToken(auth()->user()->token);
        }

        $this->drive = new \Google_Service_Drive($this->client);
        $this->driveFile = new \Google_Service_Drive_DriveFile();

        $this->httpClient = new Client();
    }

    /**
     * Update file to Google Drive
     *
     * @param string $name
     * @param string $url
     * @return void
     */
    public function upload($name, $url)
    {
        $fileID = str_random(64);

        $res = $this->httpClient->request('GET', $url, [
            'progress' => function ($downloadTotal, $downloadedBytes, $uploadTotal, $uploadedBytes) use ($name, $url, $fileID) {
                $progress = 0;

                if ($downloadTotal) {
                    $progress = round($downloadedBytes * 100 / $downloadTotal);
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
                            'uploaded' => 0,
                            'upload_progress' => 0,
                            'user_id' => auth()->user()->id
                        ];

                        broadcast(new Progress($data));
                        $this->downloadProgress = $progress;
                    }
                }
            },

            'sink' => storage_path('/app/files/' . $fileID)
        ]);

        if ($res->getStatusCode() == 200) {
            $file = storage_path('/app/files/' . $fileID);
            $fileSize = filesize($file);
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
            $handle = fopen($file, "rb");

            while (!$status && !feof($handle)) {
                $progress = round($this->media->getProgress() * 100 / $fileSize);

                $chunk = fread($handle, $chunkSizeBytes);

                $status = $this->media->nextChunk($chunk);

                if ($this->uploadProgress != $progress) {
                    broadcast(new Progress([
                        'file_name' => $name,
                        'file_url' => $url,
                        'file_id' => $fileID,
                        'file_size' => $fileSize,
                        'downloaded' => $fileSize,
                        'download_progress' => 100,
                        'uploaded' => $this->media->getProgress(),
                        'upload_progress' => $progress,
                        'user_id' => auth()->user()->id
                    ]));

                    $this->uploadProgress != $progress;
                }

                if ($status) {
                    $progress = 100;

                    broadcast(new Progress([
                        'file_name' => $name,
                        'file_url' => $url,
                        'file_id' => $fileID,
                        'file_size' => $fileSize,
                        'downloaded' => $fileSize,
                        'download_progress' => 100,
                        'uploaded' => $fileSize,
                        'upload_progress' => 100,
                        'user_id' => auth()->user()->id
                    ]));

                    $this->uploadProgress = $progress;

                    Storage::disk('local')->delete('/files/' . $fileID);
                }
            }

            fclose($handle);

            $this->client->setDefer(false);
        }
    }
}
