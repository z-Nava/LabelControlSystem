<?php

use App\Http\Requests\Admin\StoreSkuTemplateConfigurationRequest;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;

function serialTemplateValidator(): Factory
{
    return new Factory(new Translator(new ArrayLoader(), 'en'));
}

it('requires physical dimensions for active serial and rating templates', function () {
    $request = StoreSkuTemplateConfigurationRequest::create('/', 'POST', [
        'template_is_active' => true,
    ]);
    $rules = $request->rules();
    $validator = serialTemplateValidator()->make(
        ['template_is_active' => true],
        [
            'template_is_active' => $rules['template_is_active'],
            'template_width_mm' => $rules['template_width_mm'],
            'template_height_mm' => $rules['template_height_mm'],
        ],
    );

    expect($validator->errors())
        ->toHaveKey('template_width_mm')
        ->toHaveKey('template_height_mm');
});

it('allows incomplete physical dimensions only for inactive legacy templates', function () {
    $request = StoreSkuTemplateConfigurationRequest::create('/', 'POST', [
        'template_is_active' => false,
    ]);
    $rules = $request->rules();
    $validator = serialTemplateValidator()->make(
        ['template_is_active' => false],
        [
            'template_is_active' => $rules['template_is_active'],
            'template_width_mm' => $rules['template_width_mm'],
            'template_height_mm' => $rules['template_height_mm'],
        ],
    );

    expect($validator->passes())->toBeTrue();
});
