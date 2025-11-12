<?php
namespace n2n\bind\build\impl\source\object;

use n2n\bind\plan\BindSource;
use n2n\bind\err\IncompatibleBindInputException;
use n2n\util\type\TypeUtils;
use n2n\bind\plan\BindInstance;
use n2n\reflection\property\UninitializedBehaviour;

class ObjectBindSource implements BindSource {
	/**
	 * Shared proxy cache for object property access.
	 */
	private ObjectBindAccessProxyCache $proxyCache;

	/**
	 * @param object|null $object
	 * @param bool $undefinedAsNonExisting Treat bindables whose value is Undefined/uninitialized as non-existing (skipped; will fail mustExist).
	 * @param UninitializedBehaviour $uninitializedBehaviour Strategy for accessing uninitialized/undefinable properties (e.g. return Undefined, return null, or throw exception) when reading object properties.
 	*/
	public function __construct(private ?object $object, private bool $undefinedAsNonExisting,
			private UninitializedBehaviour $uninitializedBehaviour) {
		$this->proxyCache = new ObjectBindAccessProxyCache($uninitializedBehaviour);
	}

	public function next(mixed $input): BindInstance {
		if ($this->object !== null) {
			return (new BindInstance(
					new ObjectBindableFactory($this->object, $this->proxyCache),
					$this->undefinedAsNonExisting))->init();
		}

		if (is_object($input)) {
			return (new BindInstance(
					new ObjectBindableFactory($input, $this->proxyCache)))->init();
		}

		throw new IncompatibleBindInputException('ObjectBindSource requires input to be of type object. Given: '
				. TypeUtils::getTypeInfo($input));
	}
}