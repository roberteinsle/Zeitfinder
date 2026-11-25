<?php

namespace App\Modules\Legal\Http\Controllers;

use App\Modules\Legal\Models\LegalSetting;
use Illuminate\View\View;

final class LegalController
{
    public function imprint(): View
    {
        $legal = LegalSetting::current();
        abort_unless($legal->hasOperator(), 404);

        return view('legal.imprint', ['legal' => $legal]);
    }

    public function privacy(): View
    {
        return view('legal.privacy', [
            'legal' => LegalSetting::current(),
            'months' => (int) config('zeitfinder.retention_months'),
        ]);
    }
}
