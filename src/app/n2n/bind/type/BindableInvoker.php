<?php
namespace n2n\bind\type;

use n2n\core\container\N2nContext;
use n2n\bind\Bindable;
use n2n\reflection\ReflectionUtils;
use n2n\reflection\magic\MagicMethodInvoker;
use n2n\bind\marshal\MarshalComposer;
use n2n\util\type\TypeConstraints;

class BindableInvoker {
	const MARSHAL_METHOD = '_marshal';
	const UNMARSHAL_METHOD = '_unmarshal';
	
	private $n2nContext;
	
	function __construct(N2nContext $n2nContext) {
		
	}
	
	function invokeMarshal(Bindable $bindable, TypeSafeModel $tsModel) {
		$mmi = new MagicMethodInvoker($this->n2nContext);
		
		$class = $tsModel->getClass();
		$marshalComposer = new MarshalComposer($tsModel);
		$method = null;
		try {
			$method = $class->getMethod(self::MARSHAL_METHOD);
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
	
}