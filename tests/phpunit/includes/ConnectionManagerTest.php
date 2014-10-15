<?php

namespace SMW\Tests;

use SMW\Tests\Util\UtilityFactory;

use SMW\ConnectionManager;

/**
 * @covers \SMW\ConnectionManager
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ConnectionManagerTest extends \PHPUnit_Framework_TestCase {

	private $mwHooksHandler;

	protected function setUp() {
		parent::setUp();

		$this->mwHooksHandler = UtilityFactory::getInstance()->newMwHooksHandler();
	}

	protected function tearDown() {
		$this->mwHooksHandler->restoreListedHooks();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\ConnectionManager',
			new ConnectionManager()
		);
	}

	public function testGetPreferredSQLConnection() {

		$instance = new ConnectionManager();
		$instance->releaseConnection();

		$connection = $instance->getConnection( 'sql' );

		$this->assertSame(
			$connection,
			$instance->getConnection()
		);

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Database',
			$connection
		);

		$instance->releaseConnection();

		$this->assertNotSame(
			$connection,
			$instance->getConnection( 'sql' )
		);
	}

	public function testGetSPARQLConnection() {

		$instance = new ConnectionManager();

		$connection = $instance->getConnection( 'sparql' );

		$this->assertSame(
			$connection,
			$instance->getConnection( 'sparql' )
		);

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\GenericHttpDatabaseConnector',
			$connection
		);

		$instance->releaseConnection();

		$this->assertNotSame(
			$connection,
			$instance->getConnection( 'sparql' )
		);
	}

	public function testGetStandardMasterSQLConnection() {

		$instance = new ConnectionManager();

		$connection = $instance->getConnection( DB_MASTER );

		$this->assertSame(
			$connection,
			$instance->getConnection( 'master' )
		);

		$this->assertInstanceOf(
			'\DatabaseBase',
			$connection
		);
	}

	public function testRegisterConnectionProvider() {

		$connectionProvider = $this->getMockBuilder( '\SMW\DBConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$connectionProvider->expects( $this->once() )
			->method( 'getConnection' );

		$instance = new ConnectionManager();
		$instance->registerConnection( 'foo', $connectionProvider );

		$instance->getConnection( 'foo' );
	}

}
