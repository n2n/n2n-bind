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

use n2n\bind\mapper\Mapper;
use n2n\util\type\TypeConstraint;
use n2n\bind\plan\Bindable;
use n2n\validation\plan\Validatable;
use n2n\validation\err\ValidationMismatchException;
use n2n\bind\err\BindMismatchException;
use n2n\util\type\ValueIncompatibleWithConstraintsException;
use n2n\l10n\Lstr;
use n2n\util\type\ArgUtils;
use n2n\bind\build\impl\Bind;
use n2n\validation\validator\impl\ValidatorAdapter;

abstract class MapperAdapter implements Mapper {

	/**
	 * @param Bindable $bindable
	 * @param TypeConstraint $typeConstraint
	 * @return mixed
	 */
	protected function readSafeValue(Bindable $bindable, TypeConstraint $typeConstraint) {
		try {
			return $typeConstraint->validate($bindable->getValue());
		} catch (ValueIncompatibleWithConstraintsException $e) {
//			throw new BindMismatchException('Bindable ' . $bindable->getName() . ' is not compatible with '
//					. get_class($this), 0, $e);
			throw $this->createMismatchException($bindable, null, $e);
		}
	}

	protected function createMismatchException(Bindable $bindable, string $reason = null, \Throwable $previous = null) {
		throw new BindMismatchException('Bindable ' . $bindable->getName() . ' is not compatible with '
				. get_class($this) . ($reason === null ? '' : 'Reason: ' . $reason), 0, $previous);
	}

	/**
	 * @param Bindable $bindable
	 * @return string|Lstr
	 */
	protected function readLabel(Bindable $bindable) {
		$label = $bindable->getLabel();
		ArgUtils::valType(['string', Lstr::class], $label);
		return $label;
	}

}