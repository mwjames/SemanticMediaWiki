<?php

namespace SMW\Tests\Util;

use SMW\MediaWiki\Hooks\HookRegistry;

use Closure;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class MwHooksHandler {

	/**
	 * @var HookRegistry
	 */
	private $hookRegistry = null;

	private $wgHooks = array();
	private $inTestRegisteredHooks = array();

	private $listOfSmwHooks = array(
		'SMWStore::updateDataBefore',
		'smwInitProperties',
		'SMW::SQLStore::updatePropertyTableDefinitions',
		'SMW::Store::BeforeQueryResultLookup',
		'SMW::Store::AfterQueryResultLookup',
		'SMW::ConnectionManager::RegisterConnectionProvider'
	);

	/**
	 * @since  2.0
	 *
	 * @return MwHooksHandler
	 */
	public function deregisterListedHooks() {

		$listOfHooks = array_merge(
			$this->listOfSmwHooks,
			$this->getHookRegistry()->getListOfRegisteredFunctionHooks()
		);

		foreach ( $listOfHooks as $hook ) {

			if ( !isset( $GLOBALS['wgHooks'][ $hook ] ) ) {
				continue;
			}

			$this->wgHooks[ $hook ] = $GLOBALS['wgHooks'][ $hook ];
			$GLOBALS['wgHooks'][ $hook ] = array();
		}

		return $this;
	}

	/**
	 * @since  2.0
	 *
	 * @return MwHooksHandler
	 */
	public function restoreListedHooks() {

		foreach ( $this->inTestRegisteredHooks as $hook ) {
			unset( $GLOBALS['wgHooks'][ $hook ] );
		}

		foreach ( $this->wgHooks as $hook => $definition ) {
			$GLOBALS['wgHooks'][ $hook ] = $definition;
			unset( $this->wgHooks[ $hook ] );
		}

		return $this;
	}

	/**
	 * @since  2.1
	 *
	 * @return MwHooksHandler
	 */
	public function register( $name, Closure $callback ) {

		$listOfHooks = array_merge(
			$this->listOfSmwHooks,
			$this->getHookRegistry()->getListOfRegisteredFunctionHooks()
		);

		if ( !in_array( $name, $listOfHooks ) ) {
			throw new RuntimeException( "$name is not listed as registrable hook" );
		}

		$this->inTestRegisteredHooks[] = $name;
		$GLOBALS['wgHooks'][ $name ][] = $callback;

		return $this;
	}

	/**
	 * @since  2.1
	 *
	 * @return MwHooksHandler
	 */
	public function invokeHooksFromRegistry() {
		$this->getHookRegistry()->register();
		return $this;
	}

	/**
	 * @since  2.1
	 *
	 * @return HookRegistry
	 */
	public function getHookRegistry() {

		if ( $this->hookRegistry === null ) {
			 $this->hookRegistry = new HookRegistry( $GLOBALS, '' );
		}

		return $this->hookRegistry;
	}

}
