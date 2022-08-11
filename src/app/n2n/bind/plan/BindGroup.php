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
use n2n\bind\map\Mapper;
use n2n\validation\plan\ValidationContext;
use n2n\util\magic\MagicContext;

class BindGroup {

	/**
	 * @param Mapper[] $mappers
	 * @param Bindable[] $bindables
	 */
	function __construct(private array $mappers, private array $bindables) {
		ArgUtils::valArray($mappers, Mapper::class);
		ArgUtils::valArray($this->bindables, Bindable::class);
	}

	function exec(BindContext $bindContext, MagicContext $magicContext): void {
		foreach ($this->mappers as $mapper) {
			$mapper->map($this->bindables, $bindContext, $magicContext);
		}
	}

	private handleMapReturn(array $bindables) {

	}
}