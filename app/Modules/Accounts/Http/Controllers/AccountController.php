<?php

namespace App\Modules\Accounts\Http\Controllers;

use App\Models\User;
use App\Modules\Accounts\Actions\UpdateProfile;
use App\Support\Registries\DashboardSectionRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController
{
    public function show(Request $request, DashboardSectionRegistry $sections): View
    {
        /** @var User $user */
        $user = $request->user();

        $rendered = [];
        foreach ($sections->all() as $section) {
            $rendered[] = ['view' => $section->view, 'data' => ($section->data)($user)];
        }

        return view('accounts.dashboard', ['user' => $user, 'sections' => $rendered]);
    }

    public function updateProfile(Request $request, UpdateProfile $update): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:80']]);

        /** @var User $user */
        $user = $request->user();
        $update($user, trim($validated['name']));

        return back()->with('status', __('Gespeichert.'));
    }
}
