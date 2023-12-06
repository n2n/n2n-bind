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
	 * @return PropBindComposer
	 */
	function toAttrs(AttributeWriter $attributeWriter): PropBindComposer {
		return $this->to(new AttrsBindableTarget($attributeWriter));
	}

	/**
	 * @param array $array
	 * @return PropBindComposer
	 */
	function toArray(array &$array): PropBindComposer {
		return $this->to(new RefBindableTarget($array, true));
	}

	/**
	 * @param $value
	 * @return PropBindComposer
	 */
	function toValue(&$value): PropBindComposer {
		return $this->to(new RefBindableTarget($value, false));
	}

	/**
	 * @param object $obj
	 * @return PropBindComposer
	 */
	function toObj(object $obj): PropBindComposer {
		return $this->to(new ObjectBindableTarget($obj));
	}

	/**
	 * @param BindableTarget $target
	 * @return PropBindComposer
	 */
	function to(BindableTarget $target): PropBindComposer {
		return new PropBindTask($this->source, $target);
	}

}