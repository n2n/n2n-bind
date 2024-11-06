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
namespace n2n\bind\plan;

use n2n\util\type\ArgUtils;
use n2n\util\magic\MagicContext;
use n2n\bind\err\BindMismatchException;
use n2n\bind\mapper\Mapper;
use n2n\bind\err\UnresolvableBindableException;

class BindGroup {

	/**
	 * @param Mapper[] $mappers
	 * @param BindablesResolver $bindableResolver
	 * @param BindContext $bindContext
	 */
	function __construct(private array $mappers, private BindablesResolver $bindableResolver) {
		ArgUtils::valArray($mappers, Mapper::class);
	}

	/**
	 * @param BindInstance $bindInstance
	 * @param BindContext $bindContext
	 * @param MagicContext $magicContext
	 * @return bool false if a Mapper could not perform a modification of value due to errors. The bind process should be
	 * aborted in this case.
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function exec(BindInstance $bindInstance, BindContext $bindContext, MagicContext $magicContext): bool {
		$bindables = $this->bindableResolver->resolve($bindInstance, $bindContext);
		$bindBoundary = new BindBoundary($bindInstance, $bindContext, $bindables);

		foreach ($this->mappers as $mapper) {
			$bindables = $bindBoundary->getBindables();
			try {
				if (!$mapper->map($bindBoundary, $magicContext)) {
					return false;
				}
			} catch (BindMismatchException $e) {
				throw new BindMismatchException('Mapper ' . get_class($mapper) . ' rejected bindables group '
						. implode(', ', array_map(fn ($b) => $b->getPath(), $bindables)), 0, $e);
			}
		}

		return true;
	}
}