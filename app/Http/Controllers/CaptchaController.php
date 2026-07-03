<?php

namespace App\Http\Controllers;

use App\Services\CaptchaService;
use Illuminate\Http\Request;

class CaptchaController extends Controller
{
    public function generate(Request $request, CaptchaService $captcha)
    {
        $form = (string) $request->query('form', '');

        if (! $form || ! $captcha->appliesTo($form)) {
            return response()->json(['enabled' => false]);
        }

        return response()->json(['enabled' => true, ...$captcha->generate()]);
    }
}
