<?php 
namespace n2n\bind\mapper;

use n2n\util\magic\MagicContext;
use n2n\bind\plan\BindBoundary;
use n2n\bind\plan\BindContext;
use n2n\bind\err\BindMismatchException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\plan\Bindable;

interface Mapper  {

	/**
	 * Action method of the Mapper. It performs modifications on the passed Bindables ({@link BindBoundary::getBindables()}).
	 *
	 * If errors occur it must one or more of the following actions can be applied:
	 *  - Add errors to the affected Bindables (see {@link Bindable::addError()}).
	 * 	- Mark affected Bindables as dirty (see {@link Bindable::setDirty()}).
	 *  - Abort the whole bind process by returning false or throwing a BindMismatchException.
	 *
	 * @param BindBoundary $bindBoundary
	 * @param MagicContext $magicContext
	 * @return MapResult
	 * @throws BindMismatchException if the input value is not compatible with the Mapper
	 * @throws UnresolvableBindableException
	 */
	function map(BindBoundary $bindBoundary, MagicContext $magicContext): MapResult;

}