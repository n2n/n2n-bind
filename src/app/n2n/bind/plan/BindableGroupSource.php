<?php

namespace n2n\bind\plan;

use n2n\bind\err\UnresolvableBindableException;

interface BindableGroupSource {

	/**
	 * @return Bindable[]
	 * @throws UnresolvableBindableException
	 */
	function acquireDefaultBindables(): array;

	/**
	 * @param string $name
	 * @return Bindable
	 */
	function acquireBindable(string $name): Bindable;

}