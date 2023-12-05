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

use n2n\bind\plan\BindableResolver;
use n2n\bind\plan\Bindable;
use n2n\util\type\ArgUtils;
use n2n\validation\plan\DetailedName;

class PropBindableResolver implements BindableResolver {

	function __construct(private PropBindSource $propBindableSource,
			private array $expressions, private bool $mustExist) {
		ArgUtils::valArray($this->expressions, ['string', 'null']);
	}

	function acquireDefaultBindables(): array {
		$bindables = [];
		foreach ($this->expressions as $expression) {
			if ($expression === null) {
				$iBindables = $this->propBindableSource->acquireRootAsBindable();
				ArgUtils::valArrayReturn($iBindables, $this->propBindableSource, 'acquireBindables', Bindable::class);
			} else {
				$iBindables = $this->propBindableSource->acquireBindables($expression, $this->mustExist);
				ArgUtils::valArrayReturn($iBindables, $this->propBindableSource, 'acquireBindables', Bindable::class);
			}

			array_push($bindables, ...$iBindables);
		}
		return $bindables;
	}

	function acquireBindable(string $name, bool $mustExist): ?Bindable {
		return $this->propBindableSource->acquireBindable($name, $mustExist);
	}

	/**
	 * @param DetailedName $detailedName
	 * @return BindableResolver[]
	 * @throws \n2n\bind\err\UnresolvableBindableException
	 */
	function acquireDefaultSubBindGroupSources(DetailedName $detailedName): array {
		$subBindSources = [];
		foreach ($this->acquireDefaultBindables() as $bindable) {
			$subBindSources[] = $this->propBindableSource->acquireSubBindableSource($bindable->getName());
		}
		return $subBindSources;
	}




}