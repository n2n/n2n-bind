<?php

namespace n2n\bind\plan;

use n2n\bind\err\BindTargetException;

/**
 * @template T
 */
interface BindTargetInstance {

	/**
	 * @param Bindable[] $bindables includes also {@link Bindable}s which are invalid/dirty, do not exists or are logical.
	 * @throws BindTargetException
	 */
	function write(array $bindables): void;

	/**
	 * @return T
	 */
	function getValue();
}