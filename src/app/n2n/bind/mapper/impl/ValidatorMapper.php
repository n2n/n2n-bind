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

use n2n\validation\validator\Validator;
use n2n\bind\plan\BindBoundary;
use n2n\util\magic\MagicContext;
use n2n\bind\plan\BindContext;
use n2n\bind\mapper\Mapper;
use n2n\bind\mapper\MapperUtils;

class ValidatorMapper extends MultiMapperAdapter {

	function __construct(private Validator $validator) {
		parent::__construct(spreadDirtyState: false);
	}

	function mapMulti(array $bindables, BindBoundary $bindBoundary, MagicContext $magicContext): bool {
		MapperUtils::validate($bindables, [$this->validator], $bindBoundary->getBindContext(),
				$magicContext);
		return true;
	}

	/**
	 * @param array<Validator|Mapper> $validators
	 * @return Mapper[]
	 */
	static function convertValidators(array $validators): array {
		foreach ($validators as $key => $validator) {
			if ($validator instanceof Validator) {
				$validators[$key] = new ValidatorMapper($validator);
			}
		}
		return $validators;
	}
}