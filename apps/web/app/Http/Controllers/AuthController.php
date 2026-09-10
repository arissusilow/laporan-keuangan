<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\AuditLog;
use App\Models\ReportSession;
use App\Services\ApplicationSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();
        AuditLog::record('LOGIN');

        if (Auth::user()->must_change_password) {
            return redirect()->intended(route('password.first'));
        }

        return redirect()->intended($this->landingUrl($request));
    }

    public function logout(Request $request): RedirectResponse
    {
        AuditLog::record('LOGOUT');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function firstPassword(): View
    {
        return view('auth.first-password');
    }

    public function updateFirstPassword(Request $request): RedirectResponse
    {
        $minimumLength = app(ApplicationSettings::class)->integer('password_min_length', 6);
        $data = $request->validate(['current_password' => ['required', 'current_password'], 'password' => ['required', 'confirmed', PasswordRule::min($minimumLength)->letters()->numbers()]]);
        $request->user()->update(['password' => Hash::make($data['password']), 'must_change_password' => false]);
        $request->session()->regenerate();
        AuditLog::record('PASSWORD_CHANGED');

        return redirect()->to($this->landingUrl($request))->with('success', 'Kata sandi berhasil diganti.');
    }

    private function landingUrl(Request $request): string
    {
        $reports = $request->user()->is_super_admin
            ? ReportSession::query()->where('status', 'ACTIVE')->limit(2)->get()
            : $request->user()->reports()->where('status', 'ACTIVE')->limit(2)->get();

        return $reports->count() === 1
            ? route('reports.dashboard', $reports->first())
            : route('reports.index');
    }
}
