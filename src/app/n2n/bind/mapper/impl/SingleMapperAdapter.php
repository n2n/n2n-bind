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
namespace n2n\bind\mapper\impl;

use n2n\bind\plan\Bindable;
use n2n\bind\plan\BindContext;
use n2n\util\magic\MagicContext;
use n2n\bind\plan\BindBoundary;
use n2n\bind\err\BindMismatchException;
use n2n\bind\mapper\Mapper;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\mapper\MapResult;

abstract class SingleMapperAdapter extends MapperAdapter {
	protected bool $nonExistingSkipped = true;
	protected bool $dirtySkipped = true;

	public function isNonExistingSkipped(): bool {
		return $this->nonExistingSkipped;
	}

	public function setNonExistingSkipped(bool $nonExistingSkipped): static {
		$this->nonExistingSkipped = $nonExistingSkipped;
		return $this;
	}

	public function isDirtySkipped(): bool {
		return $this->dirtySkipped;
	}

	public function setDirtySkipped(bool $dirtySkipped): static {
		$this->dirtySkipped = $dirtySkipped;
		return $this;
	}

	final function map(BindBoundary $bindBoundary, MagicContext $magicContext): MapResult {
		$mapResult = new MapResult();

		foreach ($bindBoundary->getBindables() as $bindable) {
			if (($this->nonExistingSkipped && !$bindable->doesExist())
					|| ($this->dirtySkipped && $bindable->isDirty())) {
				continue;
			}

			$mapResult = $mapResult->merge(MapResult::fromArg($this->mapSingle($bindable, $bindBoundary, $magicContext)));
			if (!$mapResult->isOk()) {
				return $mapResult;
			}
		}

		return $mapResult;
	}

	/**
	 * @param Bindable $bindable
	 * @param BindBoundary $bindBoundary
	 * @param MagicContext $magicContext
	 * @return MapResult
	 * @throws BindMismatchException {@see Mapper::map()}
	 * @throws UnresolvableBindableException
	 */
	protected abstract function mapSingle(Bindable $bindable, BindBoundary $bindBoundary, MagicContext $magicContext): MapResult|bool;
	
}