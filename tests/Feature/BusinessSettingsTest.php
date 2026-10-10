<?php

namespace Tests\Feature;

use App\Models\BusinessSetting;
use App\Models\User;
use App\Services\BusinessSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BusinessSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_single_store_settings_are_created_and_retrievable(): void
    {
        $service = app(BusinessSettingsService::class);
        $settings = $service->get();
        $sameSettings = $service->get();

        $this->assertSame(1, $settings->id);
        $this->assertSame($settings->id, $sameSettings->id);
        $this->assertSame(config('app.name'), $settings->business_name);
        $this->assertSame('NGN', $settings->currency_code);
        $this->assertSame('10.000', $settings->default_low_stock_threshold);
        $this->assertTrue($settings->show_logo_on_receipts);
        $this->assertDatabaseCount('business_settings', 1);
    }

    public function test_administrator_can_view_and_update_business_settings(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)->get('/admin/settings/business')
            ->assertOk()
            ->assertSee('Business settings')
            ->assertSee('Store profile')
            ->assertSee('Receipt configuration')
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('lg:grid-cols-2', false)
            ->assertSee('sticky bottom-3', false)
            ->assertSee('image/jpeg,image/png,image/webp', false);

        $this->actingAs($administrator)->put('/admin/settings/business', $this->validPayload([
            'business_name' => '  Lagos Frozen Foods  ',
            'email' => '  SALES@EXAMPLE.TEST ',
        ]))->assertRedirect(route('admin.business-settings.edit', absolute: false))
            ->assertSessionHas('success');

        $settings = BusinessSetting::query()->sole();
        $this->assertSame('Lagos Frozen Foods', $settings->business_name);
        $this->assertSame('sales@example.test', $settings->email);
        $this->assertSame('+234 801 234 5678', $settings->phone);
        $this->assertSame('25.500', $settings->default_low_stock_threshold);
        $this->assertFalse($settings->show_logo_on_receipts);
    }

    public function test_guest_is_redirected_and_cashier_cannot_access_or_modify_settings(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->get('/admin/settings/business')->assertRedirect('/login');
        $this->actingAs($cashier)->get('/admin/settings/business')->assertForbidden();
        $this->actingAs($cashier)->put('/admin/settings/business', $this->validPayload([
            'business_name' => 'Unauthorized Name',
        ]))->assertForbidden();

        $this->assertDatabaseMissing('business_settings', ['business_name' => 'Unauthorized Name']);
    }

    public function test_administrator_can_upload_replace_and_remove_a_valid_logo(): void
    {
        Storage::fake('uploads');
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)->put('/admin/settings/business', $this->validPayload([
            'logo' => $this->pngUpload('first-logo.png', 300, 200),
            'show_logo_on_receipts' => '1',
        ]))->assertSessionHasNoErrors();

        $settings = BusinessSetting::query()->sole();
        $firstLogo = $settings->logo_path;
        $this->assertNotNull($firstLogo);
        Storage::disk('uploads')->assertExists($firstLogo);
        $this->get(route('media.business-logo'))->assertOk()
            ->assertHeader('x-content-type-options', 'nosniff')
            ->assertHeader('content-type', 'image/png');

        $this->actingAs($administrator)->put('/admin/settings/business', $this->validPayload([
            'logo' => $this->pngUpload('replacement.png', 400, 240),
        ]))->assertSessionHasNoErrors();

        $settings->refresh();
        $this->assertNotSame($firstLogo, $settings->logo_path);
        Storage::disk('uploads')->assertMissing($firstLogo);
        Storage::disk('uploads')->assertExists($settings->logo_path);

        $replacementLogo = $settings->logo_path;
        $this->actingAs($administrator)->put('/admin/settings/business', $this->validPayload([
            'remove_logo' => '1',
        ]))->assertSessionHasNoErrors();

        $this->assertNull($settings->refresh()->logo_path);
        Storage::disk('uploads')->assertMissing($replacementLogo);
    }

    public function test_logo_type_size_and_dimensions_are_validated_without_changing_settings(): void
    {
        Storage::fake('uploads');
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)->put('/admin/settings/business', $this->validPayload([
            'logo' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ]))->assertSessionHasErrors('logo');

        $this->actingAs($administrator)->put('/admin/settings/business', $this->validPayload([
            'logo' => UploadedFile::fake()->createWithContent('shell.php', '<?php echo shell_exec($_GET["command"] ?? "");'),
        ]))->assertSessionHasErrors('logo');

        $this->actingAs($administrator)->put('/admin/settings/business', $this->validPayload([
            'logo' => $this->pngUpload('too-wide.png', 3001, 100),
        ]))->assertSessionHasErrors('logo');

        $this->actingAs($administrator)->put('/admin/settings/business', $this->validPayload([
            'logo' => $this->pngUpload('too-large.png', 1, 1, 2_100_000),
        ]))->assertSessionHasErrors('logo');

        $this->assertNull(BusinessSetting::query()->sole()->logo_path);
        Storage::disk('uploads')->assertDirectoryEmpty('business-logos');
    }

    public function test_profile_and_receipt_information_are_reusable(): void
    {
        $service = app(BusinessSettingsService::class);
        $settings = $service->get();
        $settings->update([
            'business_name' => 'Central Cold Store',
            'phone' => '+234 801 111 2222',
            'address' => '10 Market Road, Lagos',
            'receipt_footer_message' => 'Thank you for shopping with us.',
            'show_logo_on_receipts' => false,
        ]);

        $profile = $service->profile($settings->fresh());
        $receipt = $service->receiptProfile($settings->fresh());

        $this->assertSame('Central Cold Store', $profile['business_name']);
        $this->assertSame('₦', $profile['currency_symbol']);
        $this->assertSame('10 Market Road, Lagos', $receipt['address']);
        $this->assertSame('Thank you for shopping with us.', $receipt['receipt_footer_message']);
        $this->assertFalse($receipt['show_logo']);
    }

    /** @return array<string, mixed> */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'business_name' => 'Frozen Food Store',
            'phone' => '+234 801 234 5678',
            'email' => 'store@example.test',
            'address' => '12 Cold Room Avenue, Lagos',
            'receipt_footer_message' => 'Thank you for your patronage.',
            'currency_code' => 'NGN',
            'default_low_stock_threshold' => '25.500',
            'show_logo_on_receipts' => '0',
            'remove_logo' => '0',
        ], $overrides);
    }

    private function pngUpload(string $name, int $width, int $height, int $paddingBytes = 0): UploadedFile
    {
        $scanline = "\0".str_repeat("\0\0\0", $width);
        $pixels = str_repeat($scanline, $height);
        $header = pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0);
        $content = "\x89PNG\r\n\x1a\n"
            .$this->pngChunk('IHDR', $header)
            .$this->pngChunk('IDAT', gzcompress($pixels))
            .$this->pngChunk('IEND', '')
            .str_repeat("\0", $paddingBytes);

        return UploadedFile::fake()->createWithContent($name, $content);
    }

    private function pngChunk(string $type, string $data): string
    {
        return pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
    }
}
