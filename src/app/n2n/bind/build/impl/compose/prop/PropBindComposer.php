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

use n2n\bind\plan\BindPlan;
use n2n\bind\plan\BindableTarget;
use n2n\util\magic\MagicTask;
use n2n\util\magic\TaskResult;
use n2n\util\magic\MagicContext;
use n2n\bind\plan\BindGroup;
use n2n\validation\validator\Validator;
use n2n\bind\mapper\Mapper;
use n2n\bind\mapper\impl\ValidatorMapper;
use n2n\bind\plan\BindResult;

class PropBindComposer implements MagicTask {

	private BindPlan $bindPlan;

	function __construct(private PropBindComposerSource $proBindComposerSource, BindableTarget $bindableTarget) {
		$this->bindPlan = new BindPlan($proBindComposerSource, $bindableTarget);
	}

	/**
	 *
	 * @param string $expression
	 * @param Mapper|Validator ...$mappers
	 * @return PropBindComposer
	 */
	function prop(string $expression, Mapper|Validator ...$mappers): static {
		return $this->props([$expression], ...$mappers);
	}

	/**
	 * @param string[] $expressions
	 * @param Mapper|Validator ...$mappers
	 * @return PropBindComposer
	 */
	function props(array $expressions, Mapper|Validator ...$mappers): static {
		$this->assembleBindGroup($expressions, $mappers, true);
		return $this;
	}

	/**
	 *
	 * @param string $expression
	 * @param Mapper|Validator ...$mappers
	 * @return PropBindComposer
	 */
	function optProp(string $expression, Mapper|Validator ...$mappers): static {
		return $this->optProps([$expression], ...$mappers);
	}

	/**
	 * @param string[] $expressions
	 * @param Mapper|Validator ...$mappers
	 * @return PropBindComposer
	 */
	function optProps(array $expressions, Mapper|Validator ...$mappers): static {
		$this->assembleBindGroup($expressions, $mappers, false);
		return $this;
	}

	/**
	 *
	 * @param string $expression
	 * @param bool $mustExist
	 * @param Mapper|Validator ...$mappers
	 * @return PropBindComposer
	 */
	function dynProp(string $expression, bool $mustExist, Mapper|Validator ...$mappers): static {
		return $this->dynProps([$expression], $mustExist, ...$mappers);
	}

	/**
	 * @param string[] $expressions
	 * @param bool $mustExist
	 * @param Mapper|Validator ...$mappers
	 * @return PropBindComposer
	 */
	function dynProps(array $expressions, bool $mustExist, Mapper|Validator ...$mappers): static {
		$this->assembleBindGroup($expressions, $mappers, $mustExist);
		return $this;
	}


	/**
	 * @param array $expressions
	 * @param array $mappers
	 * @param bool $mustExist
	 * @return void
	 */
	private function assembleBindGroup(array $expressions, array $mappers, bool $mustExist): void {
		$mappers = ValidatorMapper::convertValidators($mappers);

		$this->bindPlan->addBindGroup(new BindGroup($mappers,
				new PropBindableGroupSource($this->proBindComposerSource, $expressions, $mustExist),
				$this->proBindComposerSource));
	}

	function exec(MagicContext $magicContext): BindResult {
		return $this->bindPlan->exec($magicContext);
	}
}