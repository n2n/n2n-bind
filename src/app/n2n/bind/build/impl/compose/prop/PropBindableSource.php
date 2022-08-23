<?php

namespace n2n\bind\build\impl\compose\prop;


use n2n\bind\build\impl\prop\BindableResolver;
use n2n\bind\build\impl\prop\UnresolvableBindableException;
use n2n\bind\build\impl\prop\Bindable;

class PropBindableSource implements BindableSource {

	/**
	 * @param string $expression
	 * @param bool $mustExist
	 * @return Bindable[]
	 * @throws UnresolvableBindableException only if $mustExist is true
	 */
	function acquireBindables(string $expression, bool $mustExist): array;
}