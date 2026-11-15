<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * `clients:recover-emails` is a read-only report: it must never touch the
 * database, only produce a CSV with the recovery candidates.
 */
class RecoverClientEmailsCommandTest extends TestCase
{
    use DatabaseTransactions;

    private string $csv;

    protected function setUp(): void
    {
        parent::setUp();

        $this->csv = storage_path('app/reports/test-recover-emails.csv');

        if (file_exists($this->csv)) {
            @unlink($this->csv);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->csv)) {
            @unlink($this->csv);
        }

        parent::tearDown();
    }

    public function test_it_reports_candidates_without_changing_the_database(): void
    {
        // social_id holds the email (old app behaviour).
        $recoverable = Client::query()->create([
            'full_name' => 'Report Candidate',
            'email'     => null,
            'password'  => bcrypt('secret123'),
            'social_id' => 'candidate.report@example.com',
            'locale'    => 'en',
        ]);

        // Shared device token with an account that has an email.
        $device = Client::query()->create([
            'full_name' => 'Device Candidate',
            'email'     => null,
            'password'  => bcrypt('secret123'),
            'social_id' => 'google-sub-device',
            'fb_token'  => 'shared-device-token',
            'locale'    => 'en',
        ]);

        Client::query()->create([
            'full_name' => 'Device Candidate',
            'email'     => 'device-owner@example.com',
            'password'  => bcrypt('secret123'),
            'fb_token'  => 'shared-device-token',
            'locale'    => 'en',
        ]);

        $this->artisan('clients:recover-emails', ['--csv' => $this->csv])
            ->assertSuccessful();

        $this->assertFileExists($this->csv);

        $report = file_get_contents($this->csv);

        $this->assertStringContainsString('candidate.report@example.com', $report);
        $this->assertStringContainsString('device-owner@example.com', $report);

        // Still read-only.
        $this->assertNull($recoverable->refresh()->email);
        $this->assertNull($device->refresh()->email);
    }

    public function test_only_filters_the_report_by_evidence_type(): void
    {
        Client::query()->create([
            'full_name' => 'Only Social',
            'email'     => null,
            'password'  => bcrypt('secret123'),
            'social_id' => 'only.social@example.com',
            'locale'    => 'en',
        ]);

        $this->artisan('clients:recover-emails', ['--csv' => $this->csv, '--only' => 'device'])
            ->assertSuccessful();

        $this->assertStringNotContainsString(
            'only.social@example.com',
            file_get_contents($this->csv)
        );
    }
}
