<?php

namespace n2n\bind\build\impl;

use n2n\util\type\attrs\AttributeWriter;
use n2n\bind\build\impl\compose\union\UnionBindComposer;
use n2n\bind\build\impl\target\AttrsBindTarget;
use n2n\bind\build\impl\target\RefBindTarget;
use n2n\bind\plan\BindTarget;
use n2n\bind\build\impl\target\ObjectBindTarget;
use n2n\bind\build\impl\target\ClosureBindTarget;
use n2n\bind\plan\BindSource;

class UnionBindTo {

	function __construct(private BindSource $source) {
	}

	/**
	 * @param AttributeWriter $attributeWriter
	 * @return UnionBindComposer
	 */
	function toAttrs(AttributeWriter $attributeWriter): UnionBindComposer {
		return $this->to(new AttrsBindTarget($attributeWriter));
	}

	/**
	 * @param array $array
	 * @return UnionBindComposer
	 */
	function toArray(array &$array): UnionBindComposer {
		return $this->to(new RefBindTarget($array, true));
	}

	/**
	 * @param $value
	 * @return UnionBindComposer
	 */
	function toValue(&$value): UnionBindComposer {
		return $this->to(new RefBindTarget($value, false));
	}

	function toClosure(\Closure $closure): UnionBindComposer {
		return $this->to(new ClosureBindTarget($closure));
	}

	/**
	 * @param object $obj
	 * @return UnionBindComposer
	 */
	function toObj(object $obj): UnionBindComposer {
		return $this->to(new ObjectBindTarget($obj));
	}


	/**
	 * @param BindTarget $target
	 * @return UnionBindComposer
	 */
	function to(BindTarget $target): UnionBindComposer {
		return new UnionBindComposer($this->source, $target);
	}

}