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
namespace n2n\bind\build\impl\source;

use n2n\l10n\Message;
use n2n\bind\plan\BindContext;
use n2n\bind\plan\BindableSource;
use n2n\bind\plan\Bindable;
use n2n\validation\plan\ErrorMap;
use n2n\util\ex\IllegalStateException;
use n2n\validation\plan\DetailedName;
use n2n\util\type\ArgUtils;
use n2n\bind\build\impl\Bind;

abstract class ComposerSourceAdapter implements BindableSource, BindContext {
	/**
	 * @var Bindable[]
	 */
	private array $bindables;
	/**
	 * @var Bindable[]
	 */
	private array $originalBindables;
	/**
	 * @var Message[]
	 */
	private array $generalMessages = [];

	/**
	 * @param Bindable[] $bindables
	 */
	function __construct(array $bindables = []) {
		ArgUtils::valArray($bindables, Bindable::class);

		$this->bindables = [];
		foreach ($bindables as $bindable) {
			$this->addBindable($bindable);
		}

		$this->originalBindables = $this->bindables;
	}
	
	public function addGeneralError(Message $message) {
		$this->generalMessages[] = $message;
	}
	
	function createErrorMap(): ErrorMap {
		$errorMap = new ErrorMap($this->generalMessages);
		
		foreach ($this->bindables as $bindable) {
			$errorMap->putDecendant($bindable->getName()->toArray(), new ErrorMap($bindable->getMessages()));
		}
		
		return $errorMap;
	}

	/**
	 * @param Bindable $bindable
	 * @return void
	 */
	protected function addBindable(Bindable $bindable): void {
		$nameStr = $bindable->getName()->__toString();
		if (isset($this->bindables[$nameStr])) {
			throw new IllegalStateException('Bindable \''  . $nameStr . '\' already defined.');
		}

		$this->bindables[$nameStr] = $bindable;
	}

	/**
	 * @param DetailedName $detailedName
	 * @return Bindable|null
	 */
	protected function getBindable(DetailedName $detailedName) {
		return $this->bindables[$detailedName->__toString()] ?? null;
	}

	function getBindables(): array {
		return $this->bindables;
	}

	function reset(): void {
		$this->generalMessages = [];
		$this->bindables = $this->originalBindables;

		foreach ($this->bindables as $bindable) {
			$bindable->reset();
		}
	}
}
