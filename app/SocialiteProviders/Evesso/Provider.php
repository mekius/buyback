<?php

namespace App\SocialiteProviders\Evesso;

use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;
use GuzzleHttp\ClientInterface;

class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'EVESSO';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['characterAssetsRead', 'esi-universe.read_structures.v1'];

    /**
     * {@inheritdoc}
     */
    protected $parameters = [];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected $encodingType = PHP_QUERY_RFC3986;

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://login.eveonline.com/oauth/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://login.eveonline.com/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        $postKey = (version_compare(ClientInterface::VERSION, '6') === 1) ? 'form_params' : 'body';

        $grantType = 'authorization_code';
        if (strpos($code, '::')) {
            list($code, $grantType) = explode('::', $code);
        }

        $fields = $this->getTokenFields($code);
        $clientId = $fields['client_id'];
        $clientSecret = $fields['client_secret'];

        $fields['grant_type'] = $grantType;

        if ($grantType === 'refresh_token') {
            $fields['refresh_token'] = $code;
            unset($fields['code']);
        }

        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($clientId . ':' . $clientSecret)
            ],
            $postKey => $fields,
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://login.eveonline.com/oauth/verify', [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        $api_instance = new \ESI\Api\CharacterApi();
        $portraitResponse = $api_instance->getCharactersCharacterIdPortrait($user['CharacterID']);

        $avatar = null;
        if ($portraitResponse) {
            $avatar = $portraitResponse->getPx512x512();
        }

        $user = (new User())->setRaw($user)->map([
            'id'       => $user['CharacterID'],
            'nickname' => $user['CharacterName'],
            'name'     => $user['CharacterName'],
            'email'    => $user['CharacterOwnerHash'],
            'avatar'   => $avatar
        ]);

        return $user;
    }
}
