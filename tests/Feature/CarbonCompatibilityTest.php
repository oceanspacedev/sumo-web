<?php

namespace Tests\Feature;

use App\Models\Insurance;
use App\Models\InsuranceUpdate;
use App\Models\Rent;
use App\Models\RentUpdate;
use App\Models\User;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CarbonCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo(now()->setDate(2026, 9, 13)->setTime(12, 0));
        $this->actingAs(User::factory()->create());
        Storage::fake('local');
        DB::table('insurance_categories')->insert(['id' => 1, 'insurance_category' => 'Fixture Category']);
        DB::table('insurance_scopes')->insert(['id' => 1, 'insurance_scope' => 'Fixture Scope']);
    }

    public static function rentalPeriods(): array
    {
        return [
            'leap year' => ['2024-01-01 00:00:00', '2025-01-01 00:00:00', 'Rp 12.000.000', 'Rp 9.000.000'],
            'partial last day truncated' => ['2024-01-01 00:00:00', '2025-01-01 23:59:59', 'Rp 12.000.000', 'Rp 9.000.000'],
            'reversed dates remain absolute' => ['2025-01-01 23:59:59', '2024-01-01 00:00:00', 'Rp 12.000.000', 'Rp 9.000.000'],
            'next whole day rounds to two years' => ['2024-01-01 00:00:00', '2025-01-02 00:00:00', 'Rp 24.000.000', 'Rp 21.000.000'],
            'ordinary year' => ['2025-01-01 00:00:00', '2026-01-01 00:00:00', 'Rp 12.000.000', 'Rp 9.000.000'],
            'less than one whole day' => ['2026-01-01 00:00:00', '2026-01-01 23:59:59', 'Rp 0', 'Rp -3.000.000'],
        ];
    }

    #[DataProvider('rentalPeriods')]
    public function test_original_rental_nominal_in_listing_and_detail(
        string $join, string $expiry, string $total, string $remaining
    ): void {
        $rent = Rent::create($this->rentAttributes($join, $expiry));

        $index = $this->get('/rent')->assertOk();
        $row = $this->rowFor($index->getContent(), 'RENT-BASE');
        $this->assertSame($total, $this->cellText($row, 12));
        $this->assertSame($remaining, $this->cellText($row, 15));
        $this->assertSame($total === 'Rp 0' ? 'LEBIH' : 'BELUM LUNAS', $this->cellText($row, 16));

        $show = $this->get('/rent/'.$rent->id)->assertOk();
        $xpath = $this->xpath($show->getContent());
        $this->assertSame('Total Tagihan : '.$total,
            $this->normalize($xpath->query('//h5[strong[normalize-space(.)="Total Tagihan :"]]')->item(0)->textContent));
        $this->assertSame('Sisa Tagihan : '.$remaining,
            $this->normalize($xpath->query('//h5[strong[normalize-space(.)="Sisa Tagihan :"]]')->item(0)->textContent));
    }

    #[DataProvider('rentalPeriods')]
    public function test_renewed_rental_nominal_in_listing_and_detail(
        string $join, string $expiry, string $total, string $remaining
    ): void {
        $rent = Rent::create($this->rentAttributes('2023-01-01', '2024-01-01'));
        RentUpdate::create(array_merge($this->rentAttributes($join, $expiry), [
            'rent_id' => $rent->id, 'rent_code' => 'RENT-UPDATE',
        ]));

        $index = $this->get('/rent')->assertOk();
        $row = $this->rowFor($index->getContent(), 'RENT-UPDATE');
        $this->assertSame($total, $this->cellText($row, 12));
        $this->assertSame($remaining, $this->cellText($row, 15));

        $show = $this->get('/rent/'.$rent->id)->assertOk();
        $row = $this->rowFor($show->getContent(), 'RENT-UPDATE');
        $this->assertSame($total, $this->cellText($row, 10));
        $this->assertSame($remaining, $this->cellText($row, 13));
        $this->assertSame($total === 'Rp 0' ? 'LEBIH' : 'BELUM LUNAS', $this->cellText($row, 14));
    }

    public static function insuranceSources(): array
    {
        return ['original contract' => [false], 'renewed contract' => [true]];
    }

    #[DataProvider('insuranceSources')]
    public function test_insurance_reminders_keep_signed_whole_day_boundaries(bool $renewal): void
    {
        $fixtures = [];
        // Carbon 2 truncated signed elapsed days from now(), including partial days.
        // A calendar-day comparison would incorrectly move the 14.99/30.99-day rows.
        foreach ([
            ['POL-PAST', '2026-09-12 00:00:00', '#f2dede'],
            ['POL-TODAY', '2026-09-13 12:00:00', '#f2dede'],
            ['POL-14', '2026-09-27 12:00:00', '#f2dede'],
            ['POL-14-99', '2026-09-28 11:59:59', '#f2dede'],
            ['POL-15', '2026-09-28 12:00:00', '#fcf8e3'],
            ['POL-30', '2026-10-13 12:00:00', '#fcf8e3'],
            ['POL-30-99', '2026-10-14 11:59:59', '#fcf8e3'],
            ['POL-31', '2026-10-14 12:00:00', 'white'],
        ] as [$code, $expiry, $color]) {
            $insurance = Insurance::create($this->insuranceAttributes(
                $renewal ? 'OLD-'.$code : $code, $renewal ? '2027-01-01' : $expiry
            ));
            if ($renewal) {
                InsuranceUpdate::create(array_merge($this->insuranceAttributes($code, $expiry), [
                    'insurance_id' => $insurance->id,
                ]));
            }
            $fixtures[] = [$insurance, $code, $color];
        }

        $index = $this->get('/insurance')->assertOk();
        foreach ($fixtures as [$insurance, $code, $color]) {
            $row = $this->rowFor($index->getContent(), $code);
            $this->assertStringContainsString('background-color: '.$color.';', $row->getAttribute('style'), $code);
            if ($renewal) {
                $show = $this->get('/insurance/'.$insurance->id)->assertOk();
                $row = $this->rowFor($show->getContent(), $code);
                $this->assertStringContainsString('background-color: '.$color.';', $row->getAttribute('style'), $code);
            }
        }
    }

    public function test_empty_payment_evidence_does_not_crash_when_rent_directory_exists(): void
    {
        Storage::disk('local')->makeDirectory('public/Rent_File');

        $rent = Rent::create(array_merge($this->rentAttributes('2025-01-01 00:00:00', '2026-01-01 00:00:00'), [
            'rent_code' => 'RENT-EMPTY',
            'payment_evidence_file' => null,
        ]));
        RentUpdate::create(array_merge($this->rentAttributes('2025-01-01 00:00:00', '2026-01-01 00:00:00'), [
            'rent_id' => $rent->id,
            'rent_code' => 'RENT-UPD',
            'payment_evidence_file' => '',
        ]));

        $index = $this->get('/rent')->assertOk();
        $row = $this->rowFor($index->getContent(), 'RENT-UPD');
        $this->assertSame('Tidak ada file', $this->cellText($row, 19));

        $show = $this->get('/rent/'.$rent->id)->assertOk();
        $row = $this->rowFor($show->getContent(), 'RENT-UPD');
        $this->assertStringContainsString('Tidak ada file', $this->normalize($row->textContent));
    }

    public function test_existing_payment_evidence_still_renders_document_link(): void
    {
        Storage::disk('local')->put('public/Rent_File/proof.pdf', 'proof');

        Rent::create(array_merge($this->rentAttributes('2025-01-01 00:00:00', '2026-01-01 00:00:00'), [
            'rent_code' => 'RENT-FILE',
            'payment_evidence_file' => 'proof.pdf',
        ]));

        $index = $this->get('/rent')->assertOk();
        $row = $this->rowFor($index->getContent(), 'RENT-FILE');
        $this->assertStringContainsString('Lihat Dokumen', $this->normalize($row->textContent));
        $this->assertStringContainsString('storage/Rent_File/proof.pdf', $row->ownerDocument->saveHTML($row));
    }

    private function rentAttributes(string $join, string $expiry): array
    {
        return [
            'rent_code' => 'RENT-BASE', 'rented_detail' => 'Fixture Building', 'rented_address' => 'Fixture Address',
            'first_party' => 'Owner', 'second_party' => 'Tenant', 'rent_per_year' => 12000000,
            'cvcs_fund' => 1000000, 'online_fund' => 2000000, 'join_date' => $join, 'expired_date' => $expiry,
            'month_before_reminder' => 1, 'user_id' => auth()->id(), 'status' => 'BERJALAN',
            'payment_evidence_file' => 'fixture-not-present.pdf',
        ];
    }

    private function insuranceAttributes(string $code, string $expiry): array
    {
        return [
            'policy_number' => $code, 'insured_name' => 'Fixture Insured', 'insurance_category_id' => 1,
            'insurance_scope_id' => 1, 'user_id' => auth()->id(), 'join_date' => '2025-01-01',
            'expired_date' => $expiry, 'status' => 'BERJALAN', 'stock_worth' => 1000000,
            'actual_stock_worth' => 500000, 'building_worth' => 1000000, 'stock_premium' => 100000,
            'building_premium' => 100000,
        ];
    }

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML($html);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return new DOMXPath($document);
    }

    private function rowFor(string $html, string $code): DOMElement
    {
        $rows = $this->xpath($html)->query('//tbody/tr[td[normalize-space(.)="'.$code.'"]]');
        $this->assertCount(1, $rows, 'Expected one rendered row for '.$code);

        return $rows->item(0);
    }

    private function cellText(DOMElement $row, int $index): string
    {
        return $this->normalize($row->getElementsByTagName('td')->item($index)->textContent);
    }

    private function normalize(string $text): string
    {
        return trim(preg_replace('/\s+/', ' ', $text));
    }
}
