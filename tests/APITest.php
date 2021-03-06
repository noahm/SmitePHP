<?php
/**
 * This program is copyright of Curse Inc.
 * View the LICENSE file distributed with the source code
 * for copyright information and available license.
 */
namespace Curse\Smite;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2015-05-22 at 02:19:40.
 */
class APITest extends \PHPUnit_Framework_TestCase
{
	use MockHttp;

	/**
	 * @var API
	 */
	protected $object;
	/**
	 * @var API same reference as object
	 */
	protected $api;

	private $devId = 12345;
	private $authKey = "testauthkey";

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp()
	{
		$this->object = new API($this->devId, $this->authKey);
		$this->api = &$this->object;
		$this->setUpMockData();
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
	 * @covers Smite\API::request
	 * @expectedException Smite\ApiException
	 */
	public function testInvalidServerResponses()
	{
		// api returns null for invalid server responses
		$this->getsData(200, 'not valid json data');
		$data = $this->object->request('/ping');
		$this->assertNull($data);

		$this->getsData(404, '{}');
		$this->object->request('/ping');
		// throws ApiException
	}

	/**
	 * @covers Smite\API::__call
	 * @covers Smite\API::request
	 */
	public function testCall() {
		$this->alwaysGetsData(200, '{"hello": "world"}');

		$data = $this->object->ping();
		$this->assertObjectHasAttribute('hello', $data);
		$this->assertEquals('world', $data->hello);
	}
}
