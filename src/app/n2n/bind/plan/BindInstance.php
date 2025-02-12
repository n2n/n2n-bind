<?php

namespace n2n\bind\plan;

use n2n\util\type\attrs\AttributePath;
use n2n\bind\err\UnresolvableBindableException;
use n2n\l10n\Message;
use n2n\validation\plan\ErrorMap;
use n2n\bind\err\BindMismatchException;

interface BindInstance {

	/**
	 * A new bind cycle begins. All errors of defined bindables should be removed
	 *
	 * @return void
	 */
	function reset(): void;


	/**
	 * @return Bindable[]
	 */
	function getBindables(): array;

	function getBindable(AttributePath $attributePath): ?Bindable;

	/**
	 * @param AttributePath $path
	 * @param bool $mustExist
	 * @return Bindable
	 * @throws UnresolvableBindableException will only be thrown if $mustExist is true
	 * @throws BindMismatchException if the Bindable is resolvable but somehow not accessible or a similar problem
	 * occurred.
	 */
	function createBindable(AttributePath $path, bool $mustExist): Bindable;

	function addGeneralError(Message $message): void;

//	function isValid(): bool;
	/**
	 * @return ErrorMap
	 */
	function createErrorMap(): ErrorMap;


	/**
	 * @param AttributePath $contextPath
	 * @param string|null $expression
	 * @return AttributePath[]
	 * @throws UnresolvableBindableException
	 */
	function resolvePaths(AttributePath $contextPath, ?string $expression): array;
}