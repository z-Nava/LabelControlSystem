<?php

use App\Models\LabelPrintProfile;
use App\Models\LabelPrintProfileVersion;
use App\Models\LabelSku;
use App\Models\LabelTemplate;
use App\Models\Role;
use App\Models\SkuSerialFormat;
use App\Models\User;
use App\Services\Catalogs\LabelPrintProfileService;
use App\Services\Catalogs\SkuTemplateConfigurationService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $role = Role::query()->create([
        'name' => 'admin',
        'description' => 'Test administrator',
    ]);

    $this->admin = User::query()->create([
        'employee_no' => 'TEST-ADMIN',
        'name' => 'Test Administrator',
        'password' => Hash::make('password'),
        'is_active' => true,
    ]);

    $this->admin->roles()->attach($role);
    $this->actingAs($this->admin);
    $this->withSession(['auth_access_mode' => 'admin']);
});

function createSkuTemplateTestSku(string $standard, string $sku): LabelSku
{
    return LabelSku::query()->create([
        'sku' => $sku,
        'serial_standard' => $standard,
        'label_part_number' => "PART-{$sku}",
        'is_active' => true,
    ]);
}

function createSkuTemplateTestTemplate(
    LabelSku $sku,
    string $name,
    int $version = 1,
    bool $active = true,
): LabelTemplate {
    return LabelTemplate::query()->create([
        'name' => $name,
        'label_type' => 'serial',
        'serial_standard' => $sku->serial_standard,
        'label_sku_id' => $sku->id,
        'dpi' => 203,
        'width_mm' => 50,
        'height_mm' => 30,
        'zpl' => '^XA^FD{{serial_full}}^FS^XZ',
        'serial_layout' => ['text' => ['x' => 10, 'y' => 20, 'font_size' => 30, 'orientation' => 'N']],
        'version' => $version,
        'is_active' => $active,
    ]);
}

function createSkuTemplateTestProfile(
    LabelSku $sku,
    string $name,
    ?LabelTemplate $template = null,
    bool $active = true,
): LabelPrintProfile {
    return LabelPrintProfile::query()->create([
        'label_sku_id' => $sku->id,
        'label_type' => 'serial',
        'serial_standard' => $sku->serial_standard,
        'label_template_id' => $template?->id,
        'name' => $name,
        'dpi' => 203,
        'settings' => ['connection_type' => 'network', 'usb_required' => false],
        'is_active' => $active,
    ]);
}

function skuTemplateTestPayload(LabelSku $sku, array $overrides = []): array
{
    return array_replace([
        'label_sku_id' => $sku->id,
        'label_type' => 'serial',
        'serial_standard' => $sku->serial_standard,
        'template_name' => "Template {$sku->serial_standard}",
        'template_dpi' => 203,
        'template_width_mm' => 50,
        'template_height_mm' => 30,
        'template_is_active' => true,
        'serial_position_x' => 40,
        'serial_position_y' => 40,
        'serial_font_size' => 40,
        'serial_orientation' => 'N',
        'qr_position_x' => 30,
        'qr_position_y' => 30,
        'qr_orientation' => 'N',
        'qr_magnification' => 4,
        'qr_content_mode' => 'serial_full',
        'qr_separator' => 'pipe',
        'qr_serial_style' => 'compact',
        'sku_position_x' => 170,
        'sku_position_y' => 35,
        'sku_font_size' => 44,
        'sku_orientation' => 'N',
        'sn_position_x' => 170,
        'sn_position_y' => 95,
        'sn_font_size' => 22,
        'sn_orientation' => 'N',
        'sn_prefix' => 'SN:',
        'serial_block_count' => 1,
        'serial_block_offset_y' => 180,
        'profile_name' => "Profile {$sku->serial_standard}",
        'default_printer_name' => 'Zebra Test',
        'connection_type' => 'network',
        'default_printer_ip' => '192.0.2.10',
        'profile_dpi' => 203,
        'darkness' => 10,
        'speed' => 4,
        'media_type' => 'thermal_transfer',
        'media_tracking' => 'gap',
        'print_mode' => 'tear_off',
        'offset_x' => 0,
        'offset_y' => 0,
        'profile_is_active' => true,
    ], $overrides);
}

