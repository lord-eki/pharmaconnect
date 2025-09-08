<?php

namespace App\Http\Responses;

use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Illuminate\Http\RedirectResponse;

class CustomLoginResponse implements LoginResponse
{
    public function toResponse($request): RedirectResponse
    {
        $user = auth()->user();
        
        // Get the redirect URL based on user role
        $redirectUrl = $this->getRedirectUrl($user);
        
        // Clear any existing intended URL and redirect directly
        session()->forget('url.intended');
        
        return redirect()->to($redirectUrl);
    }
    
    private function getRedirectUrl($user): string
    {
        if ($user->hasRole('Admin')) {
            return '/admin';
        } elseif ($user->hasRole('Supplier')) {
            return '/supplier';
        } elseif ($user->hasRole('Insurer')) {
            return '/insurer';
        } elseif ($user->hasRole('Operation')) {
            return '/operation';
        } elseif ($user->hasRole('Physician')) {
            return '/physician';
        }

        return '/'; // fallback
    }
}