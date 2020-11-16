<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/AbstractCidaasTestParent.php';

use Cidaas\OAuth2\Client\Provider\AbstractCidaasTestParent;

final class GetRequestIdTest extends AbstractCidaasTestParent {

    protected function setUp(): void {
        $this->setUpCidaas();
    }

    public function test_getRequestId_withClientIdAndSecretSet_serverCalledWithClientIdSecretAndDefaultScope() {
        $this->provider->getRequestId()->wait();

        $request = $this->mock->getLastRequest();
        self::assertEquals($_ENV['CIDAAS_BASE_URL'] . '/authz-srv/authrequest/authz/generate', $request->getUri());
        $body = json_decode($request->getBody(), true);
        self::assertEquals($_ENV['CIDAAS_CLIENT_ID'], $body['client_id']);
        self::assertEquals($_ENV['CIDAAS_REDIRECT_URI'], $body['redirect_uri']);
        self::assertEquals('code', $body['response_type']);
        self::assertEquals('openid', $body['scope']);
        self::assertNotNull($body['nonce']);
    }

    public function test_getRequestId_withClientIdAndSecretSetAndScopeGiven_serverCalledWithClientIdSecretAndScope() {
        $scope = 'openid profile';
        $this->provider->getRequestId($scope)->wait();

        $request = $this->mock->getLastRequest();
        self::assertEquals($_ENV['CIDAAS_BASE_URL'] . '/authz-srv/authrequest/authz/generate', $request->getUri());
        $body = json_decode($request->getBody(), true);
        self::assertEquals($_ENV['CIDAAS_CLIENT_ID'], $body['client_id']);
        self::assertEquals($_ENV['CIDAAS_REDIRECT_URI'], $body['redirect_uri']);
        self::assertEquals('code', $body['response_type']);
        self::assertEquals($scope, $body['scope']);
        self::assertNotNull($body['nonce']);
    }

    public function test_getRequestId_withClientIdAndSecretSet_returnsRequestIdFromServer() {
        $requestId = $this->provider->getRequestId()->wait();

        self::assertEquals(self::$REQUEST_ID, $requestId);
    }
}
