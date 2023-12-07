<?php

namespace n2n\bind\mapper\impl\mod;

use n2n\util\type\attrs\AttributePath;
use n2n\bind\plan\Bindable;
use n2n\bind\plan\BindSource;

class BindablesFilter {

	function __construct(private BindSource $bindSource) {
	}

	/**
	 * @param AttributePath $path
	 * @return Bindable[]
	 */
	function descendantsOf(AttributePath $path): array {
		$pathSize = $path->size();
		$bindables = [];
		foreach ($this->bindSource->getBindables() as $key => $bindable) {
			$bPath = $bindable->getPath();
			if ($bPath->size() > $pathSize && $bPath->startsWith($path)) {
				$bindables[$key] = $bindable;
			}
		}
		return $bindables;
	}
}