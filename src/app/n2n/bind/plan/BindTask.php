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
use n2n\bind\err\BindTargetException;
use n2n\bind\plan\impl\RootBindContext;
use n2n\bind\err\BindMismatchException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\IncompatibleBindInputException;
use n2n\bind\plan\impl\BindResults;
use n2n\bind\build\impl\source\BindInstance;
use n2n\validation\plan\ErrorMap;

class BindTask {

	private ?BindTarget $bindTarget = null;
	/**
	 * @var array<BindPlan>
	 */
	private array $bindPlans = [];

	private bool $writeTargetOnFailure = false;

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

	function setWriteTargetOnFailure(bool $writeTargetOnFailure): void {
		$this->writeTargetOnFailure = $writeTargetOnFailure;
	}

	function isWriteTargetOnFailure(): bool {
		return $this->writeTargetOnFailure;
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
	function exec(MagicContext $magicContext, mixed $input): BindResult {
		try {
			$bindInstance = $this->bindSource->next($input);
		} catch (IncompatibleBindInputException $e) {
			throw new BindMismatchException($e->getMessage(), previous: $e);
		}

		foreach ($this->bindPlans as $bindPlan) {
			if (!$bindPlan->exec(new RootBindContext($bindInstance), $magicContext)) {
				return $this->createInvalidResult($bindInstance, $bindInstance->createErrorMap());
			}

			$errorMap = $bindInstance->createErrorMap();
			if ($errorMap->isEmpty()) {
				continue;
			}

			return $this->createInvalidResult($bindInstance, $errorMap);
		}

		$resultValue = $this->bindTarget?->write($bindInstance->getBindables());

		return BindResults::valid($resultValue);
	}

	private function createInvalidResult(BindInstance $bindInstance, ErrorMap $errorMap): BindResult {
		if ($this->writeTargetOnFailure) {
			return BindResults::invalidWithValue($errorMap, $this->bindTarget
					?->write($bindInstance->getBindables()));
		}

		return BindResults::invalid($errorMap);
	}
}