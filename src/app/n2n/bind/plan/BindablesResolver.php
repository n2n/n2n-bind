<?php

namespace n2n\bind\plan;

use n2n\bind\err\UnresolvableBindableException;
use n2n\util\type\attrs\AttributePath;

interface BindablesResolver {

	/**
	 * @param BindInstance $bindInstance
	 * @param BindContext $bindContext
	 * @return Bindable[]
	 * @throws UnresolvableBindableException
	 */
	function resolve(BindInstance $bindInstance, BindContext $bindContext): array;

}