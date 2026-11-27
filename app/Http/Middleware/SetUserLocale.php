<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\SupportedLocale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetUserLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('sanctum') ?? $request->user('web');
        $locale = $user instanceof User && SupportedLocale::isSupported($user->locale)
            ? $user->locale
            : $this->localeFromAcceptLanguage($request->header('Accept-Language'));

        App::setLocale($locale);

        return $next($request);
    }

    private function localeFromAcceptLanguage(?string $header): string
    {
        $preferences = [];

        $languageRanges = array_slice(explode(',', substr($header ?? '', 0, 512)), 0, 20);

        foreach ($languageRanges as $position => $languageRange) {
            $parts = explode(';', trim($languageRange));
            $languageTag = trim($parts[0]);

            if (! preg_match('/\A([a-z]{2})(?:-[a-z0-9]{1,8})*\z/i', $languageTag, $matches)) {
                continue;
            }

            $locale = strtolower($matches[1]);

            if (! SupportedLocale::isSupported($locale)) {
                continue;
            }

            $quality = 1.0;

            foreach (array_slice($parts, 1) as $parameter) {
                $parameter = trim($parameter);

                if (str_starts_with(strtolower($parameter), 'q=')) {
                    $value = substr($parameter, 2);

                    if (! preg_match('/\A(?:0(?:\.\d{0,3})?|1(?:\.0{0,3})?)\z/', $value)) {
                        $quality = 0.0;
                    } else {
                        $quality = (float) $value;
                    }

                    break;
                }
            }

            if ($quality > 0) {
                $preferences[] = ['locale' => $locale, 'quality' => $quality, 'position' => $position];
            }
        }

        usort($preferences, fn (array $left, array $right): int => $right['quality'] <=> $left['quality']
            ?: $left['position'] <=> $right['position']);

        return $preferences[0]['locale'] ?? SupportedLocale::DEFAULT;
    }
}
