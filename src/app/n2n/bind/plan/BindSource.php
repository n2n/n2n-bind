<?php
/*
 * Copyright (c) 2012-2016, Hofmänner New Media.
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This file is part of the N2N FRAMEWORK.
 *
 * The N2N FRAMEWORK is free software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * N2N is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details: http://www.gnu.org/licenses/
 *
 * The following people participated in this project:
 *
 * Andreas von Burg.....: Architect, Lead Developer
 * Bert Hofmänner.......: Idea, Frontend UI, Community Leader, Marketing
 * Thomas Günther.......: Developer, Hangar
 */
namespace n2n\bind\plan;

use n2n\validation\plan\ErrorMap;
use n2n\bind\err\UnresolvableBindableException;
use n2n\util\type\attrs\AttributePath;
use n2n\l10n\Message;

interface BindSource {

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

	function addGeneralError(Message $message): void;

	/**
	 * @return ErrorMap
	 */
	function createErrorMap(): ErrorMap;


	/**
	 * @param AttributePath $contextPath
	 * @param string|null $expression if null and $contextPath is empty this method should return a Bindable representing the root.
	 * @param bool $mustExist
	 * @return Bindable[]
	 * @throws UnresolvableBindableException only if $mustExist is true
	 */
	function acquireBindables(AttributePath $contextPath, ?string $expression, bool $mustExist): array;

	/**
	 * @param AttributePath $path
	 * @param bool $mustExist
	 * @return Bindable|null can only be null if $mustExist is false.
	 * @throws UnresolvableBindableException
	 * /
	 */
	function acquireBindable(AttributePath $path, bool $mustExist): ?Bindable;



}