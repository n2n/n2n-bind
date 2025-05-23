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
namespace n2n\bind\build\impl\target;

use n2n\bind\plan\BindTarget;
use n2n\bind\err\BindTargetException;
use n2n\util\type\ArgUtils;
use n2n\util\type\attrs\AttributeWriter;

class ObjectBindTarget implements BindTarget {

	function __construct(private object $objOrCallback) {
	}

	/**
	 * @throws BindTargetException
	 */
	function write(array $bindables): object {
		if ($this->objOrCallback instanceof \Closure) {
			$obj = $this->objOrCallback->__invoke();
			ArgUtils::valTypeReturn($obj, 'object', null, $this->objOrCallback);
		} else {
			$obj = $this->objOrCallback;
		}
		$objectBindableWriteProcess = new ObjectBindableWriteProcess($bindables);
		$objectBindableWriteProcess->process($obj);
		return $obj;
	}
}