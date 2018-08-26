<?php

namespace App\Http\Controllers;

use App\File;
use App\Jobs\DownloadFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    /**
     * Return list of files
     *
     * @param Request $request
     * @return void
     */
    public function index(Request $request)
    {
        return $request->user()->files()->get();
    }

    /**
     * Store file
     *
     * @param Request $request
     * @return void
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'url' => 'required'
        ]);

        $file = $request->user()->files()->create([
            'name' => ($request->input('name')) ? $request->input('name') : $this->getFileNameFromURL($request->input('url')),
            'url' => $request->input('url'),
            'status' => 'downloading',
            'uuid' => str_random(64)
        ]);

        DownloadFile::dispatch($file)->onQueue('file');
    }

    /**
     * Delete file from database
     *
     * @param File $file
     * @return void
     */
    public function destroy(File $file)
    {
        if (Storage::disk('local')->exists('/files/' . $file->uuid)) {
            Storage::disk('local')->delete('/files/' . $file->uuid);
        }

        $file->delete();
    }

    /**
     * Cancel file download
     *
     * @param File $file
     * @return void
     */
    public function cancel(File $file)
    {
        if (Storage::disk('local')->exists('/files/' . $file->uuid)) {
            Storage::disk('local')->delete('/files/' . $file->uuid);
        }

        $file->update([
            'status' => 'canceled'
        ]);
    }

    /**
     * Retry file download
     *
     * @param File $file
     * @return void
     */
    public function retry(File $file)
    {
        if (Storage::disk('local')->exists('/files/' . $file->uuid)) {
            Storage::disk('local')->delete('/files/' . $file->uuid);
        }

        $file->update([
            'status' => 'downloading'
        ]);

        DownloadFile::dispatch($file)->onQueue('file');
    }

    /**
     * Redownload file
     *
     * @param File $file
     * @return void
     */
    public function redownload(File $file)
    {
        if (Storage::disk('local')->exists('/files/' . $file->uuid)) {
            Storage::disk('local')->delete('/files/' . $file->uuid);
        }

        $file->update([
            'status' => 'downloading'
        ]);

        DownloadFile::dispatch($file)->onQueue('file');
    }

    /**
     * Get file name from URL
     *
     * @param string $url
     * @return void
     */
    private function getFileNameFromURL($url)
    {
        $name = basename($url);
        $name = str_replace('%20', ' ', $name);

        return $name;
    }
}
