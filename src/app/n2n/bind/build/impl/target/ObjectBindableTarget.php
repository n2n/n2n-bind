<?php
namespace n2n\bind\build\impl\target;

use n2n\bind\plan\BindableTarget;
use n2n\util\type\ArgUtils;
use n2n\bind\plan\Bindable;
use n2n\reflection\property\PropertiesAnalyzer;

class ObjectBindableTarget implements BindableTarget {
	private object $obj;
	private PropertiesAnalyzer $propertiesAnalyzer;

	function __construct(object $obj) {
		$this->obj = $obj;
		$this->propertiesAnalyzer = new PropertiesAnalyzer(new \ReflectionClass($obj));
	}

	function write(array $bindables): void {
		ArgUtils::valArray($bindables, Bindable::class);

		$objectBindableWriteProces = new ObjectBindableWriteProcess($bindables);
		$objectBindableWriteProces->process($this->obj);
	}
}