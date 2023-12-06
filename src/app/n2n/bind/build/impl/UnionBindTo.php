<?php

namespace n2n\bind\build\impl;

use n2n\util\type\attrs\AttributeWriter;
use n2n\bind\build\impl\compose\union\UnionBindComposer;
use n2n\bind\build\impl\target\AttrsBindableTarget;
use n2n\bind\build\impl\target\RefBindableTarget;
use n2n\bind\plan\BindableTarget;
use n2n\bind\build\impl\target\ObjectBindableTarget;
use n2n\bind\build\impl\target\ClosureBindableTarget;
use n2n\bind\plan\BindSource;

class UnionBindTo {

	function __construct(private BindSource $source) {
	}

	/**
	 * @param AttributeWriter $attributeWriter
	 * @return UnionBindComposer
	 */
	function toAttrs(AttributeWriter $attributeWriter): UnionBindComposer {
		return $this->to(new AttrsBindableTarget($attributeWriter));
	}

	/**
	 * @param array $array
	 * @return UnionBindComposer
	 */
	function toArray(array &$array): UnionBindComposer {
		return $this->to(new RefBindableTarget($array, true));
	}

	/**
	 * @param $value
	 * @return UnionBindComposer
	 */
	function toValue(&$value): UnionBindComposer {
		return $this->to(new RefBindableTarget($value, false));
	}

	function toClosure(\Closure $closure): UnionBindComposer {
		return $this->to(new ClosureBindableTarget($closure));
	}

	/**
	 * @param object $obj
	 * @return UnionBindComposer
	 */
	function toObj(object $obj): UnionBindComposer {
		return $this->to(new ObjectBindableTarget($obj));
	}


	/**
	 * @param BindableTarget $target
	 * @return UnionBindComposer
	 */
	function to(BindableTarget $target): UnionBindComposer {
		return new UnionBindComposer($this->source, $target);
	}

}