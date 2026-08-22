<?php

use App\Models\CompanySetting;

test('the business card page loads publicly with the contact details', function () {
    $company = CompanySetting::current();

    $response = $this->get('/businesscard');

    $response->assertStatus(200);
    $response->assertSee('Ricardo Ramirez');
    $response->assertSee('Director de Proyectos');
    $response->assertSee($company->company_name);
    $response->assertSee('770-412-2535');
});

test('the vcard download has the correct headers and fields', function () {
    $company = CompanySetting::current();

    $response = $this->get('/businesscard/vcard');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/vcard; charset=utf-8');
    $response->assertHeader('Content-Disposition', 'attachment; filename="Ricardo-Ramirez.vcf"');

    $content = $response->getContent();

    expect($content)
        ->toContain('BEGIN:VCARD')
        ->toContain('FN:Ricardo Ramirez')
        ->toContain('ORG:'.$company->company_name)
        ->toContain('TITLE:Director de Proyectos')
        ->toContain('TEL;TYPE=WORK,VOICE:+17704122535')
        ->toContain('END:VCARD');
});
