<?php
namespace n2n\bind\mapper\impl;

use n2n\bind\mapper\Mapper;
use n2n\util\type\TypeConstraint;
use n2n\util\magic\MagicContext;
use n2n\reflection\magic\MagicMethodInvoker;
use n2n\util\ex\UnsupportedOperationException;

class MarshalClosureMapper implements Mapper {
	private $closure;
	private $paramTypeName;
	
	function __construct(\Closure $closure, ?string $paramTypeName) {
		$this->closure = $closure;
		$this->paramTypeName = $paramTypeName;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \n2n\bind\mapper\Mapper::unmarshal()
	 */
	public function unmarshal($value, MagicContext $magicContext) {
		throw new UnsupportedOperationException();
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\bind\mapper\Mapper::marshal()
	 */
	public function marshal($value, MagicContext $magicContext) {
		$mmi = new MagicMethodInvoker($magicContext);
		$mmi->setMethod(new \ReflectionFunction($this->closure));
		$mmi->setParamValue('value', $value);
		
		if ($this->paramTypeName !== null){
			$mmi->setClassParamObject($this->paramTypeName, $value);
		}
		
		return $mmi->invoke();
	}

	/**
	 * {@inheritDoc}
	 * @see \n2n\bind\mapper\Mapper::getTypeConstraint()
	 */
	public function getTypeConstraint(): ?TypeConstraint {
		return null;
	}
}