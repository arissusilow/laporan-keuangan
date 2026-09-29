<?php

namespace App\Http\Controllers;

use App\Models\SlideConfig;
use Illuminate\Http\RedirectResponse;

class SlideAliasController extends Controller
{
    public function __invoke(string $alias): RedirectResponse
    {
        $config = SlideConfig::query()
            ->where('public_alias', $alias)
            ->where('enabled', true)
            ->firstOrFail();

        abort_if(blank($config->public_token), 404);

        $publicSlideUrl = rtrim((string) config('app.public_url'), '/')
            .route('slides.show', $config->public_token, false);

        return redirect()->away($publicSlideUrl);
    }
}
