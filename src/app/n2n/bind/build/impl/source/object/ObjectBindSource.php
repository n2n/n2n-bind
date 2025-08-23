<?php
namespace n2n\bind\build\impl\source\object;

use n2n\bind\plan\BindableFactory;
use n2n\bind\plan\BindSource;
use n2n\bind\err\IncompatibleBindInputException;
use n2n\util\type\TypeUtils;
use n2n\bind\build\impl\source\BindInstance;

class ObjectBindSource implements BindSource {
	/**
	 * Shared proxy cache for object property access.
	 */
	private ObjectBindAccessProxyCache $proxyCache;

	public function __construct(private ?object $object, private bool $undefinedAsNonExisting) {
		$this->proxyCache = new ObjectBindAccessProxyCache();
	}

	public function next(mixed $input): BindInstance {
		if ($this->object !== null) {
			return (new BindInstance(new ObjectBindableFactory($this->object, $this->proxyCache)))->init();
		}

		if (is_object($input)) {
			return (new BindInstance(new ObjectBindableFactory($input, $this->proxyCache)))->init();
		}

		throw new IncompatibleBindInputException('ObjectBindSource requires input to be of type object. Given: '
				. TypeUtils::getTypeInfo($input));
	}
}