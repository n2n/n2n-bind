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
namespace n2n\bind\build\impl\source;

use n2n\l10n\Message;
use n2n\bind\plan\BindContext;
use n2n\bind\plan\BindSource;
use n2n\bind\plan\Bindable;
use n2n\validation\plan\ErrorMap;
use n2n\util\ex\IllegalStateException;
use n2n\util\type\attrs\AttributePath;
use n2n\util\type\ArgUtils;
use n2n\bind\build\impl\Bind;
use n2n\bind\plan\BindableFactory;

abstract class BindableFactoryAdapter implements BindableFactory {

	function createInitialBindables(): array {
		return [];
	}
}
