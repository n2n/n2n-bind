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
use n2n\validation\plan\ErrorMap;
use n2n\bind\build\impl\target\NullBindTargetInstance;

class BindTask {

	private ?BindTarget $bindTarget = null;
	/**
	 * @var array<BindStep>
	 */
	private array $bindSteps = [];

	private bool $writeTargetOnFailure = false;

	function __construct(private BindSource $bindSource) {
	}

	/**
	 * @return BindStep[]
	 */
	function getBindStep(): array {
		return $this->bindSteps;
	}

	function addBindStep(BindStep $bindStep): void {
		$this->bindSteps[] = $bindStep;
	}

	function setBindTarget(?BindTarget $bindTarget): void {
		$this->bindTarget = $bindTarget;
	}

	function getBindTarget(): ?BindTarget {
		return $this->bindTarget;
	}

	/**
	 * @param MagicContext $magicContext
	 * @param mixed
	 * @return BindResult
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function exec(MagicContext $magicContext, mixed $input): BindResult {
		try {
			$bindInstance = $this->bindSource->next($input);
		} catch (IncompatibleBindInputException $e) {
			throw new BindMismatchException($e->getMessage(), previous: $e);
		}

		$bindTargetInstance = $this->bindTarget?->next() ?? new NullBindTargetInstance();
		$bindContext = new RootBindContext($bindInstance, $bindTargetInstance);
		$targetWritten = false;
		foreach ($this->bindSteps as $bindStep) {
			$result = $bindStep->exec($bindContext, $magicContext);
			$targetWritten = $targetWritten || $result->targetWritten;
			if ($result->ok) {
				continue;
			}
			if ($targetWritten) {
				return BindResults::invalidWithValue($bindInstance->createErrorMap(), $bindTargetInstance->getValue());
			}
			return BindResults::invalid($bindInstance->createErrorMap());
		}

		$errorMap = $bindInstance->createErrorMap();
		if ($errorMap->isEmpty()) {
			$bindTargetInstance?->write($bindInstance->getBindables());
			return BindResults::valid($bindTargetInstance->getValue());
		}
		if ($targetWritten) {
			return BindResults::invalidWithValue($bindInstance->createErrorMap(), $bindTargetInstance->getValue());
		}
		return BindResults::invalid($bindInstance->createErrorMap());
	}

	private function createInvalidResult(BindInstance $bindInstance, ErrorMap $errorMap): BindResult {
		if ($this->writeTargetOnFailure) {
			return BindResults::invalidWithValue($errorMap, $this->bindTarget
					?->write($bindInstance->getBindables()));
		}

		return BindResults::invalid($errorMap);
	}
}