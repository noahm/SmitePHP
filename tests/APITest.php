<?php
namespace Smite;

use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Mock;
use GuzzleHttp\Ring\Client\MockHandler;
use GuzzleHttp\Subscriber\History;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2015-05-22 at 02:19:40.
 */
class APITest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var API
	 */
	protected $object;

	private $devId = 12345;
	private $authKey = "testauthkey";

	/**
	 * @var History
	 */
	private $requestHistory;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp()
	{
		$this->object = new API($this->devId, $this->authKey);

		// track requests made
		$this->requestHistory = new History;
		$this->object->getGuzzleClient()->getEmitter()->attach($this->requestHistory);

		// create mock data container
		$this->mockData = new Mock([]);
		$this->object->getGuzzleClient()->getEmitter()->attach($this->mockData);
	}

	/**
	 * Replaces API object with one having a custom guzzle instance that
	 * always returns the given data for every request made
	 * @param int    $code Return code to be returned by mock api
	 * @param string $body Http body to be returned by mock api
	 */
	private function alwaysGetsData($code, $body) {
		// dump existing mock data
		unset($this->mockData);

		$mock = new MockHandler([
			'status' => $code,
			'body' => $body,
		]);
		$guzzle = new Client(['handler' => $mock]);
		if ($this->requestHistory) {
			$guzzle->getEmitter()->attach($this->requestHistory);
		}
		$this->object = new API($this->devId, $this->authKey, $guzzle);
	}

	/**
	 * Queues a mock response to be returned by guzzle
	 * @param int    $code Return code to be returned by mock api
	 * @param string $body Http body to be returned by mock api
	 */
	private function getsData($code, $body) {
		if (!$this->mockData) {
			throw new Exception('Can\'t have individual mocks when data is set to always return');
		}
		$this->mockData->addResponse(
			new Response($code, [], Stream::factory($body))
		);
	}

	/**
	 * @covers Smite\API::getDevId
	 */
	public function testGetDevId()
	{
		$this->assertEquals($this->object->getDevId(), $this->devId);
	}

	/**
	 * @covers Smite\API::getAuthKey
	 */
	public function testGetAuthKey()
	{
		$this->assertEquals($this->object->getAuthKey(), $this->authKey);
	}

	/**
	 * @covers Smite\API::getGuzzleClient
	 */
	public function testGetGuzzleClient()
	{
		$this->assertInstanceOf('GuzzleHttp\Client', $this->object->getGuzzleClient());
	}

	/**
	 * @covers Smite\API::preferredFormat
	 */
	public function testPreferFormat()
	{
		$this->alwaysGetsData(200, '{"hello": "world"}');

		// test returning object by default
		$data = $this->object->request('ping');
		$this->assertInstanceOf('stdClass', $data);
		$this->assertObjectHasAttribute('hello', $data);
		$this->assertEquals('world', $data->hello);

		// test returning array
		$this->object->preferredFormat('array');
		$data = $this->object->request('ping');
		$this->assertInternalType('array', $data);
		$this->assertArrayHasKey('hello', $data);
		$this->assertEquals('world', $data['hello']);

		// test returning object explicitly
		$this->object->preferredFormat('object');
		$data = $this->object->request('ping');
		$this->assertInstanceOf('stdClass', $data);
		$this->assertObjectHasAttribute('hello', $data);
		$this->assertEquals('world', $data->hello);
	}

	/**
	 * @covers Smite\API::preferredLanguage
	 */
	public function testUseLanguage()
	{
		// pre-seed session data
		$this->getsData(200, '{"session_id": "abcd"}');

		$this->getsData(200, '{}');
		// test english as default (lang code 1)
		$this->object->request('getgods');
		$this->assertStringEndsWith('/1',
			$this->requestHistory->getLastRequest()->getUrl());

		$this->getsData(200, '{}');
		// try russian (lang code 11)
		$this->object->preferredLanguage('ru');
		$this->object->request('getgods');
		$this->assertStringEndsWith('/11',
			$this->requestHistory->getLastRequest()->getUrl());

		$this->getsData(200, '{}');
		// try latin american spanish (lang code 9)
		$this->object->preferredLanguage('es-419');
		$this->object->request('getgods');
		$this->assertStringEndsWith('/9',
			$this->requestHistory->getLastRequest()->getUrl());
	}

	/**
	 * @covers Smite\API::preferredLanguage
	 * @expectedException InvalidArgumentException
	 */
	public function testUseInvalidLanguage()
	{
		// try an invalid language code
		$this->object->preferredLanguage('abc');
	}

	/**
	 * @covers Smite\API::sessionRequiredFor
	 * @todo   Move to RequestTest
	 */
	public function testSessionRequiredFor()
	{
		$this->markTestIncomplete('This test should be moved to one covering the Request class');

		// should not require a session for these
		$this->assertFalse($this->object->sessionRequiredFor('ping'));
		$this->assertFalse($this->object->sessionRequiredFor('createsession'));
		// should require a session for anyting else
		$this->assertTrue($this->object->sessionRequiredFor('testsession'));
		$this->assertTrue($this->object->sessionRequiredFor('getplayer'));
	}

	/**
	 * @covers Smite\API::request
	 */
	public function testInvalidServerResponses()
	{
		// api returns null for invalid server responses
		$this->getsData(200, 'not valid json data');
		$data = $this->object->request('/ping');
		$this->assertNull($data);

		$this->getsData(404, '{}');
		$data = $this->object->request('/ping');
		$this->assertNull($data);

		$this->getsData(500, '{}');
		$data = $this->object->request('/ping');
		$this->assertNull($data);
	}

	/**
	 * @covers Smite\API::request
	 * @todo   Implement testSessionCreation().
	 */
	public function testSessionCreation() {
		// api should retrieve new session key from createsession endpoint
		// before issuing other requests

		// api should refresh session key after it expires (15 minutes)
	}

	/**
	 * @covers Smite\API::applyMaps
	 * @todo   Implement testApplyMaps().
	 */
	public function testApplyMaps() {
		// should convert queue types to integers for applicable calls

		// should convert ranking tiers to integers for applicable calls
	}
}
