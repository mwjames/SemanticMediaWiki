<?php

namespace SMW;

use SMW\DBConnectionProvider;
use SMW\SPARQLStore\SparqlDBConnectionProvider;
use SMW\MediaWiki\DatabaseConnectionProvider;
use SMW\MediaWiki\LazyDBConnectionProvider;

use RuntimeException;

/**
 * @group SMW
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ConnectionManager {

	/**
	 * By design this variable is kept static to ensure a ConnectionProvider
	 * instance is only intialized once per request and the connection itself
	 * is derived from the ConnectionProvider not from the ConnectionManager.
	 *
	 * Required persistency of a connection is handled by the ConnectionProvider
	 * while the persistency of a ConnectionProvider instance is managed by the
	 * ConnectionManager.
	 *
	 * @var array
	 */
	private static $connectionProviderMap = array();

	/**
	 * @since 2.1
	 *
	 * @param string|null type
	 *
	 * @return mixed
	 * @throws RuntimeException
	 */
	public function getConnection( $type = null ) {
		return $this->getConnectionProviderForType( strtolower( $type ) )->getConnection();
	}

	/**
	 * @since 2.1
	 *
	 * @return ConnectionManager
	 */
	public function releaseConnection() {

		foreach ( self::$connectionProviderMap as $connectionProvider ) {
			$connectionProvider->releaseConnection();
		}

		self::$connectionProviderMap = array();

		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @param string $type
	 * @param DBConnectionProvider $connectionProvider
	 */
	public function registerConnection( $type, DBConnectionProvider $connectionProvider ) {
		self::$connectionProviderMap[ strtolower( $type ) ] = $connectionProvider;
	}

	private function getConnectionProviderForType( $type ) {

		if ( self::$connectionProviderMap === array() ) {
			$this->initConnectionProviderMap();
		}

		if ( isset( self::$connectionProviderMap[ $type ] ) ) {
			return self::$connectionProviderMap[ $type ];
		}

		return self::$connectionProviderMap[ 'default' ];
	}

	private function initConnectionProviderMap() {

		self::$connectionProviderMap = array(
			'sql'     => new DatabaseConnectionProvider(),
			'sparql'  => new SparqlDBConnectionProvider(),
			'slave'   => new LazyDBConnectionProvider( DB_SLAVE ),
			'master'  => new LazyDBConnectionProvider( DB_MASTER ),
		);

		// Define a default in case no type has been selected
		self::$connectionProviderMap[ 'default' ] = self::$connectionProviderMap[ 'sql' ];

		// Create references to ensure to use the same resource
		self::$connectionProviderMap[ DB_SLAVE ]  = self::$connectionProviderMap[ 'slave' ];
		self::$connectionProviderMap[ DB_MASTER ] = self::$connectionProviderMap[ 'master' ];

		// Register additional connection providers (e.g 'dbal', 'pdo' or 'mongo' )
		// during the setup
		wfRunHooks( 'SMW::ConnectionManager::RegisterConnectionProvider', array( $this ) );
	}

}
