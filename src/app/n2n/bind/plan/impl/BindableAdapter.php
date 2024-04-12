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
namespace n2n\bind\plan\impl;

use n2n\util\type\attrs\AttributePath;
use n2n\bind\plan\Bindable;
use n2n\validation\plan\impl\ValidatableAdapter;
use n2n\l10n\Lstr;

abstract class BindableAdapter extends ValidatableAdapter implements Bindable {
	private bool $origExist;
	private bool $dirty = false;
	private bool $logical = false;

	function __construct(AttributePath $name, string|Lstr $label = null, private bool $exist = true) {
		parent::__construct($name, $label);

		$this->origExist = $this->exist;
	}


	function doesExist(): bool {
		return $this->exist;
	}

	function setExist(bool $exist): static {
		$this->exist = $exist;
		return $this;
	}

	function reset(): void {
		parent::reset();

		$this->exist = $this->origExist;
	}

	function isValid(): bool {
		return !$this->dirty && parent::isValid();
	}

	function isDirty(): bool {
		return $this->dirty;
	}

	function setDirty(bool $dirty): void {
		$this->dirty = $dirty;
	}

	function isLogical(): bool {
		return $this->logical;
	}

	function setLogical(bool $logical): static {
		$this->logical = $logical;
		return $this;
	}
}