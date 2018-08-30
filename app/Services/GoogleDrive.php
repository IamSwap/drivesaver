<?php

namespace App\Services;

use App\File;
use GuzzleHttp\Client;
use App\Events\Progress;
use App\Jobs\UploadFile;
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
            UploadFile::dispatch(
                $file,
                $this->client,
                $this->media,
                $this->drive,
                $this->driveFile
            )->onQueue('file');
        }
    }
}
