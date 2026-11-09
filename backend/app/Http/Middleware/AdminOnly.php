<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    private const MAX_ATTEMPTS = 5;

    private const LOCK_SECONDS = 900;

    public function handle(Request $request, Closure $next): Response
    {
        $user = (string) config('admin.user');
        $password = (string) config('admin.password');

        if ($user === '' || $password === '') {
            return $this->reject('El servidor no tiene configurado el acceso de administrador.');
        }

        $key = 'admin-login:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return $this->locked(RateLimiter::availableIn($key));
        }

        [$sent_user, $sent_password] = $this->credentials($request);

        $user_ok = hash_equals($user, $sent_user);
        $password_ok = hash_equals($password, $sent_password);

        if (! $user_ok || ! $password_ok) {
            if ($sent_user !== '' || $sent_password !== '') {
                RateLimiter::hit($key, self::LOCK_SECONDS);
            }

            return $this->reject('Usuario o contraseña incorrectos.');
        }

        if (RateLimiter::attempts($key) > 0) {
            RateLimiter::clear($key);
        }

        return $next($request);
    }

    private function credentials(Request $request): array
    {
        $header = (string) $request->header('Authorization');

        if (str_starts_with($header, 'Basic ')) {
            $decoded = base64_decode(substr($header, 6), true);

            if (is_string($decoded) && str_contains($decoded, ':')) {
                return explode(':', $decoded, 2);
            }
        }

        return [(string) $request->getUser(), (string) $request->getPassword()];
    }

    private function locked(int $seconds): Response
    {
        $minutes = max(1, (int) ceil($seconds / 60));

        return response()
            ->json(['message' => "Demasiados intentos. Prueba de nuevo en {$minutes} min."], 429)
            ->header('Retry-After', (string) $seconds);
    }

    private function reject(string $message): Response
    {
        return response()->json(['message' => $message], 401);
    }
}
