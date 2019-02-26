<?php
namespace n2n\bind\marshal;

use n2n\reflection\ReflectionUtils;
use n2n\bind\type\TypeSafeForge;
use n2n\core\container\N2nContext;
use n2n\bind\Bindable;
use n2n\bind\map\Mapper;
use n2n\util\type\attrs\AttributePath;
use n2n\bind\type\TypeSafeModel;
use n2n\reflection\magic\MagicMethodInvoker;

class MarshalPlan {
	private $tsf;
	private $attributePaths = [];
	private $mappers = [];
	
	private $activeAttributePaths = [];
	
	private function __construct(Bindable $rootBindable) {
		$class = ReflectionUtils::createReflectionClass(get_class($rootBindable));
		
		$this->tsf = new TypeSafeForge(false);
		
	}
	
	/**
	 * @param AttributePath|string ...$attributePaths
	 * @return \n2n\bind\marshal\MarshalPlan
	 */
	function prop(...$attributePaths) {
		$this->activeAttributePaths = [];
		foreach ($attributePaths as $attributePath) {
			$str = (string) $attributePath;
			$this->activeAttributePaths[$str] = $this->attributePaths[$str] = $attributePath;
		}
		
		return $this;
	}
	
	/**
	 * @param Mapper $mapper
	 * @return \n2n\bind\marshal\MarshalPlan
	 */
	function map(?Mapper $mapper) {
		foreach ($this->activeAttributePaths as $str => $attributePath) {
			$this->mappers[$str] = $mapper;
		}
		
		return $this;
	}
	
	function exec(N2nContext $n2nContext) {
		$this->asdf($this->rootBindable, $this->rootTsModel, 
				new AttributePathPool($this->activeAttributePaths));
		
	}
	
	
	private function asdf(Bindable $obj, TypeSafeModel $tsModel, AttributePathPool $pool) {
		
		$composer = new MarshalComposer($tsModel);
		
		
		$tsModel->createMar();
	}
	
	
}

class AttributePathPool {
	/**
	 * @var AttributePath[]
	 */
	private $attributePaths = [];
	private $pushedNum = 0;
	
	/**
	 * @param AttributePath[] $attributePaths
	 */
	function __construct($attributePaths) {
		$this->attributePaths = $attributePaths;
	}
	
	function push(string $name) {
		
	}
}


class MarshalTask {
	
	private $tsf;
	private $n2nContext;
	
	public function __construct(TypeSafeForge $tsf, N2nContext $n2nContext) {
		$this->tsf = $tsf;
		$this->n2nContext = $n2nContext;
	}
	
	public function exec(Bindable $rootObj) {
		$this->tsf->optainModel($rootObj);
	}
	
	
}