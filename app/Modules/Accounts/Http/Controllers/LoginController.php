<?php

namespace App\Modules\Accounts\Http\Controllers;

use App\Support\Registries\AuthMethodRegistry;
use App\Support\SafeRedirect;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginController
{
    public function show(Request $request, AuthMethodRegistry $methods): View
    {
        if ($request->query('next') !== null) {
            redirect()->setIntendedUrl(SafeRedirect::internalPath($request->query('next')));
        }

        return view('accounts.login', ['methods' => $methods->all()]);
    }
}
