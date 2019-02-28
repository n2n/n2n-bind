<?php
namespace n2n\bind\marshal;

use n2n\bind\type\BindableInvoker;
use n2n\bind\type\TypeSafeForge;
use n2n\bind\Bindable;
use n2n\util\type\TypeUtils;

class MarshalTask {
	private $invoker;
	private $tsf;
	
	/**
	 * @param BindableInvoker $bindableInvoker
	 */
	function __construct(BindableInvoker $bindableInvoker) {
		$this->invoker = $bindableInvoker;
		$this->tsf = new TypeSafeForge(false);
	}
	
	/**
	 * @param Bindable $bindable
	 * @param InterceptorPool $pool
	 * @return array
	 */
	function processBindable(Bindable $bindable, InterceptorPool $pool) {
		$class = new \ReflectionClass($bindable);
		$tsModel = $this->tsf->optainModel($class);
		$marshalResult = $this->invoker->invokeMarshal($bindable, $tsModel);
		
		$marshalComposer = $marshalResult->getMarshalComposer();
		$pool->intercept($marshalComposer);
		
		$array = $this->invoker->executeMarshalComposer($marshalComposer, $bindable);
		if (null !== ($returnedArray = $marshalResult->getReturnedArray())) {
			$array = array_merge($returnedArray, $array);
		}
		
		return $this->processsArray($array, $pool);
	}
	
	/**
	 * @param array $array
	 * @param InterceptorPool $pool
	 * @return array
	 */
	private function processsArray(array $array, InterceptorPool $pool) {
		foreach ($array as $key => $value) {
			if ($value instanceof Bindable) {
				$array[$key] = $this->processBindable($value, $pool->push($key));
				continue;
			}
			
			if ($value === null) {
				continue;
			}
			
			$pushedPool = $pool->push($key);
			$pushedPool->assertNotBindable(TypeUtils::getTypeInfo($value));
			
			if (is_array($value)) {
				$array[$key] = $this->processsArray($value, $pushedPool);
				continue;
			}
			
			if ($value instanceof \ArrayObject) {
				$array[$key] = $this->processsArray($value->getArrayCopy(), $pushedPool);
				continue;
			}
		}
		
		return $array;
	}
}