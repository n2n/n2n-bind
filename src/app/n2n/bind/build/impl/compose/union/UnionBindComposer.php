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
namespace n2n\bind\build\impl\compose\union;
use n2n\util\magic\MagicContext;
use n2n\validation\plan\ValidationTask;
use n2n\validation\plan\ValidationPlan;
use n2n\validation\plan\Validatable;
use n2n\validation\validator\Validator;
use n2n\util\type\ArgUtils;
use n2n\validation\plan\ValidationGroup;
use n2n\validation\plan\ValidationResult;
use n2n\bind\plan\BindPlan;
use n2n\bind\plan\BindableTarget;
use n2n\bind\mapper\impl\ValidatorMapper;
use n2n\bind\plan\BindGroup;
use n2n\bind\mapper\Mapper;
use n2n\bind\plan\BindTask;
use n2n\bind\plan\BindSource;
use n2n\bind\plan\BindResult;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\BindMismatchException;
use n2n\bind\err\UnresolvableBindableException;

class UnionBindComposer {

	/**
	 * @var BindPlan
	 */
	private BindTask $bindTask;

	function __construct(private BindSource $source, private BindableTarget $bindableTarget) {
		$this->bindTask = new BindTask($source, $this->bindableTarget, new BindPlan());
	}

	/**
	 * @param Mapper|Validator ...$mappers
	 * @return UnionBindComposer
	 */
	function map(Mapper|Validator ...$mappers): static {
		$mappers = ValidatorMapper::convertValidators($mappers);

		$this->bindTask->getBindPlan()->addBindGroup(
				new BindGroup($mappers, new UnionBindablesResolver($this->source), $this->source));

		return $this;
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function exec(MagicContext $magicContext): BindResult {
		return $this->bindTask->exec($magicContext);
	}	
}
