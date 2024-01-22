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
use n2n\bind\plan\BindSource;
use n2n\bind\plan\Bindable;
use n2n\validation\plan\ErrorMap;
use n2n\util\ex\IllegalStateException;
use n2n\util\type\attrs\AttributePath;
use n2n\util\type\ArgUtils;
use n2n\bind\build\impl\Bind;

abstract class BindSourceAdapter implements BindSource {
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
	
	public function addGeneralError(Message $message): void {
		$this->generalMessages[] = $message;
	}

	function createErrorMap(): ErrorMap {
		$errorMap = new ErrorMap($this->generalMessages);
		
		foreach ($this->bindables as $bindable) {
			if (!$bindable->getPath()->isEmpty()) {
				$errorMap->putDecendant($bindable->getPath()->toArray(), new ErrorMap($bindable->getMessages()));
			}

			foreach ($bindable->getMessages() as $message) {
				$errorMap->addMessage($message);
			}
		}
		
		return $errorMap;
	}

	/**
	 * @param Bindable $bindable
	 * @return void
	 */
	protected function addBindable(Bindable $bindable): void {
		$path = $bindable->getPath();
		if (isset($this->bindables[(string) $path])) {
			throw new IllegalStateException('Bindable \''  . $path->toAbsoluteString()
					. '\' already defined.');
		}

		$this->bindables[(string) $path] = $bindable;
	}

	/**
	 * @param AttributePath $path
	 * @return Bindable|null
	 */
	function getBindable(AttributePath $path): ?Bindable {
		return $this->bindables[(string) $path] ?? null;
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

	function resolvePaths(AttributePath $contextPath, ?string $expression): array {
		return [$contextPath->ext($expression)];
	}
}
