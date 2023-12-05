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

use n2n\util\magic\MagicContext;
use n2n\bind\plan\impl\SimpleBindResult;
use n2n\bind\err\BindTargetException;

class BindTask {

	/**
	 * @var BindGroup[]
	 */
	private array $bindGroups = [];

	function __construct(private BindSource $bindableSource, private BindableTarget $bindableTarget,
			private BindPlan $bindPlan) {
	}

	function getBindPlan(): BindPlan {
		return $this->bindPlan;
	}

	/**
	 * @param MagicContext $magicContext
	 * @return BindResult
	 * @throws BindTargetException
	 */
	function exec(MagicContext $magicContext): BindResult {
		$this->bindableSource->reset();

		if (!$this->bindPlan->exec($magicContext)) {
			return new SimpleBindResult($this->bindableSource->createErrorMap());
		}

		$errorMap = $this->bindableSource->createErrorMap();
		if (!$errorMap->isEmpty()) {
			return new SimpleBindResult($errorMap);
		}

		$this->bindableTarget->write($this->bindableSource->getBindables());

		return new SimpleBindResult(null);
	}
}