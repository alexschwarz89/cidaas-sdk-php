<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/AbstractCidaasTestParent.php';

use Cidaas\OAuth2\Client\Provider\AbstractCidaasTestParent;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;

final class LoginWithCredentialsTest extends AbstractCidaasTestParent {
    private static $loginUnsuccessfulWithUnkownUserResponse = '{"success":false,"status":417,"error":{"error":"invalid_username_password","username":"user@widas.de","username_type":"email","error_description":"Given username or password is invalid","view_type":"login","requestId":"bb3b6908-64e4-4ccf-891e-908fc9dd1236","suggested_url":"https://nightlybuild.cidaas.de/user-ui/login?groupname=default&lang=&view_type=login&error=invalid_username_password&username=user%40widas.de&username_type=email&error_description=Given%20username%20or%20password%20is%20invalid&requestId=bb3b6908-64e4-4ccf-891e-908fc9dd1236"}}';

    private static function loginUnsuccessfulWithKnownUserResponse() {
        return '{"success":false,"status":417,"error":{"error":"invalid_username_password","username":"user@widas.de","username_type":"email","view_type":"login","sub":"' . self::$SUB . '","q":"dddb62b2-7e2a-4143-a492-d5818802cdac","requestId":"cfc1d841-4dab-4ffa-ab6f-fc2662a9b206","suggested_url":"https://nightlybuild.cidaas.de/user-ui/login?groupname=default&lang=&view_type=login&error=invalid_username_password&username=user%40widas.de&username_type=email&sub=dddb62b2-7e2a-4143-a492-d5818802cdac&q=dddb62b2-7e2a-4143-a492-d5818802cdac&requestId=cfc1d841-4dab-4ffa-ab6f-fc2662a9b206"}}';
    }

    private $responsePromise;

    protected function setUp(): void {
        $this->setUpCidaas();

        $this->responsePromise = $this->provider->getRequestId();
    }

    public function test_loginWithCredentials_withValidCredentialsGiven_serverCalledWithClientIdAndSecret() {
        $this->mock->append(new Response(200, [], self::loginSuccessfulResponse()));

        $this->responsePromise->then(function ($requestId) {
            return $this->provider->loginWithCredentials($_ENV['USERNAME'], $_ENV['USERNAME_TYPE'], $_ENV['PASSWORD'], $requestId);
        })->wait();

        $request = $this->mock->getLastRequest();
        $body = json_decode($request->getBody(), true);

        self::assertEquals($_ENV['USERNAME'], $body['username']);
        self::assertEquals($_ENV['PASSWORD'], $body['password']);
        self::assertEquals($_ENV['USERNAME_TYPE'], $body['username_type']);
        self::assertEquals(self::$REQUEST_ID, $body['requestId']);
    }

    public function test_loginWithCredentials_withValidCredentialsGiven_returnsLoginSuccessfulFromServer() {
        $this->mock->append(new Response(200, [], self::loginSuccessfulResponse()));

        $promise = $this->responsePromise->then(function ($requestId) {
            return $this->provider->loginWithCredentials($_ENV['USERNAME'], $_ENV['USERNAME_TYPE'], $_ENV['PASSWORD'], $requestId);
        });

        $response = $promise->wait();
        self::assertTrue($response['success']);
        self::assertEquals(200, $response['status']);
        self::assertEquals(self::$CODE, $response['data']['code']);
    }

    public function test_loginWithCredentials_withInvalidCredentialsAndKnownUsernameGiven_returnsLoginUnsuccessfulFromServer() {
        $this->mock->append(new Response(417, [], self::loginUnsuccessfulWithKnownUserResponse()));

        $promise = $this->responsePromise->then(function ($requestId) {
            return $this->provider->loginWithCredentials($_ENV['USERNAME'], $_ENV['USERNAME_TYPE'], $_ENV['PASSWORD'], $requestId);
        });

        try {
            $promise->wait();
            self::fail('Promise should return exception');
        } catch (ClientException $exception) {
            self::assertEquals(417, $exception->getCode());
            $response = json_decode($exception->getResponse()->getBody(), true);
            self::assertFalse($response['success']);
            self::assertEquals(417, $response['status']);
            self::assertArrayNotHasKey('data', $response);
            self::assertEquals(self::$SUB, $response['error']['sub']);
            self::assertEquals('invalid_username_password', $response['error']['error']);
        }
    }

    public function test_loginWithCredentials_withInvalidCredentialsAndUnknownUsernameGiven_returnsLoginUnsuccessfulFromServer() {
        $this->mock->append(new Response(417, [], self::$loginUnsuccessfulWithUnkownUserResponse));

        $promise = $this->responsePromise->then(function ($requestId) {
            return $this->provider->loginWithCredentials($_ENV['USERNAME'], $_ENV['USERNAME_TYPE'], $_ENV['PASSWORD'], $requestId);
        });

        try {
            $promise->wait();
            self::fail('Promise should return exception');
        } catch (ClientException $exception) {
            self::assertEquals(417, $exception->getCode());
            $response = json_decode($exception->getResponse()->getBody(), true);
            self::assertFalse($response['success']);
            self::assertEquals(417, $response['status']);
            self::assertArrayNotHasKey('data', $response);
            self::assertEquals('invalid_username_password', $response['error']['error']);
            self::assertArrayNotHasKey('login', $response['error']);
        }
    }
}
