<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class Controller
{
    /**
     * Route dashboard milik user yang sedang login.
     */
    protected function dashboardRouteFor(Request $request): string
    {
        return ($request->user()?->role ?? UserRole::Patient)->homeRoute();
    }

    protected function backWithSuccess(string $route, string $message, array $params = []): RedirectResponse
    {
        return redirect()->route($route, $params)->with('success', $message);
    }
}
