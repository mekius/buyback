<?php

namespace App\Http\Controllers\Account;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as ProviderUser;
use App\User;
use App\Models\SocialUser;

/**
 * Class SocialLoginController
 * @package App\Http\Controllers\Auth
 */
class LoginController extends Controller
{
    /**
     * Allowed providers
     *
     * @var array
     */
    protected $allowedProviders = ['evesso'];

    /**
     * @param Request $request
     * @param $provider
     * @return \Illuminate\Http\RedirectResponse|mixed
     */
    public function login(Request $request, $provider)
    {
        //If the provider is not an acceptable third party than kick back
        if (! in_array($provider, $this->allowedProviders)) {
            return redirect()->route('home');
        }

        /**
         * The first time this is hit, request is empty
         * It's redirected to the provider and then back here, where request is populated
         * So it then continues creating the user
         */
        if (! $request->all()) {
            return $this->getAuthorizationFirst($provider);
        }

        // Get user
        $socialUser = $this->getSocialUser($provider);

        $user = $this->createOrGetUser($socialUser, $provider);

        // Login the user so that the parts of the site requiring authentication will work
        auth()->login($user);

        info(var_export($user, true));

        // Set access token on the ESI library
        \ESI\Configuration::getDefaultConfiguration()->setAccessToken($user->token);

        return redirect()->intended(route('home'));
    }

    public function logout(Request $request)
    {
        auth()->logout();

        return redirect()->route('home');
    }

    /**
     * @param  $provider
     * @return mixed
     */
    private function getAuthorizationFirst($provider)
    {
        $socialite = Socialite::driver($provider);
        $scopes = count(config("services.{$provider}.scopes")) ? config("services.{$provider}.scopes") : false;
        $with = count(config("services.{$provider}.with")) ? config("services.{$provider}.with") : false;
        $fields = count(config("services.{$provider}.fields")) ? config("services.{$provider}.fields") : false;

        if ($scopes)
            $socialite->scopes($scopes);

        if ($with)
            $socialite->with($with);

        if ($fields)
            $socialite->fields($fields);

        return $socialite->redirect();
    }

    /**
     * @param $provider
     * @return mixed
     */
    private function getSocialUser($provider)
    {
        return Socialite::driver($provider)->user();
    }

    /**
     * Create user or load existing one
     *
     * @param ProviderUser $providerUser
     * @param $provider
     * @return mixed
     */
    public function createOrGetUser(ProviderUser $providerUser, $provider)
    {
        $socialUser = SocialUser::whereProvider($provider)
            ->whereProviderUserId($providerUser->getId())
            ->first();

        if ($socialUser) {
            $socialUser->token = $providerUser->token;
            $socialUser->refresh_token = $providerUser->refreshToken;
            $socialUser->expiration = date("Y-m-d H:i:s", (int)$providerUser->expiresIn + time());
            $socialUser->save();

            return $socialUser->user;
        } else {
            $socialUser = new SocialUser([
                'provider_user_id' => $providerUser->getId(),
                'provider' => $provider,
                'token' => $providerUser->token,
                'refresh_token' => $providerUser->refreshToken,
                'expiration' => date("Y-m-d H:i:s", (int)$providerUser->expiresIn + time())
            ]);

            $user = User::whereEmail($providerUser->getEmail())->first();

            if (!$user) {
                $user = User::create([
                    'email' => $providerUser->getEmail(),
                    'name' => $providerUser->getName(),
                    'avatar' => $providerUser->getAvatar()
                ]);
            }

            $socialUser->user()->associate($user);
            $socialUser->save();

            return $user;
        }

    }
}