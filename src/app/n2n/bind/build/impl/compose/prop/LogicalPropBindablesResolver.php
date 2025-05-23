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

class LogicalPropBindablesResolver implements BindablesResolver {

	function __construct(private array $expressions) {
		ArgUtils::valArray($this->expressions, ['string', 'null']);
	}

	function resolve(BindContext $bindContext): array {
		return [];
	}

//	function resolveLogicalPaths(BindInstance $bindInstance, BindContext $bindContext): array {
//		$paths = [];
//		foreach ($this->expressions as $expression) {
//			$iPaths = $bindInstance->resolvePaths($bindContext->getPath(), $expression);
//			ArgUtils::valArrayReturn($iPaths, $bindInstance, 'resolvePaths', AttributePath::class);
//
//			array_push($paths, ...$iPaths);
//		}
//		return $paths;
//	}
}