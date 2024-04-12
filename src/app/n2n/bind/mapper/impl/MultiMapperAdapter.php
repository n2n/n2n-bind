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
use n2n\bind\mapper\MapperUtils;

abstract class MultiMapperAdapter extends MapperAdapter {

	private MultiMapMode $multiMapMode = MultiMapMode::ALWAYS;

	function __construct(MultiMapMode $multiMapMode) {
		$this->multiMapMode = $multiMapMode;
	}

	final function map(BindBoundary $bindBoundary, MagicContext $magicContext): bool {
		$allBindables = $bindBoundary->getBindables();

		if (MapperUtils::spreadDirtyState($allBindables)) {
			return true;
		}

		$existingBindables = array_filter($allBindables, fn (Bindable $b) => $b->doesExist());

		if (($this->multiMapMode === MultiMapMode::ANY_BINDABLE_MUST_EXIST && empty($existingBindables))
				|| ($this->multiMapMode === MultiMapMode::EVERY_BINDABLE_MUST_EXIST
						&& count($existingBindables) < count($allBindables))) {
			return true;
		}

		return $this->mapMulti($existingBindables, $bindBoundary, $magicContext);
	}

	/**
	 * @param Bindable[] $bindables
	 * @param BindBoundary $bindBoundary
	 * @param MagicContext $magicContext
	 * @return bool
	 * @throws BindMismatchException {@see Mapper::map()}
	 * @throws UnresolvableBindableException
	 */
	protected abstract function mapMulti(array $bindables, BindBoundary $bindBoundary, MagicContext $magicContext): bool;
	
}