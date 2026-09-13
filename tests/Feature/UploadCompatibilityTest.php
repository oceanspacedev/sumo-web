<?php

namespace Tests\Feature;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ProblemReportController;
use App\Http\Controllers\RentController;
use App\Http\Controllers\RentUpdateController;
use App\Http\Controllers\RequestController;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Milon\Barcode\DNS2D;
use Tests\TestCase;

class UploadCompatibilityTest extends TestCase
{
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    public function test_all_four_upload_helpers_preserve_small_jpeg_png_and_pdf_files_and_paths(): void
    {
        Storage::fake('public');

        $locations = [
            [RentController::class, 'payment_evidence_file', 'Rent_File', 'RENT-PAYMENT-'],
            [RentUpdateController::class, 'payment_evidence_file', 'Rent_File', 'RENT-PAYMENT-'],
            [ProblemReportController::class, 'photo_before', 'Problem_Report_File', 'PR-BEFORE-'],
            [RequestController::class, 'request_file', 'Request_File', 'Req-42_'],
        ];

        foreach ($locations as [$controller, $field, $directory, $prefix]) {
            foreach (['jpg', 'png', 'pdf'] as $extension) {
                $file = $this->upload($extension);
                $original = file_get_contents($file->getRealPath());
                $request = Request::create('/upload', 'POST', ['request_id' => 42], [], [$field => $file]);
                $name = app($controller)->storeImage($request, $field);

                $this->assertStringStartsWith($prefix, $name);
                $this->assertStringEndsWith('.'.$extension, $name);
                Storage::disk('public')->assertExists($directory.'/'.$name);
                $this->assertSame($original, Storage::disk('public')->get($directory.'/'.$name));
            }
        }
    }

    public function test_compression_threshold_is_strictly_above_two_megabytes(): void
    {
        $file = $this->upload('jpg', 2048 * 1024);
        $this->assertSame(2048 * 1024, $file->getSize());
        $this->assertSame($file, $this->compress($file));

        $compressed = $this->compress($this->upload('jpg', 2048 * 1024 + 1));
        $this->assertLessThan(2048 * 1024, $compressed->getSize());
        $this->assertSame('image/jpeg', $compressed->getMimeType());
    }

    public function test_large_jpeg_uses_gd_quality_thirty_and_keeps_legacy_exif_orientation(): void
    {
        $this->assertSame(Driver::class, config('intervention-image.driver'));
        $file = $this->upload('jpg', 2048 * 1024 + 1, orientation: 6);
        $this->assertSame(6, exif_read_data($file->getRealPath())['Orientation']);

        // The previous Image::make()->encode() path did not call orientate().
        // Compare the migrated result to GD's raw pixel decoding at quality 30.
        $gd = imagecreatefromjpeg($file->getRealPath());
        ob_start();
        imagejpeg($gd, null, 30);
        $expected = ob_get_clean();
        $compressed = $this->compress($file);

        $this->assertSame([12, 8], array_slice(getimagesize($compressed->getRealPath()), 0, 2));
        $this->assertSame($expected, file_get_contents($compressed->getRealPath()));
        $this->assertSame('fixture.jpg', $compressed->getClientOriginalName());
    }

    public function test_large_png_remains_png_and_pdf_is_never_decoded_as_an_image(): void
    {
        $png = $this->compress($this->upload('png', 2048 * 1024 + 1));
        $this->assertSame('image/png', $png->getMimeType());
        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", file_get_contents($png->getRealPath()));
        $this->assertSame([12, 8], array_slice(getimagesize($png->getRealPath()), 0, 2));

        $pdf = $this->upload('pdf', 2048 * 1024 + 1);
        $this->assertSame($pdf, $this->compress($pdf));
    }

    public function test_large_images_are_compressed_through_each_existing_upload_helper(): void
    {
        Storage::fake('public');

        foreach ([
            [RentController::class, 'payment_evidence_file', 'Rent_File'],
            [RentUpdateController::class, 'payment_evidence_file', 'Rent_File'],
            [ProblemReportController::class, 'photo_after', 'Problem_Report_File'],
            [RequestController::class, 'request_file', 'Request_File'],
        ] as [$controller, $field, $directory]) {
            foreach (['jpg', 'png'] as $extension) {
                $file = $this->upload($extension, 2048 * 1024 + 1);
                $request = Request::create('/upload', 'POST', ['request_id' => 42], [], [$field => $file]);
                $name = app($controller)->storeImage($request, $field);
                $stored = Storage::disk('public')->path($directory.'/'.$name);

                $this->assertLessThan(2048 * 1024, filesize($stored));
                $this->assertSame($extension === 'jpg' ? 'image/jpeg' : 'image/png', mime_content_type($stored));
            }
        }
    }

