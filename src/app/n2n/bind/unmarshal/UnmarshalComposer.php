<?php
namespace n2n\bind\marshal;

use n2n\bind\type\TypeSafeModel;
use n2n\bind\map\Mapper;
use n2n\util\type\attrs\AttributeReader;
use n2n\util\magic\MagicContext;
use n2n\reflection\property\AccessProxy;
use n2n\util\type\attrs\DataSet;
use n2n\validation\plan\Validator;
use n2n\validation\build\impl\Validate;

class UnmarshalComposer {
	private $attributeReader;
	private $tsModel;
	/**
	 * @var AccessProxy[]
	 */
	private $activeAccessProxies = [];
	
	/**
	 * @param TypeSafeModel $tsModel
	 */
	function __construct(AttributeReader $attributeReader, TypeSafeModel $tsModel) {
		$this->attributeReader = $attributeReader;
		$this->tsModel = $tsModel;
	}
	
	/**
	 * @param string ...$props
	 * @return \n2n\bind\marshal\UnmarshalComposer
	 */
	function prop(string ...$names) {
		$this->activeAccessProxies = [];
		foreach ($names as $name) {
			$this->activeAccessProxies[$name] = $this->tsModel->obtainAccessProxy($name, false);
		}
		
		return $this;
	}
	
	/**
	 * @param Mapper ...$mappers
	 * @return \n2n\bind\marshal\UnmarshalComposer
	 */
	function map(Mapper ...$mappers) {
		foreach ($this->activeAccessProxies as $name => $aap) {
			foreach ($mappers as $mapper) {
				
			}
		}
		
		return $this;
	}
	
	/**
	 * @param Validator ...$validators
	 * @return \n2n\bind\marshal\UnmarshalComposer
	 */
	function val(Validator ...$validators) {
		if ($this->activeAccessProxies) {
			Validate::attrs($this->attributeReader)
		}
		
		->props(array_keys($this->activeAccessProxies)), $validators);
		
		foreach ($this->activeAccessProxies as $name => $aap) {
			foreach ($validators as $validator) {
				 
			}
		}
		return $this;
	}
	
	function exec(MagicContext $magicContext) {
		$ds = new DataSet();
		foreach ($this->tsModel->getAccessProxies() as $name => $accessProxy) {
			$ds->set($name, $accessProxy->getValue($this->bindable));
		}
		
		
	}
}

class UnmarshalMappingTask {
	private $mappers = [];
	
	function addMapper(string $name, Mapper $mapper) {
		if (isset($this->mappers[$name])) {
			$this->mappers[$name] = [];
		}
		
		$this->mappers[$name][] = $mapper;
	}
}

