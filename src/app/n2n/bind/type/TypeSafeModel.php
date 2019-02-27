<?php
namespace n2n\bind\type;

use n2n\util\type\TypeConstraint;
use n2n\reflection\property\PropertiesAnalyzer;
use n2n\reflection\property\AccessProxy;
use n2n\reflection\property\ConstraintsConflictException;
use n2n\reflection\property\annotation\AnnoType;
use n2n\reflection\property\annotation\AnnoPropTypes;

class TypeSafeModel {
	/**
	 * @var PropertiesAnalyzer
	 */
	private $analyzer;
	/**
	 * @var bool
	 */
	private $writable;
	/**
	 * @var AccessProxy[]
	 */
	private $accessProxies = [];
	/**
	 * @var AccessProxy[]
	 */
	private $defaultAccessProxies = null; 
	
	/**
	 * @param \ReflectionClass $class
	 * @param bool $writable
	 */
	function __construct(\ReflectionClass $class, bool $writable) {
		$this->analyzer = new PropertiesAnalyzer($class, false, false);
		$this->writable = $writable;
	}
	
	/**
	 * @return \ReflectionClass
	 */
	function getClass() {
		return $this->analyzer->getClass();
	}
	
	/**
	 * @param string $name
	 * @param TypeConstraint $overTypeConstraint
	 * @return AccessProxy
	 * @throws \ReflectionException
	 */
	function obtainAccessProxy(string $name, bool $typeSafe) {
		$accessProxy = null;
		
		if (!isset($this->accessProxies[$name])) {
			$accessProxy = $this->analyzer->analyzeProperty($name, $this->writable);
		} else {
			$accessProxy = $this->accessProxies[$name];
		}
		
		$typeConstraint = $accessProxy->getConstraint();
		if ($typeSafe && (null === $typeConstraint || !$typeConstraint->isTypeSafe())) {
			throw new ConstraintsConflictException('Property must be typesafe but ' . $accessProxy 
					. ' isn\'t. Use ' . AnnoType::class . ' or ' . AnnoPropTypes::class . ' if you can\'t provide'
					. ' an exact type with the setter method.');
		}
		
		return $this->accessProxies[$name] = $accessProxy;	
	}
	
	/**
	 * @param string $name
	 * @throws \ReflectionException
	 */
	function obtainAccessProxies() {
		if ($this->defaultAccessProxies !== null) {
			return $this->defaultAccessProxies;
		}
		
		$this->defaultAccessProxies = $this->analyzer->analyzeProperties(true, false);
		foreach ($this->defaultAccessProxies as $name => $accessProxy) {
			$this->accessProxies[$name] = $accessProxy;
		}
		
		return $this->defaultAccessProxies;
	}
	
	/**
	 * @return \n2n\reflection\property\AccessProxy[]
	 */
	function getAccessProxies() {
		return $this->accessProxies;
	}
}
