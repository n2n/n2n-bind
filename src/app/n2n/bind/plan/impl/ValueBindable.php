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

class ValueBindable extends BindableAdapter {
	private mixed $origValue;
	private bool $origDoesExist;

	private bool $logical = false;

	function __construct(AttributePath $name, private mixed $value, bool $doesExist, string $label = null) {
		parent::__construct($name, $label, $doesExist);

		$this->origValue = $this->value;
	}
	
	function getValue(): mixed {
		return $this->value;
	}

	function setValue(mixed $value): static {
		$this->value = $value;
		return $this;
	}

	function reset(): void {
		parent::reset();

		$this->value = $this->origValue;
	}

	function isLogical(): bool {
		return $this->logical;
	}

	function setLogical(bool $logical): static {
		$this->logical = $logical;
		return $this;
	}

}