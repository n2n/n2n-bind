<?php

namespace n2n\bind\plan;

use n2n\bind\err\UnresolvableBindableException;
use n2n\util\attr\AttributePath;
use n2n\bind\err\BindMismatchException;

interface BindablesResolver {

	/**
	 * @param BindContext $bindContext
	 * @return Bindable[]
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function resolve(BindContext $bindContext): array;

}