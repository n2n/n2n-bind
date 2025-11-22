<?php

namespace n2n\bind\plan;

use n2n\util\type\attrs\AttributePath;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\BindMismatchException;
use n2n\util\ex\IllegalStateException;

abstract class BindContextAdapter implements BindContext {

	function __construct(private readonly BindInstance $bindInstance,
			private readonly BindTargetInstance $bindTargetInstance) {

	}

	/**
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function getValue(AttributePath|string|null $relativePath = null, bool $mustExist = true): mixed {
		$attributePath = $this->getPath()->ext(AttributePath::build($relativePath));
		return $this->getValueByAbsolutePath($attributePath, $mustExist);
	}

	/**
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function getValueByAbsolutePath(AttributePath|string|null $absolutePath = null, bool $mustExist = true): mixed {
		$attributePath = AttributePath::build($absolutePath) ?? new AttributePath([]);

		$bindable = $this->bindInstance->getBindable($attributePath);
		if ($bindable !== null) {
			if (!$mustExist || $bindable->doesExist()) {
				return $bindable->getValue();
			}

			throw new UnresolvableBindableException('Bindable is marked as not existing: '
						. $absolutePath->toAbsoluteString());
		}

		$bindable = $this->bindInstance->createBindable($attributePath, $mustExist);
		$bindable->setLogical(true);
		return $bindable?->getValue();
	}

	function acquireBindable(string|array|AttributePath $relativePath): Bindable {
		$path = $this->getPath()->ext(AttributePath::create($relativePath));

		return $this->acquireBindableByAbsoluteName($path);
	}

	function acquireBindableByAbsoluteName(string|array|AttributePath $absolutePath): Bindable {
		$path = AttributePath::create($absolutePath);

		return $this->bindInstance->getBindable($path) ??
				IllegalStateException::try(fn () => $this->bindInstance->createBindable($path, false));
	}

	function unwarpBindInstance(): BindInstance {
		return $this->bindInstance;
	}

	function unwrapBindTargetInstance(): BindTargetInstance {
		return $this->bindTargetInstance;
	}
}