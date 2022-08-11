<?php

namespace n2n\bind\build\impl\prop;


class PropBindableSource implements BindableResolver {

	/**
	 * @param string $expression
	 * @param bool $mustExist
	 * @return Bindable[]
	 * @throws UnresolvableBindableException only if $mustExist is true
	 */
	function resolveBindables(string $expression, bool $mustExist): array;
}