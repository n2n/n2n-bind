<?php
namespace n2n\bind\marshal;

use n2n\util\type\attrs\AttributePath;
use n2n\bind\type\BindingFailedExcpetion;
use n2n\util\ex\IllegalStateException;
use n2n\bind\Bindable;

class InterceptorPool {
	/**
	 * @var \ArrayObject
	 */
	private $mapperMap;
	/**
	 * @var AttributePath[]
	 */
	private $attributePaths = [];
	/**
	 * @var int
	 */
	private $pushedNum;
	
	/**
	 * @param \ArrayObject $mapperArgMap
	 * @param AttributePath[] $attributePaths
	 * @param int $pushedNum
	 */
	function __construct(\ArrayObject $mapperArgMap, array $attributePaths, int $pushedNum = 0) {
		$this->mapperMap = $mapperArgMap;
		$this->attributePaths = $attributePaths;
		$this->pushedNum = $pushedNum;
	}
	
	/**
	 * @param string $name
	 * @return \n2n\bind\marshal\InterceptorPool
	 */
	function push(string $name) {
		$attributePaths = [];
		$pushedDepth = $this->pushedNum + 1;
		
		foreach ($this->attributePaths as $apStr => $attributePath) {
			$parts = $attributePath->toArray();
			
			if (!isset($parts[$this->pushedNum])
					|| count($parts) == $pushedDepth
					|| !AttributePath::match($parts[$this->pushedNum], $name)) {
				continue;
			}
			
			$attributePaths[$apStr] = $attributePath;
		}
		
		return new InterceptorPool($this->mapperMap, $attributePaths, $this->pushedNum + 1);
	}
	
	/**
	 * @param string $typeName
	 * @throws BindingFailedExcpetion
	 */
	function assertNotBindable(string $typeName) {
		$assertNum = $this->pushedNum + 1;
		foreach ($this->attributePaths as $attributePath) {
			if ($assertNum != $attributePath->size()) {
				continue;
			}
			
			throw new BindingFailedExcpetion('Could not resolve attribute path ' . $attributePath 
					. '. Reason: ' . $attributePath->slices(0, $this->pushedNum) . ' is not of type ' 
					. Bindable::class . ' but ' . $typeName . '.');
		}
	}
	
	/**
	 * @param MarshalComposer $marshalComposer
	 * @throws BindingFailedExcpetion
	 */
	function intercept(MarshalComposer $marshalComposer) {
		$pushedDepth = $this->pushedNum + 1;
		
		foreach ($this->attributePaths as $apStr => $attributePath) {
			$pathParts = $attributePath->toArray();
			
			IllegalStateException::assertTrue(isset($pathParts[$this->pushedNum]));
			
			$name = $pathParts[$this->pushedNum];
			
			if (AttributePath::matchesWildcard($name)) {
				$marshalComposer->curProps();	
			} else {
				try {
					$marshalComposer->prop($name);
				} catch (BindingFailedExcpetion $e) {
					throw new BindingFailedExcpetion('Could not resolve attribute path ' . $attributePath 
							. '. Reason: ' . $e->getMessage());
				}
			}
			
			if ($pushedDepth == count($pathParts) && $this->mapperMap->offsetExists($apStr)) {
				try {
					$marshalComposer->map($this->mapperMap->offsetGet($apStr));
				} catch (BindingFailedExcpetion $e) {
					throw new BindingFailedExcpetion('Could not place mapper at ' . $attributePath . '. Reason: '
							. $e->getMessage());
				}
			}
		}
	}
}

