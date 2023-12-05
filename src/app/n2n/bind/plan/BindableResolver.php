<?php

namespace n2n\bind\plan;

use n2n\bind\err\UnresolvableBindableException;

interface BindableResolver {

	/**
	 * @param BindSource $bindSource
	 * @return Bindable[]
	 * @throws UnresolvableBindableException
	 */
	function resolve(BindSource $bindSource): array;

	function resolveSubBind

}