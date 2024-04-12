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

use n2n\validation\plan\Validatable;

interface Bindable extends Validatable {

	function setExist(bool $exist): static;

	function setValue(mixed $value): static;

	/**
	 * If true it means that a Mapper could not perform necessary modifications on this Bindable and this bindable
	 * should be skipped by future Mappers. This can cause other Bindable to become dirty too if a Mapper
	 * needs to perform modification in context of multiple Bindable and one of these Bindables is dirty.
	 *
	 * @return bool
	 */
	function isDirty(): bool;

	/**
	 * See {@link self::isDirty()} for meaning.
	 *
	 * If a Mapper sets dirty to true it should be either due to other dirty or invalid Bindables or due to a valdation
	 * error with should result in adding an error (see {@link Bindable::addError()}) and {@link Bindable::isValid()}
	 * becoming false.
	 *
	 * @param bool $dirty
	 * @return void
	 */
	function setDirty(bool $dirty): void;

	function isLogical(): bool;

	function setLogical(bool $logical): static;
}
