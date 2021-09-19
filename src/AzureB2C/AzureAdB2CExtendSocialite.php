<?php

namespace SocialiteProviders\AzureB2C;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AzureAdB2CExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('azuread_b2c', AzureAdB2CProvider::class);
    }
}
