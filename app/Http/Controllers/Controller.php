<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Compress oversized raster uploads with the GD driver.
     *
     * The 2 MB threshold, JPEG quality, PNG output, and temporary-upload
     * contract match the legacy Intervention Image implementation.
     */
    protected function compressImageFile(UploadedFile $file): UploadedFile
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, ['jpeg', 'jpg', 'png'], true) || $file->getSize() <= 2048 * 1024) {
            return $file;
        }

        $image = Image::decode($file);
        $encoded = in_array($extension, ['jpeg', 'jpg'], true)
            ? $image->encodeUsingFileExtension($extension, quality: 30)
            : $image->encodeUsingFileExtension('png');
        $tmpFile = tempnam(sys_get_temp_dir(), 'compressed-');

        if ($tmpFile === false) {
            throw new Exception('Tidak dapat membuat file sementara untuk kompresi gambar.');
        }

        file_put_contents($tmpFile, (string) $encoded);

        return new UploadedFile(
            $tmpFile,
            $file->getClientOriginalName(),
            $file->getClientMimeType(),
            null,
            true
        );
    }

    protected function storeImportFile(Request $request, string $disk = 'public'): string
    {
        $request->validate([
            'fileImport' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $file = $request->file('fileImport');

        if (! $file || ! $file->isValid()) {
            throw new Exception('File import tidak valid.');
        }

        $path = 'import';

        if (! Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->makeDirectory($path);
        }

        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeBaseName = preg_replace('/[^A-Za-z0-9._-]+/', '-', $baseName) ?: 'import';
        $extension = strtolower($file->getClientOriginalExtension() ?: 'xlsx');
        $filename = $safeBaseName.'_'.Carbon::now()->format('YmdHis').'.'.$extension;

        $file->storeAs($path, $filename, $disk);

        return Storage::disk($disk)->path($path.'/'.$filename);
    }

    protected function parseExportDateRange(?string $value): array
    {
        if (! $value) {
            throw new Exception('Rentang tanggal wajib diisi.');
        }

        $dates = preg_split('/\s+-\s+/', trim($value), 2);

        if (count($dates) < 2) {
            $compactValue = preg_replace('/\s+/', '', $value);

            if (preg_match('/^(\d{4}-\d{2}-\d{2})-(\d{4}-\d{2}-\d{2})$/', $compactValue, $matches)) {
                $dates = [$matches[1], $matches[2]];
            } elseif (preg_match('/^(\d{1,2}\/\d{1,2}\/\d{4})-(\d{1,2}\/\d{1,2}\/\d{4})$/', $compactValue, $matches)) {
                $dates = [$matches[1], $matches[2]];
            } else {
                throw new Exception('Format rentang tanggal tidak valid.');
            }
        }

        try {
            $date1 = Carbon::parse($dates[0])->format('Y-m-d');
            $date2 = Carbon::parse($dates[1])->addDay()->format('Y-m-d');
        } catch (Exception $e) {
            throw new Exception('Format rentang tanggal tidak valid.');
        }

        return [$date1, $date2];
    }
}
