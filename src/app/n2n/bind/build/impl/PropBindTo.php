<?php

namespace n2n\bind\build\impl;

use n2n\bind\build\impl\compose\prop\PropBindComposerSource;
use n2n\bind\plan\BindableTarget;
use n2n\bind\build\impl\compose\prop\PropBindComposer;
use n2n\util\type\attrs\AttributeWriter;
use n2n\bind\build\impl\target\RefBindableTarget;
use n2n\bind\build\impl\target\AttrsBindableTarget;

class PropBindTo {

	function __construct(private PropBindComposerSource $source) {
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
	 * @param BindableTarget $target
	 * @return PropBindComposer
	 */
	function to(BindableTarget $target): PropBindComposer {
		return new PropBindComposer($this->source, $target);
	}

}