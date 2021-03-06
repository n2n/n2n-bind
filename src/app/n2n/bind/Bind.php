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
namespace n2n\bind; 

use n2n\bind\marshal\MarshalPlan;
use n2n\util\type\attrs\DataMap;
use n2n\bind\marshal\UnmarshalComposer;
use n2n\util\type\attrs\AttributeReader;
use n2n\util\type\attrs\DataSet;

class Bind {
	
	/**
	 * @param Bindable $bindable
	 * @return \n2n\bind\marshal\MarshalPlan
	 */
	public function marshal(Bindable $bindable) {
		return new MarshalPlan($bindable);
	}
	
	/**
	 * @param DataMap $dataMap
	 * @param Bindable $bindable
	 * @return \n2n\bind\UnmarshalComposer
	 */
	static function unmarshalAttrs(AttributeReader $attributeReader, Bindable $bindable) {
		return new UnmarshalComposer($attributeReader, $bindable);
	}
	
	/**
	 * 
	 * @param array $data
	 * @param Bindable $bindable
	 */
	static function unmarshalArray(array $data, Bindable $bindable) {
		return self::unmarshalAttrs(new DataSet($data), $bindable);
	}
}
