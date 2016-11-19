<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Arr;
use App\Models\SocialUser;
use Laravel\Socialite\Two\ProviderInterface;

class RefreshEveToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        info("Initializing EVE API");

        if (!Auth::guest()) {
            info("User is authenticated");

            $user =  Auth::user();

            $socialUsers = $user['relations']['providers'];


            info(var_export($socialUsers, true));
            /** @var $user \App\Models\SocialUser */
            foreach($socialUsers as $socialUser) {
                info("Validating Provider {$socialUser->provider}");

                if (time() >= strtotime($socialUser->expiration)) {
                    /** @var $provider \SocialiteProviders\Manager\OAuth2\AbstractProvider */
                    $provider = Socialite::with($socialUser->provider);

                    if (!$provider instanceof \SocialiteProviders\Manager\OAuth2\AbstractProvider) {
                        continue;
                    }

                    $response = $provider->getAccessTokenResponse($socialUser->refresh_token . '::' . 'refresh_token');

                    $socialUser->token = $response['access_token'];
                    $socialUser->expiration = date('Y-m-d H:i:s', time() + $response['expires_in']);
                    $socialUser->save();
                }

                if ($socialUser->provider === 'evesso') {
                    \ESI\Configuration::getDefaultConfiguration()->setAccessToken($socialUser->token);
                }
            }
        }

        return $next($request);
    }
}
