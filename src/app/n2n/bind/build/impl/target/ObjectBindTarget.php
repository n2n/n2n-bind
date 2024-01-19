<?php
namespace n2n\bind\build\impl\target;

use n2n\bind\plan\BindTarget;
use n2n\util\type\ArgUtils;
use n2n\bind\plan\Bindable;
use n2n\reflection\property\PropertiesAnalyzer;
use n2n\bind\err\BindTargetException;

class ObjectBindTarget implements BindTarget {
	private object $obj;

	function __construct(object $obj) {
		$this->obj = $obj;
	}

	/**
	 * @throws BindTargetException
	 */
	function write(array $bindables): object {
		$objectBindableWriteProcess = new ObjectBindableWriteProcess($bindables);
		$objectBindableWriteProcess->process($this->obj);
		return $this->obj;
	}
}