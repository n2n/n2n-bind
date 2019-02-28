<?php
namespace n2n\bind\marshal;

use n2n\bind\type\TypeSafeModel;
use n2n\reflection\property\AccessProxy;
use n2n\bind\map\Mapper;
use n2n\bind\type\BindingFailedExcpetion;
use n2n\util\magic\MagicContext;
use n2n\bind\Bindable;
use n2n\bind\map\impl\MarshalClosureMapper;
use n2n\util\type\ArgUtils;

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
	 * @throws BindingFailedExcpetion
	 */
	function prop(string ...$names) {
		$this->activeAccessProxies = [];
		foreach ($names as $name) {
			$this->bindProp($name);
		}
		
		return $this;
	}
	
	/**
	 * @return \n2n\bind\marshal\MarshalComposer
	 */
	function autoProps() {
		$this->activeAccessProxies = [];
		
		foreach ($this->tsModel->obtainAccessProxies() as $name => $accessProxy) {
			if (isset($this->accessProxies[$name]) || null === $accessProxy->getConstraint()) {
				continue;
			}
			
			if (AutoMarshalMapper::testCompatibility($accessProxy->getConstraint())) {
				$this->bindProp($name);		
				$this->mappers[$name] = new AutoMarshalMapper();
			}
		}
		
		return $this;
	}
	
	private function bindProp($name) {
		try {
			$this->activeAccessProxies[$name] = $this->accessProxies[$name]
					= $this->tsModel->obtainAccessProxy($name, false);
		} catch (\ReflectionException $e) {
			throw new BindingFailedExcpetion($e->getMessage());
		}
	}
	
	/**
	 * @return \n2n\bind\marshal\MarshalComposer
	 */
	function curProps() {
		$this->activeAccessProxies = $this->accessProxies;
		return $this;
	}
	
	/**
	 * @param Mapper|\Closure|null $mapper
	 * @throws BindingFailedExcpetion
	 * @return \n2n\bind\marshal\MarshalComposer
	 */
	function map($mapper) {
		ArgUtils::valType($mapper, [Mapper::class, \Closure::class], true, 'mapper');
		
		if ($mapper instanceof Mapper) {
			$this->applyMapper($mapper);
			return $this;
		}
			
		if ($mapper instanceof \Closure) {
			$this->applyMapperClosure($mapper);
			return $this;
		}
		
		foreach ($this->activeAccessProxies as $name => $accessProxy) {
			$this->mappers[$name] = null;
		}
		
		return $this;
	}	
	
	private function applyMapper(Mapper $mapper) {
		foreach ($this->activeAccessProxies as $name => $accessProxy) {
			if (null !== $accessProxy->getConstraint() && null !== $mapper->getTypeConstraint()
					&& !$accessProxy->getConstraint()->isPassableBy($mapper->getTypeConstraint())) {
				throw new BindingFailedExcpetion('Mapper' . get_class($mapper)
						. ' (TypeConstraint: ' . $mapper->getTypeConstraint()
						. ') is not compatible with ' . $accessProxy);
			}
			
			$this->mappers[$name] = $mapper;
		}
	}
	
	/**
	 * @param \Closure $closure
	 */
	private function applyMapperClosure(\Closure $closure) {
		foreach ($this->activeAccessProxies as $name => $accessProxy) {
			$typeName = null;
			if (null !== ($typeConstraint = $accessProxy->getConstraint())) {
				$typeName = $typeConstraint->getTypeName();
			}
			
			$this->mappers[$name] = new MarshalClosureMapper($closure, $typeName);
		}
	}
	
	/**
	 * @param Bindable $bindable
	 * @param MagicContext $magicContext
	 * @return array
	 * @throws \n2n\reflection\ReflectionException
	 */
	function execute(Bindable $bindable, MagicContext $magicContext) {
		$array = [];
		
		foreach ($this->accessProxies as $name => $accessProxy) {
			$accessProxy->setNullReturnAllowed(true);
			$value = $accessProxy->getValue($bindable);
			
			if (isset($this->mappers[$name])) {
				$value = $this->mappers[$name]->marshal($value, $magicContext);
			}
				
			$array[$name] = $value;
		}
		
		return $array;
	}
}