    public function test_upload_validation_still_rejects_unsupported_files_and_existing_ten_megabyte_limit(): void
    {
        Storage::fake('public');

        foreach ([RentController::class, RentUpdateController::class, ProblemReportController::class, RequestController::class] as $controller) {
            $field = match ($controller) {
                ProblemReportController::class => 'photo_before',
                RequestController::class => 'request_file',
                default => 'payment_evidence_file',
            };
            $file = UploadedFile::fake()->create('script.txt', 1, 'text/plain');
            $request = Request::create('/upload', 'POST', [], [], [$field => $file]);

            try {
                app($controller)->storeImage($request, $field);
                $this->fail($controller.' accepted an unsupported upload.');
            } catch (\Exception $exception) {
                $this->assertStringContainsString('must be a file of type', $exception->getMessage());
            }
        }

        foreach ([RentController::class, RentUpdateController::class, ProblemReportController::class] as $controller) {
            $field = $controller === ProblemReportController::class ? 'photo_before' : 'payment_evidence_file';
            $file = UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf');
            $request = Request::create('/upload', 'POST', [], [], [$field => $file]);

            try {
                app($controller)->storeImage($request, $field);
                $this->fail($controller.' accepted an upload above its existing limit.');
            } catch (\Exception $exception) {
                $this->assertStringContainsString('10240', $exception->getMessage());
            }
        }
    }

    public function test_qr_renderer_and_both_pdf_routes_produce_real_documents(): void
    {
        $qr = new DNS2D();
        $png = base64_decode($qr->getBarcodePNG('42', 'QRCODE'), true);
        $this->assertStringStartsWith("\x89PNG", $png);
        $this->assertGreaterThan(0, getimagesizefromstring($png)[0]);
        $this->assertStringContainsString('<div', $qr->getBarcodeHTML('42', 'QRCODE'));

        foreach (['request', 'product'] as $kind) {
            $response = $this->get('/print'.$kind.'qr/42');
            $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $response->assertHeader('Content-Disposition', 'inline; filename='.$kind.'qr.pdf');
            $this->assertStringStartsWith('%PDF-', $response->getContent());
            $this->assertStringContainsString('%%EOF', $response->getContent());
        }
    }

    private function compress(UploadedFile $file): UploadedFile
    {
        $controller = new class extends Controller
        {
            public function compress(UploadedFile $file): UploadedFile
            {
                return $this->compressImageFile($file);
            }
        };

        $result = $controller->compress($file);
        if ($result !== $file) {
            $this->temporaryFiles[] = $result->getRealPath();
        }

        return $result;
    }

    private function upload(string $extension, int $minimumBytes = 0, ?int $orientation = null): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'gais-upload-');
        $this->temporaryFiles[] = $path;

        if ($extension === 'pdf') {
            $bytes = "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\n%%EOF\n";
        } else {
            $gd = imagecreatetruecolor(12, 8);
            imagefilledrectangle($gd, 0, 0, 5, 7, imagecolorallocate($gd, 230, 30, 60));
            imagefilledrectangle($gd, 6, 0, 11, 7, imagecolorallocate($gd, 20, 160, 230));
            ob_start();
            $extension === 'png' ? imagepng($gd) : imagejpeg($gd, null, 95);
            $bytes = ob_get_clean();

            if ($orientation !== null) {
                // Minimal little-endian TIFF IFD with one EXIF Orientation tag.
                $exif = "Exif\0\0II".pack('vVv', 42, 8, 1)
                    .pack('vvVvvV', 0x0112, 3, 1, $orientation, 0, 0);
                $bytes = substr($bytes, 0, 2)."\xff\xe1".pack('n', strlen($exif) + 2).$exif.substr($bytes, 2);
            }
        }

        file_put_contents($path, str_pad($bytes, max(strlen($bytes), $minimumBytes), "\0"));

        return new UploadedFile($path, 'fixture.'.$extension, $extension === 'pdf' ? 'application/pdf' : 'image/'.($extension === 'jpg' ? 'jpeg' : 'png'), null, true);
    }
}
