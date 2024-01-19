<?php

namespace n2n\bind\build\impl;

use n2n\bind\plan\BindTarget;
use n2n\bind\build\impl\compose\prop\PropBindComposer;
use n2n\util\type\attrs\AttributeWriter;
use n2n\bind\build\impl\target\RefBindTarget;
use n2n\bind\build\impl\target\AttrsBindTarget;
use n2n\bind\build\impl\target\ObjectBindTarget;
use n2n\bind\plan\BindSource;
use n2n\bind\build\impl\compose\prop\PropBindTask;

class PropBindTo {

	function __construct(private BindSource $source) {
	}

	/**
	 * @param AttributeWriter $attributeWriter
	 * @return PropBindTask
	 */
	function toAttrs(AttributeWriter $attributeWriter): PropBindTask {
		return $this->to(new AttrsBindTarget($attributeWriter));
	}

	/**
	 * @param array $array
	 * @return PropBindTask
	 */
	function toArray(array &$array): PropBindTask {
		return $this->to(new RefBindTarget($array, true));
	}

	/**
	 * @param $value
	 * @return PropBindTask
	 */
	function toValue(&$value): PropBindTask {
		return $this->to(new RefBindTarget($value, false));
	}

	/**
	 * @param object $obj
	 * @return PropBindTask
	 */
	function toObj(object $obj): PropBindTask {
		return $this->to(new ObjectBindTarget($obj));
	}

	/**
	 * @param BindTarget $target
	 * @return PropBindTask
	 */
	function to(BindTarget $target): PropBindTask {
		return new PropBindTask($this->source, $target);
	}

}