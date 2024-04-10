<?php 
namespace n2n\bind\mapper;

use n2n\util\magic\MagicContext;
use n2n\bind\plan\BindBoundary;
use n2n\bind\plan\BindContext;
use n2n\bind\err\BindMismatchException;
use n2n\bind\err\UnresolvableBindableException;

interface Mapper  {

	/**
	 * @param BindBoundary $bindBoundary
	 * @param MagicContext $magicContext
	 * @return bool false if a Mapper could not perform a modification of value due to errors of the passed bindables.
	 *    The bind process will be aborted in this case.
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function map(BindBoundary $bindBoundary, MagicContext $magicContext): bool;

}