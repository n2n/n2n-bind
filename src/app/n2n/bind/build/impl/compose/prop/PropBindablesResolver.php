<?php
/*
 * Copyright (c) 2012-2016, Hofmänner New Media.
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This file is part of the N2N FRAMEWORK.
 *
 * The N2N FRAMEWORK is free software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * N2N is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details: http://www.gnu.org/licenses/
 *
 * The following people participated in this project:
 *
 * Andreas von Burg.....: Architect, Lead Developer
 * Bert Hofmänner.......: Idea, Frontend UI, Community Leader, Marketing
 * Thomas Günther.......: Developer, Hangar
 */
namespace n2n\bind\build\impl\compose\prop;

use n2n\bind\plan\BindablesResolver;
use n2n\bind\plan\Bindable;
use n2n\util\type\ArgUtils;
use n2n\util\type\attrs\AttributePath;
use n2n\bind\plan\BindSource;
use n2n\bind\plan\BindContext;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\plan\BindInstance;

class PropBindablesResolver implements BindablesResolver {

	function __construct(private array $expressions, private bool $mustExist, private bool $logical) {
		ArgUtils::valArray($this->expressions, ['string', 'null']);
	}


	/**
	 * @throws UnresolvableBindableException
	 */
	private function acquireBindable(BindInstance $bindInstance, AttributePath $attributePath): Bindable {
		$bindable = $bindInstance->getBindable($attributePath);
		if ($bindable === null) {
			$bindable = $bindInstance->createBindable($attributePath, $this->mustExist);
			$bindable->setLogical($this->logical);
			return $bindable;
		}

		if ($this->mustExist && !$bindable->doesExist()) {
			throw new UnresolvableBindableException(
					'Bindable does not exist and was probably removed during bind process: '
							. $attributePath);
		}

		if (!$this->logical) {
			$bindable->setLogical(false);
		}

		return $bindable;
	}

	function resolve(BindInstance $bindInstance, BindContext $bindContext): array {
		$bindables = [];
		foreach ($this->expressions as $expression) {
			foreach ($bindInstance->resolvePaths($bindContext->getPath(), $expression) as $path) {
				$bindables[] = $this->acquireBindable($bindInstance, $path);
			}
		}
		return $bindables;
	}
}