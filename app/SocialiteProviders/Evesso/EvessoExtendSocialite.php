<?php

namespace App\SocialiteProviders\Evesso;

use SocialiteProviders\Manager\SocialiteWasCalled;

class EvessoExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('evesso', __NAMESPACE__.'\Provider');
    }
}
