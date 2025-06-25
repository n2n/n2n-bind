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
use n2n\bind\plan\BindTarget;
use n2n\bind\mapper\impl\ValidatorMapper;
use n2n\bind\plan\BindGroup;
use n2n\bind\mapper\Mapper;
use n2n\bind\plan\BindTask;
use n2n\bind\plan\BindSource;
use n2n\bind\plan\BindResult;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\BindMismatchException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\util\type\attrs\AttributeWriter;
use n2n\bind\build\impl\target\AttrsBindTarget;
use n2n\bind\build\impl\target\RefBindTarget;
use n2n\bind\build\impl\target\ClosureBindTarget;
use n2n\bind\build\impl\target\ObjectBindTarget;
use n2n\util\magic\TaskResult;
use n2n\util\magic\impl\MagicContexts;
use n2n\util\magic\MagicTask;

class UnionBindComposer implements MagicTask {

	private BindTask $bindTask;

	private BindPlan $bindPlan;

	function __construct(private BindSource $source) {
		$this->bindTask = new BindTask($source);
		$this->bindTask->addBindPlan($this->bindPlan = new BindPlan());
	}

	function ifValid(): static {
		$this->bindTask->addBindPlan($this->bindPlan = new BindPlan());
		return $this;
	}

	/**
	 * @param Mapper|Validator ...$mappers
	 * @return UnionBindComposer
	 */
	function map(Mapper|Validator ...$mappers): static {
		$mappers = ValidatorMapper::convertValidators($mappers);

		$this->bindPlan->addBindGroup(
				new BindGroup($mappers, new UnionBindablesResolver($this->source), $this->source));

		return $this;
	}

	function toAttrs(AttributeWriter|\Closure $attributeWriter): UnionBindComposer {
		return $this->to(new AttrsBindTarget($attributeWriter));
	}

	/**
	 * @param array $array
	 * @return UnionBindComposer
	 */
	function toArray(array &$array = []): UnionBindComposer {
		return $this->to(new RefBindTarget($array, true));
	}

	/**
	 * @param $value
	 * @return UnionBindComposer
	 */
	function toValue(&$value = null): UnionBindComposer {
		return $this->to(new RefBindTarget($value, false));
	}

	function toClosure(\Closure $closure): UnionBindComposer {
		return $this->to(new ClosureBindTarget($closure));
	}

	/**
	 * @param object $obj
	 * @return UnionBindComposer
	 */
	function toObj(object $obj): UnionBindComposer {
		return $this->to(new ObjectBindTarget($obj));
	}


	/**
	 * @param BindTarget $target
	 * @return UnionBindComposer
	 */
	function to(BindTarget $target): UnionBindComposer {
		$this->bindTask->setBindTarget($target);
		return $this;
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function exec(?MagicContext $magicContext = null, mixed $input = null): TaskResult {
		$magicContext ??= MagicContexts::simple([]);

		return $this->bindTask->exec($magicContext, $input);
	}	
}