it('keeps search conditions inside the selected serial standard', function (): void {
    $ulSku = createSkuTemplateTestSku('UL', 'SHARED-UL');
    $emeaSku = createSkuTemplateTestSku('EMEA', 'SHARED-EMEA');
    $ulProfile = createSkuTemplateTestProfile($ulSku, 'Shared UL profile');
    $emeaProfile = createSkuTemplateTestProfile($emeaSku, 'Shared EMEA profile');

    $response = $this->get(route('admin.sku_template_configurations.index', [
        'q' => 'Shared',
        'sort' => 'sku',
        'serial_standard' => 'EMEA',
    ]));

    $response->assertOk()
        ->assertViewIs('admin.sku_template_configurations.index')
        ->assertViewHas('search', 'Shared')
        ->assertViewHas('sort', 'sku')
        ->assertViewHas('serialStandard', 'EMEA')
        ->assertViewHas('configs', function ($configs) use ($emeaProfile, $ulProfile): bool {
            return $configs->pluck('id')->contains($emeaProfile->id)
                && ! $configs->pluck('id')->contains($ulProfile->id);
        });
});

it('normalizes unsupported index filters to safe defaults', function (): void {
    $response = $this->get(route('admin.sku_template_configurations.index', [
        'sort' => 'unsupported',
        'serial_standard' => 'unknown',
    ]));

    $response->assertOk()
        ->assertViewHas('sort', 'sku')
        ->assertViewHas('serialStandard', 'ALL');
});

it('renders the create form for each supported standard', function (
    string $standard,
    string $scheme,
    string $view,
): void {
    $sku = createSkuTemplateTestSku($standard, "SKU-{$standard}");

    SkuSerialFormat::query()->create([
        'sku' => $sku->sku,
        'serial_standard' => $standard,
        'serial_scheme' => $scheme,
        'is_active' => true,
    ]);

    $this->get(route('admin.sku_template_configurations.create_by_standard', $standard))
        ->assertOk()
        ->assertViewIs($view)
        ->assertViewHas('forcedStandard', $standard)
        ->assertViewHas('activeStandard', $standard)
        ->assertViewHas('marketStandards', [$standard]);
})->with([
    'UL' => ['UL', 'ul_standard', 'admin.sku_template_configurations.create_ul'],
    'EMEA' => ['EMEA', 'emea_rating', 'admin.sku_template_configurations.create_emea'],
    'ANZ' => ['ANZ', 'anz_standard', 'admin.sku_template_configurations.create_anz'],
]);

it('builds the complete edit view contract', function (): void {
    $sku = createSkuTemplateTestSku('UL', 'SKU-EDIT-FORM');
    $template = createSkuTemplateTestTemplate($sku, 'Edit form template');
    $profile = createSkuTemplateTestProfile($sku, 'Edit form profile', $template);

    $this->get(route('admin.sku_template_configurations.edit', $profile))
        ->assertOk()
        ->assertViewIs('admin.sku_template_configurations.edit')
        ->assertViewHas('configuration', fn ($configuration): bool => $configuration->is($profile))
        ->assertViewHas('activeStandard', 'UL')
        ->assertViewHas('marketStandards', ['UL', 'EMEA', 'ANZ'])
        ->assertViewHas('selectedLabelType', 'serial')
        ->assertViewHas('selectedConnectionType', 'network')
        ->assertViewHas('formState');
});

