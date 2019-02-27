<?php
namespace n2n\bind\type;

use n2n\bind\Bindable;
use n2n\reflection\magic\MagicMethodInvoker;
use n2n\bind\marshal\MarshalComposer;
use n2n\util\type\TypeConstraints;
use n2n\util\magic\MagicContext;

class BindableInvoker {
	const MARSHAL_METHOD = '_marshal';
	const UNMARSHAL_METHOD = '_unmarshal';
	
	private $magicContext;
	
	/**
	 * @param MagicContext $magicContext
	 */
	function __construct(MagicContext $magicContext) {
		$this->magicContext = $magicContext;
	}
	
	/**
	 * @param Bindable $bindable
	 * @param TypeSafeModel $tsModel
	 * @return \n2n\bind\type\MarshalResult
	 */
	function invokeMarshal(Bindable $bindable, TypeSafeModel $tsModel) {
		$mmi = new MagicMethodInvoker($this->magicContext);
		
		$class = $tsModel->getClass();
		$marshalComposer = new MarshalComposer($tsModel);
		$method = null;
		try {
			$method = $class->getMethod(self::MARSHAL_METHOD);
			$method->setAccessible(true);
		} catch (\ReflectionException $e) {
			$marshalComposer->autoProps();
			return new MarshalResult($marshalComposer, null);
		}
		
		$mmi->setClassParamObject(MarshalComposer::class, $marshalComposer);
		$mmi->setMethod($method);
		$mmi->setReturnTypeConstraint(TypeConstraints::array(true));
		$returnArr = $mmi->invoke($bindable);
		
		return new MarshalResult($marshalComposer, $returnArr);
	}
	
	/**
	 * @param MarshalComposer $marshalComposer
	 * @param Bindable $bindable
	 * @return array
	 */
	function executeMarshalComposer(MarshalComposer $marshalComposer, Bindable $bindable) {
		return $marshalComposer->execute($bindable, $this->magicContext);
	}
}

class MarshalResult {
	/**
	 * @var MarshalComposer
	 */
	private $marshalComposer;
	/**
	 * @var array|null
	 */
	private $returnedArray;
	
	/**
	 * @param MarshalComposer $marshalComposer
	 * @param array $returnedArray
	 */
	function __construct(MarshalComposer $marshalComposer, ?array $returnedArray) {
		$this->marshalComposer = $marshalComposer;
		$this->returnedArray = $returnedArray;
	}
	
	/**
	 * @return \n2n\bind\marshal\MarshalComposer
	 */
	function getMarshalComposer() {
		return $this->marshalComposer;
	}
	
	/**
	 * @return array|null
	 */
	function getReturnedArray() {
		return $this->returnedArray;
	}
	
	
	
}