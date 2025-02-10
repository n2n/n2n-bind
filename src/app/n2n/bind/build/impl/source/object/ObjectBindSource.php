<?php
namespace n2n\bind\build\impl\source\object;

use n2n\bind\plan\BindInstance;
use n2n\bind\plan\BindSource;
use n2n\bind\err\IncompatibleBindInputException;
use n2n\util\type\TypeUtils;

class ObjectBindSource implements BindSource {
	/**
	 * Shared proxy cache for object property access.
	 */
	private ObjectBindAccessProxyCache $proxyCache;

	public function __construct(private ?object $object) {
		$this->proxyCache = new ObjectBindAccessProxyCache();
	}

	public function next(mixed $input): BindInstance {
		if ($this->object !== null) {
			return new ObjectBindInstance($this->object, $this->proxyCache);
		}

		if (is_object($input)) {
			return new ObjectBindInstance($input, $this->proxyCache);
		}

		throw new IncompatibleBindInputException('ObjectBindSource requires input to be of type object. Given: '
				. TypeUtils::getTypeInfo($input));
	}
}