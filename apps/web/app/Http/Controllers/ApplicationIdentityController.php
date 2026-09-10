<?php

namespace App\Http\Controllers;

use App\Services\ApplicationSettings;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationIdentityController extends Controller
{
    public function logo(ApplicationSettings $settings): StreamedResponse
    {
        $path = $settings->get('logo_path');
        abort_unless(is_string($path) && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
