<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = (string) config('admin.user');
        $password = (string) config('admin.password');

        if ($user === '' || $password === '') {
            return $this->reject('El servidor no tiene configurado el acceso de administrador.');
        }

        [$sent_user, $sent_password] = $this->credentials($request);

        // hash_equals para evitar ataques de timing
        $user_ok = hash_equals($user, $sent_user);
        $password_ok = hash_equals($password, $sent_password);

        if (! $user_ok || ! $password_ok) {
            return $this->reject('Usuario o contraseña incorrectos.');
        }

        return $next($request);
    }

    private function credentials(Request $request): array
    {
        // con php-fpm no siempre llega PHP_AUTH_USER, así que se lee la cabecera
        $header = (string) $request->header('Authorization');

        if (str_starts_with($header, 'Basic ')) {
            $decoded = base64_decode(substr($header, 6), true);

            if (is_string($decoded) && str_contains($decoded, ':')) {
                return explode(':', $decoded, 2);
            }
        }

        return [(string) $request->getUser(), (string) $request->getPassword()];
    }

    private function reject(string $message): Response
    {
        return response()->json(['message' => $message], 401);
    }
}
