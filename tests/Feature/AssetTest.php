<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Support\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_url_changes_when_content_changes_even_with_the_same_file_timestamp(): void
    {
        $path = 'asset-version-test-'.bin2hex(random_bytes(8)).'.css';
        $file = public_path($path);
        try {
            file_put_contents($file, 'body{color:red}');
            touch($file, 1700000000);
            $first = Asset::url($path);
            $this->assertSame($first, Asset::url($path));
            file_put_contents($file, 'body{color:tan}');
            touch($file, 1700000000);
            $this->assertNotSame($first, Asset::url($path));
            $this->assertStringContainsString('?v=', $first);
        } finally {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function test_login_internal_shared_and_print_pages_reference_versioned_assets(): void
    {
        $this->assertVersionedAssets($this->get(route('login'))->assertOk()->getContent(), 2);
        $this->signIn();
        $document = Document::create([
            'category' => 'Pembangunan Baru - RPS Pengembangan Gim',
            'receipt_date' => '2026-10-07', 'receipt_number' => 'ASSET-001',
        ]);
        $this->post(route('documents.share', $document))->assertSessionHasNoErrors();
        $this->assertVersionedAssets($this->get(route('documents.index'))->assertOk()->getContent(), 3);
        $this->assertVersionedAssets($this->get(route('shared.show', $document->fresh()->share_token))->assertOk()->getContent(), 1);
        $this->assertVersionedAssets($this->get(route('documents.print', $document))->assertOk()->getContent(), 4);
    }

    private function assertVersionedAssets(string $content, int $expected): void
    {
        $html = new \DOMDocument;
        @$html->loadHTML($content);
        $assets = (new \DOMXPath($html))->query('//link[@rel="stylesheet"]/@href | //script[@src]/@src');
        $this->assertCount($expected, $assets);
        foreach ($assets as $asset) {
            parse_str(parse_url($asset->value, PHP_URL_QUERY) ?? '', $query);
            $this->assertMatchesRegularExpression('/^[a-f0-9]{16}$/', $query['v'] ?? '');
            $this->assertFileExists(public_path(ltrim(parse_url($asset->value, PHP_URL_PATH), '/')));
        }
    }
}
