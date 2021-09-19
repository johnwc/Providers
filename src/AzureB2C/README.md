# Azure

```bash
composer require socialiteproviders/azureb2c
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'azuread_b2c' => [
    'pwreset_policy' => env('AZUREB2C_PWRESET_POLICY'),
    'policy' => env('AZUREB2C_POLICY'),
    'tenant' => env('AZUREB2C_TENANT'),
    'tenant_id' => env('AZUREB2C_TENANT_ID'),
    'client_id' => env('AZUREB2C_CLIENT_ID'),
    'client_secret' => env('AZUREB2C_CLIENT_SECRET'),
    'redirect' => env('AZUREB2C_REDIRECT_URI')
],
```
 * AZUREB2C_TENANT: Name given to B2C tenant, name before .onmicrosoft.com.
 * AZUREB2C_TENANT_ID: B2C tenant ID, found in app registration.
 * AZUREB2C_CLIENT_ID: Client ID, create/found in app registration.
 * AZUREB2C_CLIENT_SECRET: Client secret created for registered app.
 * AZUREB2C_REDIRECT_URI: Your site's url to redirect to after login.(MUST be given as url in 'Authentication' tab for registered app.)
 * AZUREB2C_POLICY: User Flow policy for normal login.
 * AZUREB2C_PWRESET_POLICY: User Flow policy for password resets.


### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\AzureB2C\AzureAdB2CExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('azuread_b2c')->redirect();
```

### Returned User fields

- ``id``
- ``name``
- ``givenName``
- ``surname``
- ``email``
