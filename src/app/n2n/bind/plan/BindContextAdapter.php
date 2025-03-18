<?php

namespace n2n\bind\plan;

use n2n\l10n\Message;
use n2n\util\type\attrs\AttributePath;
use n2n\bind\err\UnresolvableBindableException;

abstract class BindContextAdapter implements BindContext {

	function __construct(private readonly BindInstance $bindInstance) {

	}

	/**
	 * @throws UnresolvableBindableException
	 */
	function getValue(AttributePath|string|null $relativePath = null, bool $mustExist = true): mixed {
		$attributePath = $this->getPath()->ext(AttributePath::build($relativePath));
		return $this->getValueByAbsolutePath($attributePath);
	}

	/**
	 * @throws UnresolvableBindableException
	 */
	function getValueByAbsolutePath(AttributePath|string|null $absolutePath = null, bool $mustExist = true): mixed {
		$attributePath = $this->getPath()->ext(AttributePath::build($absolutePath));

		$bindable = $this->bindInstance->getBindable($attributePath);
		if ($bindable !== null) {
			return $bindable->getValue();
		}

		$bindable = $this->bindInstance->createBindable($attributePath, $mustExist);
		$bindable->setLogical(true);
		return $bindable?->getValue();
	}

}