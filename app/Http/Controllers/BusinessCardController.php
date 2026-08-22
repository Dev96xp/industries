<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use Illuminate\Http\Response;
use Illuminate\View\View;

class BusinessCardController extends Controller
{
    private const NAME = 'Ricardo Ramirez';

    private const TITLE = 'Project Director';

    private const PHONE = '770-412-2535';

    public function show(): View
    {
        return view('businesscard', [
            'company' => CompanySetting::current(),
            'name' => self::NAME,
            'title' => self::TITLE,
            'phone' => self::PHONE,
            'phoneDigits' => $this->phoneDigits(),
        ]);
    }

    public function vcard(): Response
    {
        $company = CompanySetting::current();
        $phoneDigits = $this->phoneDigits();

        $lines = [
            'BEGIN:VCARD',
            'VERSION:3.0',
            'FN:'.self::NAME,
            'ORG:'.$company->company_name,
            'TITLE:'.self::TITLE,
            'TEL;TYPE=WORK,VOICE:+'.$phoneDigits,
            'URL:'.route('home'),
            'END:VCARD',
        ];

        $vcard = implode("\r\n", $lines)."\r\n";

        $filename = str_replace(' ', '-', self::NAME).'.vcf';

        return response($vcard, 200, [
            'Content-Type' => 'text/vcard; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function phoneDigits(): string
    {
        $digits = preg_replace('/\D/', '', self::PHONE);

        return str_starts_with($digits, '1') ? $digits : '1'.$digits;
    }
}
