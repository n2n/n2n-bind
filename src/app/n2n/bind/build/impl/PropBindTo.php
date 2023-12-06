<?php

namespace n2n\bind\build\impl;

use n2n\bind\plan\BindableTarget;
use n2n\bind\build\impl\compose\prop\PropBindComposer;
use n2n\util\type\attrs\AttributeWriter;
use n2n\bind\build\impl\target\RefBindableTarget;
use n2n\bind\build\impl\target\AttrsBindableTarget;
use n2n\bind\build\impl\target\ObjectBindableTarget;
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
		return $this->to(new AttrsBindableTarget($attributeWriter));
	}

	/**
	 * @param array $array
	 * @return PropBindTask
	 */
	function toArray(array &$array): PropBindTask {
		return $this->to(new RefBindableTarget($array, true));
	}

	/**
	 * @param $value
	 * @return PropBindTask
	 */
	function toValue(&$value): PropBindTask {
		return $this->to(new RefBindableTarget($value, false));
	}

	/**
	 * @param object $obj
	 * @return PropBindTask
	 */
	function toObj(object $obj): PropBindTask {
		return $this->to(new ObjectBindableTarget($obj));
	}

	/**
	 * @param BindableTarget $target
	 * @return PropBindTask
	 */
	function to(BindableTarget $target): PropBindTask {
		return new PropBindTask($this->source, $target);
	}

}