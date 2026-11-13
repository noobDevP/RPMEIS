<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

trait RendersFailedLogin
{
    /**
     * Render the login view directly with HTTP 401 instead of a 302 flash redirect.
     * Eliminates session-flash race conditions across concurrent requests and prevents
     * automated scanners (e.g., ZAP Path Traversal Check 5) from mistaking a 200 redirect
     * target without errors for a local-file inclusion match.
     */
    protected function failedLoginResponse(Request $request, string $view, string $message, int $status = 401): Response
    {
        $username = (string) $request->input('username', '');
        $safeInput = (preg_match('/^[a-zA-Z0-9_\-\.\@\s]+$/', $username) === 1 && !str_contains($username, '..'))
            ? ['username' => $username]
            : [];

        if ($request->hasSession()) {
            $request->session()->now('_old_input', $safeInput);
        }

        $errors = (new ViewErrorBag())->put('default', new MessageBag(['login' => $message]));

        return response()->view($view, ['errors' => $errors], $status);
    }
}
