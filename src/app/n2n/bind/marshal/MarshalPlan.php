<?php
namespace n2n\bind\marshal;

use n2n\core\container\N2nContext;
use n2n\bind\Bindable;
use n2n\bind\map\Mapper;
use n2n\util\type\attrs\AttributePath;
use n2n\bind\type\BindableInvoker;
use n2n\util\magic\MagicContext;
use n2n\util\magic\MagicArray;
use n2n\util\type\ArgUtils;

class MarshalPlan implements MagicArray {
	private $rootBindable;
	private $attributePaths = [];
	private $mapperArgs;
	
	private $activeAttributePaths = [];
	private $invoker;
	
	/**
	 * @param Bindable $rootBindable
	 */
	function __construct(Bindable $rootBindable) {
		$this->mapperArgs = array();
		$this->rootBindable = $rootBindable;
	}
	
	/**
	 * @param AttributePath|string ...$attributePaths
	 * @return \n2n\bind\marshal\MarshalPlan
	 */
	function prop(...$attributePaths) {
		$this->activeAttributePaths = [];
		foreach ($attributePaths as $attributePath) {
			$attributePath  = AttributePath::create($attributePath);
			$str = (string) $attributePath;
			$this->activeAttributePaths[$str] = $this->attributePaths[$str] = $attributePath;
		}
		
		return $this;
	}
	
	/**
	 * @param Mapper|\Closure|null $mapper
	 * @return \n2n\bind\marshal\MarshalPlan
	 */
	function map($mapper) {
		ArgUtils::valType($mapper, [Mapper::class, \Closure::class], true, 'mapper');
		
		foreach ($this->activeAttributePaths as $str => $attributePath) {
			$this->mapperArgs[$str] = $mapper;
		}
		
		return $this;
	}
	
	/**
	 * @param N2nContext $n2nContext
	 * @return array
	 */
	function toArray(MagicContext $n2nContext): array {
		$marshalTask = new MarshalTask(new BindableInvoker($n2nContext));
		
		return $marshalTask->processBindable($this->rootBindable,  
				new InterceptorPool(new \ArrayObject($this->mapperArgs), $this->activeAttributePaths));
	}
}
