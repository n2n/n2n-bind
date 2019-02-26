<?php
namespace n2n\bind\marshal;

use n2n\bind\type\TypeSafeModel;
use n2n\reflection\property\AccessProxy;
use n2n\bind\map\Mapper;
use n2n\bind\type\InvalidBindableExcpetion;
use n2n\util\type\TypeConstraint;
use n2n\util\ex\UnsupportedOperationException;

class MarshalComposer {
	/**
	 * @var TypeSafeModel
	 */
	private $tsModel;
	/**
	 * @var AccessProxy[]
	 */
	private $accessProxies = [];
	/**
	 * @var Mapper[]
	 */
	private $mappers = [];
	/**
	 * @var AccessProxy[]
	 */
	private $activeAccessProxies = [];
	
	/**
	 * @param TypeSafeModel $tsModel
	 */
	function __construct(TypeSafeModel $tsModel) {
		$this->tsModel = $tsModel;
	}
	
	/**
	 * @param string ...$names
	 * @return \n2n\bind\marshal\MarshalComposer
	 */
	function prop(string ...$names) {
		$this->activeAccessProxies = [];
		foreach ($names as $name) {
			$this->activeAccessProxies[$name] = $this->accessProxy[$name] 
					= $this->tsModel->obtainAccessProxy($name, false);
		}
		
		return $this;
	}
	
	/**
	 * @return \n2n\bind\marshal\MarshalComposer
	 */
	function autoProps() {
		$this->activeAccessProxies = [];
		
		foreach ($this->tsModel->optionAutoAccessProxies() as $name => $accessProxy) {
			if (isset($this->accessProxies[$name]) || null === $accessProxy->getConstraint()) {
				continue;
			}
			
			if (AutoMarshalMapper::testCompatibility($accessProxy->getConstraint())) {
				$this->activeAccessProxies[$name] = $this->accessProxies[$name] = $accessProxy;
				$this->mappers[$name] = new AutoMarshalMapper();
			}
		}
		
		return $this;
	}
	
	/**
	 * @param Mapper $mapper
	 * @throws InvalidBindableExcpetion
	 * @return \n2n\bind\marshal\MarshalComposer
	 */
	function map(?Mapper $mapper) {
		foreach ($this->activeAccessProxies as $name => $accessProxy) {
			if ($mapper !== null && null !== $accessProxy->getConstraint() && null !== $mapper->getTypeConstraint()
					&& !$accessProxy->getConstraint()->isPassableBy($mapper->getTypeConstraint())) {
				throw new InvalidBindableExcpetion('Mapper' . get_class($mapper) 
						. ' (TypeConstraint: ' . $mapper->getTypeConstraint() 
						. ') is not compatible with ' . $accessProxy);
			}
			
			$this->mappers[$name] = $accessProxy;
		}
		
		return $this;
	}	
}
