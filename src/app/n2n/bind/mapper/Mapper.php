<?php 
namespace n2n\bind\mapper;

use n2n\util\magic\MagicContext;
use n2n\bind\plan\BindableBoundary;
use n2n\bind\plan\BindContext;
use n2n\bind\err\BindMismatchException;

interface Mapper  {

	/**
	 * @param BindableBoundary $bindableBoundary
	 * @param BindContext $bindContext
	 * @param MagicContext $magicContext
	 * @return bool false if a Mapper could not perform a modification of value due to errors of the passed bindables.
	 * 	The bind process will be aborted in this case.
	 * @throws BindMismatchException
	 */
	function map(BindableBoundary $bindableBoundary, BindContext $bindContext, MagicContext $magicContext): bool;

}