it('creates the template and profile as one configuration', function (): void {
    $sku = createSkuTemplateTestSku('UL', 'SKU-CREATE');

    $response = $this->post(
        route('admin.sku_template_configurations.store'),
        skuTemplateTestPayload($sku),
    );

    $response->assertRedirectToRoute('admin.sku_template_configurations.index')
        ->assertSessionHas('success', 'Configuración de template + print profile creada correctamente.');

    $template = LabelTemplate::query()->sole();
    $profile = LabelPrintProfile::query()->sole();
    $version = LabelPrintProfileVersion::query()->sole();

    expect($profile->label_template_id)->toBe($template->id)
        ->and($profile->label_sku_id)->toBe($sku->id)
        ->and($profile->settings)->toBe([
            'connection_type' => 'network',
            'usb_required' => false,
        ])
        ->and($profile->created_by_user_id)->toBe($this->admin->id)
        ->and($template->serial_layout['text']['x'])->toBe(40)
        ->and($template->serial_layout['qr']['content_mode'])->toBe('serial_full')
        ->and($template->meta['serial_layout'])->toBe($template->serial_layout)
        ->and($template->zpl)->toContain('^XA', '{{serial_full_compact}}', '^XZ')
        ->and($template->created_by_user_id)->toBe($this->admin->id)
        ->and($version->label_print_profile_id)->toBe($profile->id)
        ->and($version->created_by_user_id)->toBe($this->admin->id);
});

it('updates an existing template without replacing its identity', function (): void {
    $sku = createSkuTemplateTestSku('UL', 'SKU-UPDATE');
    $template = createSkuTemplateTestTemplate($sku, 'Original template');
    $profile = createSkuTemplateTestProfile($sku, 'Original profile', $template);

    $response = $this->put(
        route('admin.sku_template_configurations.update', $profile),
        skuTemplateTestPayload($sku, [
            'template_name' => 'Updated template',
            'profile_name' => 'Original profile',
            'serial_position_x' => 88,
        ]),
    );

    $response->assertRedirectToRoute('admin.sku_template_configurations.index')
        ->assertSessionHas('success', 'Configuración actualizada correctamente.');

    expect(LabelTemplate::query()->count())->toBe(1)
        ->and($template->refresh()->name)->toBe('Updated template')
        ->and($template->version)->toBe(1)
        ->and($template->serial_layout['text']['x'])->toBe(88)
        ->and($profile->refresh()->name)->toBe('Original profile')
        ->and($profile->label_template_id)->toBe($template->id)
        ->and($profile->versions()->count())->toBe(1);
});

it('copies a shared template before editing it', function (): void {
    $sku = createSkuTemplateTestSku('UL', 'SKU-SHARED-TEMPLATE');
    $sharedTemplate = createSkuTemplateTestTemplate($sku, 'Shared template');
    $profile = createSkuTemplateTestProfile($sku, 'Profile to update', $sharedTemplate);
    $otherProfile = createSkuTemplateTestProfile($sku, 'Other profile', $sharedTemplate, false);

    $this->put(
        route('admin.sku_template_configurations.update', $profile),
        skuTemplateTestPayload($sku, [
            'template_name' => 'Private updated template',
            'profile_name' => 'Profile to update',
        ]),
    )->assertRedirectToRoute('admin.sku_template_configurations.index');

    $profile->refresh();
    $otherProfile->refresh();

    expect(LabelTemplate::query()->count())->toBe(2)
        ->and($profile->label_template_id)->not->toBe($sharedTemplate->id)
        ->and($profile->template->name)->toBe('Private updated template')
        ->and($otherProfile->label_template_id)->toBe($sharedTemplate->id)
        ->and($sharedTemplate->refresh()->name)->toBe('Shared template');
});

it('creates and links a template when an existing profile has none', function (): void {
    $sku = createSkuTemplateTestSku('UL', 'SKU-WITHOUT-TEMPLATE');
    $profile = createSkuTemplateTestProfile($sku, 'Profile without template');

    $this->put(
        route('admin.sku_template_configurations.update', $profile),
        skuTemplateTestPayload($sku, ['profile_name' => 'Profile with template']),
    )->assertRedirectToRoute('admin.sku_template_configurations.index');

    $profile->refresh();

    expect(LabelTemplate::query()->count())->toBe(1)
        ->and($profile->label_template_id)->not->toBeNull()
        ->and($profile->template->label_sku_id)->toBe($sku->id)
        ->and($profile->name)->toBe('Profile with template');
});

