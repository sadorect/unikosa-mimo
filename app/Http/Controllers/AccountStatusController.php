<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class AccountStatusController extends Controller
{
    public function pending(Request $request)
    {
        $user = $request->user();

        if ($user->status === 'approved' || $user->hasRole(['super_admin', 'set_representative', 'chapter_head', 'content_moderator', 'finance_admin'])) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Account/Pending', [
            'status' => $user->status,
        ]);
    }
}
