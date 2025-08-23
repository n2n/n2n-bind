<?php

namespace n2n\bind\plan;

use n2n\util\type\attrs\AttributePath;
use n2n\bind\err\UnresolvableBindableException;
use n2n\l10n\Message;
use n2n\validation\plan\ErrorMap;
use n2n\bind\err\BindMismatchException;

interface BindableFactory {


	/**
	 * @return array<Bindable>
	 */
	function createInitialBindables(): array;

	/**
	 * @param AttributePath $path
	 * @param bool $mustExist
	 * @return Bindable
	 * @throws UnresolvableBindableException will only be thrown if $mustExist is true
	 * @throws BindMismatchException if the Bindable is resolvable but somehow not accessible or a similar problem
	 * occurred.
	 */
	function createBindable(AttributePath $path, bool $mustExist): Bindable;

}