it('toggles the operational profile atomically without changing template fallback state', function (): void {
    $sku = createSkuTemplateTestSku('UL', 'SKU-TOGGLE');
    $selectedTemplate = createSkuTemplateTestTemplate($sku, 'Selected template', 1, false);
    $siblingTemplate = createSkuTemplateTestTemplate($sku, 'Sibling template', 2, true);
    $selectedProfile = createSkuTemplateTestProfile($sku, 'Selected profile', $selectedTemplate, false);
    $siblingProfile = createSkuTemplateTestProfile($sku, 'Sibling profile', $siblingTemplate, true);

    $this->post(route('admin.sku_template_configurations.toggle', $selectedProfile))
        ->assertRedirectToRoute('admin.sku_template_configurations.index')
        ->assertSessionHas('success', 'Estado de la configuración actualizado.');

    expect($selectedProfile->refresh()->is_active)->toBeTrue()
        ->and($siblingProfile->refresh()->is_active)->toBeFalse()
        ->and($selectedTemplate->refresh()->is_active)->toBeFalse()
        ->and($siblingTemplate->refresh()->is_active)->toBeTrue()
        ->and($selectedProfile->versions()->count())->toBe(1);

    $this->post(route('admin.sku_template_configurations.toggle', $selectedProfile))
        ->assertRedirectToRoute('admin.sku_template_configurations.index');

    expect($selectedProfile->refresh()->is_active)->toBeFalse()
        ->and($selectedProfile->versions()->count())->toBe(1)
        ->and($selectedTemplate->refresh()->is_active)->toBeFalse();
});

it('reloads the profile before applying a toggle', function (): void {
    $actualSku = createSkuTemplateTestSku('UL', 'SKU-CURRENT-SCOPE');
    $staleSku = createSkuTemplateTestSku('UL', 'SKU-STALE-SCOPE');
    $target = createSkuTemplateTestProfile($actualSku, 'Target profile', null, false);
    $sibling = createSkuTemplateTestProfile($actualSku, 'Active sibling', null, true);
    $staleProfile = clone $target;
    $staleProfile->label_sku_id = $staleSku->id;

    app(LabelPrintProfileService::class)->toggleActive($staleProfile, $this->admin->id);

    expect($target->refresh()->is_active)->toBeTrue()
        ->and($sibling->refresh()->is_active)->toBeFalse();
});

it('validates duplicate profile names before starting the aggregate transaction', function (): void {
    $sku = createSkuTemplateTestSku('UL', 'SKU-DUPLICATE-NAME');
    $template = createSkuTemplateTestTemplate($sku, 'Existing template');
    createSkuTemplateTestProfile($sku, 'Existing profile', $template);

    $this->from(route('admin.sku_template_configurations.create'))
        ->post(
            route('admin.sku_template_configurations.store'),
            skuTemplateTestPayload($sku, ['profile_name' => ' Existing profile ']),
        )
        ->assertRedirect(route('admin.sku_template_configurations.create'))
        ->assertSessionHasErrors('profile_name');

    expect(LabelTemplate::query()->count())->toBe(1)
        ->and(LabelPrintProfile::query()->count())->toBe(1);
});

it('rolls back template creation when profile creation fails', function (): void {
    $sku = createSkuTemplateTestSku('UL', 'SKU-ROLLBACK');
    $existingTemplate = createSkuTemplateTestTemplate($sku, 'Existing template');
    createSkuTemplateTestProfile($sku, 'Duplicate profile', $existingTemplate);
    $service = app(SkuTemplateConfigurationService::class);

    expect(fn () => $service->create(
        skuTemplateTestPayload($sku, ['profile_name' => 'Duplicate profile']),
        $this->admin->id,
    ))->toThrow(QueryException::class);

    expect(LabelTemplate::query()->count())->toBe(1)
        ->and($existingTemplate->refresh()->is_active)->toBeTrue()
        ->and(LabelPrintProfile::query()->count())->toBe(1)
        ->and(LabelPrintProfileVersion::query()->count())->toBe(0);
});
