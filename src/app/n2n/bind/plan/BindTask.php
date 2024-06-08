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
use n2n\bind\plan\impl\RootBindContext;
use n2n\bind\err\BindMismatchException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\util\magic\impl\TaskResults;
use n2n\util\magic\TaskResult;

class BindTask {

	private ?BindTarget $bindTarget = null;
	/**
	 * @var array<BindPlan>
	 */
	private array $bindPlans = [];

	function __construct(private BindSource $bindSource) {
	}

	/**
	 * @return BindPlan[]
	 */
	function getBindPlans(): array {
		return $this->bindPlans;
	}

	function addBindPlan(BindPlan $bindPlan): void {
		$this->bindPlans[] = $bindPlan;
	}

	function setBindTarget(?BindTarget $bindTarget): void {
		$this->bindTarget = $bindTarget;
	}

	function getBindTarget(): ?BindTarget {
		return $this->bindTarget;
	}

	/**
	 * @param MagicContext $magicContext
	 * @return BindResult
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 * /
	 */
	function exec(MagicContext $magicContext): TaskResult {
		$this->bindSource->reset();

		foreach ($this->bindPlans as $bindPlan) {
			if (!$bindPlan->exec($this->bindSource, new RootBindContext($this->bindSource),
					$magicContext)) {
				return TaskResults::invalid($this->bindSource->createErrorMap());
			}

			$errorMap = $this->bindSource->createErrorMap();
			if (!$errorMap->isEmpty()) {
				return TaskResults::invalid($errorMap);
			}
		}

		$resultValue = $this->bindTarget?->write($this->bindSource->getBindables());

		return TaskResults::valid($resultValue);
	}
}