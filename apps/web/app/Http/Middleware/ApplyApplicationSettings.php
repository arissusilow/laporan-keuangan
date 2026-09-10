<?php

namespace App\Http\Middleware;

use App\Services\ApplicationSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class ApplyApplicationSettings
{
    public function handle(Request $request, Closure $next): Response
    {
        $settings = app(ApplicationSettings::class)->all();
        App::setLocale((string) $settings['locale']);
        config([
            'session.lifetime' => (int) $settings['session_lifetime'],
        ]);

        return $next($request);
    }
}
