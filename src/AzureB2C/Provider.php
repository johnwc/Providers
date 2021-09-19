<?php

namespace SocialiteProviders\AzureB2C;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;
use Jose\Component\Signature\Serializer\JWSSerializerManager;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Checker\HeaderCheckerManager;
use Jose\Component\Checker\AlgorithmChecker;
use Jose\Component\Signature\JWSTokenSupport;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker;
use Illuminate\Support\Arr;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'AZUREAD_B2C';

    private $metadata = [];

    // Given a B2C policy name, constructs the metadata endpoint
	// and fetches the metadata from that endpoint
	private function getMetadata($policy_name) {

        if (isset($this->metadata[$policy_name]) === false) {
            $metadata_endpoint = "https://{$this->config['tenant']}.b2clogin.com/{$this->config['tenant_id']}/$policy_name/v2.0/.well-known/openid-configuration";
            $response = Http::get($metadata_endpoint);
            $this->metadata[$policy_name] = $response->json();
        }

        return $this->metadata[$policy_name];
	}

    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        'openid',
        'offline_access',
    ];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        $this->request->session()->put('url.intended', URL::previous());
        $meta = $this->getMetadata($this->config['policy']);
        return
            $this->buildAuthUrlFromBase(
                $meta["authorization_endpoint"],
                $state
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        $meta = $this->getMetadata($this->config['policy']);
        return $meta["token_endpoint"];
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $meta = $this->getMetadata($this->config['policy']);
        $serializerManager = new JWSSerializerManager([
            new CompactSerializer(),
        ]);
        $jws = $serializerManager->unserialize($token);
        $headerCheckerManager = new HeaderCheckerManager(
            [
                new AlgorithmChecker(['RS256']),
            ],
            [
                new JWSTokenSupport(),
            ]
        );
        $headerCheckerManager->check($jws, 0);
        $claimCheckerManager = new ClaimCheckerManager(
            [
                new Checker\IssuerChecker([$meta["issuer"]]),
                new Checker\IssuedAtChecker(),
                new Checker\NotBeforeChecker(),
                new Checker\ExpirationTimeChecker(),
                new Checker\AudienceChecker($this->config['client_id']),
            ]
        );

        $claims = json_decode($jws->getPayload(), true);
        $claimCheckerManager->check($claims);

        return $claims;
    }

    /**
     * {@inheritdoc}
     */
    protected function parseAccessToken($body)
    {
        return Arr::get($body, 'id_token');
    }

    /**
     * {@inheritdoc}
     */
    protected function parseExpiresIn($body)
    {
        return Arr::get($body, 'id_token_expires_in');
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'        => $user['oid'],
            'name'      => "{$user['given_name']} {$user['family_name']}",
            'givenName' => $user['given_name'],
            'surname'   => $user['family_name'],
            'email'     => $user['emails'][0],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }

    /**
     * Redirect the user to the authentication page for the provider.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function logout()
    {
        $meta = $this->getMetadata($this->config['policy']);
        return new RedirectResponse($meta["end_session_endpoint"].'?'.http_build_query(['post_logout_redirect_uri'=> url('/logout/success')], '', '&', $this->encodingType));
    }

    /**
     * Redirect the user to the authentication page for the provider.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function resetPassword($state)
    {
        $fields = [
            'client_id' => $this->clientId,
            'nonce' => 'defaultNonce',
            'redirect_uri' => $this->redirectUrl,
            'scope' => $this->formatScopes($this->getScopes(), $this->scopeSeparator),
            'response_type' => 'id_token',
            'prompt' => 'login',
        ];

        if ($this->usesState()) {
            $fields['state'] = $state;
        }

        $meta = $this->getMetadata($this->config['pwreset_policy']);
        $url = $meta["authorization_endpoint"].'?'.http_build_query($fields, '', '&', $this->encodingType);
        return new RedirectResponse($url);
    }

    /**
     * Add the additional configuration keys to enable the branded sign-in experience.
     *
     * @return array
     */
    public static function additionalConfigKeys()
    {
        return [
            'tenant',
            'tenant_id',
            'policy',
            'pwreset_policy'
        ];
    }
}
