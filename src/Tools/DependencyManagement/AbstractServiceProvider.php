<?php
/**
 * AbstractServiceProvider class file.
 *
 * @package Automattic/WooCommerce/Tools/DependencyManagement
 */

namespace Automattic\WooCommerce\Tools\DependencyManagement;

use League\Container\Definition\DefinitionInterface;
use League\Container\Definition\Definition;

/**
 * Base class for the service providers used to register classes in the container.
 *
 * See the documentation of the original class this one is based on (https://container.thephpleague.com/3.x/service-providers)
 * for basic usage details. What this class adds is:
 *
 * - The `addWithAutoArguments` method that allows to register classes without having to specify the constructor arguments.
 * - The `shareWithAutoArguments` method, sibling of the above.
 * - Convenience `add` and `share` methods that are just proxies for the same methods in `$this->getContainer()`.
 *
 * @package Automattic\WooCommerce\Tools\DependencyManagement
 */
abstract class AbstractServiceProvider extends \League\Container\ServiceProvider\AbstractServiceProvider {

	/**
	 * Register a class in the container and use reflection to guess the constructor arguments.
	 *
	 * @param string $class_name Class name to register.
	 * @param bool   $shared Whether to register the class as shared (`get` always returns the same instance) or not.
	 *
	 * @return DefinitionInterface The generated container definition.
	 *
	 * @throws \Exception Error when reflecting the class, or class constructor is not public, or an argument has no valid type hint.
	 */
	public function addWithAutoArguments( string $class_name, bool $shared = false ) : DefinitionInterface {
		try {
			$reflector = new \ReflectionClass( $class_name );
		} catch ( \ReflectionException $ex ) {
			throw new \Exception( get_class( $this ) . "::addWithAutoArguments: error when reflecting class '$class_name': {$ex->getMessage()}" );
		}

		$definition = new Definition( $class_name, null );

		$constructor = $reflector->getConstructor();

		if ( ! is_null( $constructor ) ) {
			if ( ! $constructor->isPublic() ) {
				throw new \Exception( get_class( $this ) . "::addWithAutoArguments: constructor of class '$class_name' isn't public, instances can't be created." );
			}

			$constructor_arguments = $constructor->getParameters();
			foreach ( $constructor_arguments as $argument ) {
				$argument_class = $argument->getClass();
				if ( is_null( $argument_class ) ) {
					throw new \Exception( get_class( $this ) . "::addWithAutoArguments: constructor argument '{$argument->getName()}' of class '$class_name' doesn't have a type hint or has one that doesn't specify a class." );
				}

				$definition->addArgument( $argument_class->name );
			}
		}

		// Register the definition only after being sure that no exception will be thrown.

		$this->getContainer()->add( $definition->getAlias(), $definition, $shared );

		return $definition;
	}

	/**
	 * Register a class in the container and use reflection to guess the constructor arguments.
	 * The class is registered as shared, so `get` on the container always returns the same instance.
	 *
	 * @param string $class_name Class name to register.
	 *
	 * @return DefinitionInterface The generated container definition.
	 *
	 * @throws \Exception Error when reflecting the class, or class constructor is not public, or an argument has no valid type hint.
	 */
	public function shareWithAutoArguments( string $class_name ) : DefinitionInterface {
		return $this->addWithAutoArguments( $class_name, true );
	}

	/**
	 * Register an entry in the container.
	 *
	 * @param string     $id Entry id (typically a class or interface name).
	 * @param mixed|null $concrete Concrete entity to register under that id, null for automatic creation.
	 * @param bool|null  $shared Whether to register the class as shared (`get` always returns the same instance) or not.
	 *
	 * @return DefinitionInterface The generated container definition.
	 */
	public function add( string $id, $concrete = null, bool $shared = null ) : DefinitionInterface {
		return $this->getContainer()->add( $id, $concrete, $shared );
	}

	/**
	 * Register a shared entry in the container (`get` always returns the same instance).
	 *
	 * @param string     $id Entry id (typically a class or interface name).
	 * @param mixed|null $concrete Concrete entity to register under that id, null for automatic creation.
	 *
	 * @return DefinitionInterface The generated container definition.
	 */
	public function share( string $id, $concrete = null ) : DefinitionInterface {
		return $this->getContainer()->share( $id, $concrete );
	}
}