<?php
namespace n2n\bind\marshal;

use n2n\bind\type\TypeSafeModel;
use n2n\bind\map\Mapper;
use n2n\util\type\attrs\AttributeReader;
use n2n\util\magic\MagicContext;
use n2n\web\dispatch\map\val\Validator;

class UnmarshalComposer {
	
	/**
	 * @param TypeSafeModel $tsModel
	 */
	function __construct(AttributeReader $attributeReader, TypeSafeModel $tsModel) {
		$this->tsModel = $tsModel;
	}
	
	/**
	 * @param string ...$props
	 * @return \n2n\bind\marshal\UnmarshalComposer
	 */
	function prop(string ...$props) {
		
		return $this;
	}
	
	/**
	 * @param Mapper ...$mappers
	 * @return \n2n\bind\marshal\UnmarshalComposer
	 */
	function map(Mapper ...$mappers) {
		
		return $this;
	}
	
	/**
	 * @param Validator ...$validators
	 * @return \n2n\bind\marshal\UnmarshalComposer
	 */
	function val(Validator ...$validators) {
		
		return $this;
	}
	
	function exec(MagicContext $magicContext) {
		
	}
}

class UnmarshalMappingJob {
	
	function 
}