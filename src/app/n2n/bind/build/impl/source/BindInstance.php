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
use n2n\bind\plan\BindableFactory;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\BindMismatchException;
use n2n\util\type\custom\Undefined;

class BindInstance {
	/**
	 * @var Bindable[]
	 */
	private array $bindables = [];
	/**
	 * @var Bindable[]
	 */
	private array $originalBindables = [];
	/**
	 * @var Message[]
	 */
	private array $generalMessages = [];

	function __construct(private BindableFactory $bindableFactory, private bool $undefinedAsNonExisting = true) {
	}

	function init(): static {
		$this->originalBindables = $this->bindableFactory->createInitialBindables();
		ArgUtils::valArrayReturn($this->originalBindables, $this->bindableFactory, 'createInitialBindables',
				Bindable::class);
		$this->bindables = [];
		foreach ($this->originalBindables as $bindable) {
			$this->addBindable($bindable);
		}
		return $this;
	}

//	function isValid(): bool {
//		if (!empty($this->generalMessages)) {
//			return false;
//		}
//
//		foreach ($this->bindables as $bindable) {
//			if (!$bindable->isValid()) {
//				return false;
//			}
//		}
//
//		return true;
//	}

	public function addGeneralError(Message $message): void {
		$this->generalMessages[] = $message;
	}

	function createErrorMap(): ErrorMap {
		$errorMap = new ErrorMap($this->generalMessages);
		
		foreach ($this->bindables as $bindable) {
			foreach ($bindable->getMessages() as $message) {
				$errorMap->addDecendantMessage($bindable->getPath()->toArray(), $message);
			}
		}
		
		return $errorMap;
	}

	/**
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function createBindable(AttributePath $path, bool $mustExist): Bindable  {
		$bindable = $this->bindableFactory->createBindable($path, $mustExist);

		if ($mustExist && !$bindable->doesExist()) {
			throw new UnresolvableBindableException('Bindable with path "' . $path . '" does not exist.');
		}

		if (!$bindable->doesExist() || !$this->undefinedAsNonExisting || !Undefined::is($bindable->getValue())) {
			$this->addBindable($bindable);
			return $bindable;
		}

		if ($mustExist) {
			throw new UnresolvableBindableException('Bindable with path "' . $path
					. '" is treated as non existing because it holds a value of type ' . Undefined::class);
		}

		$bindable->setExist(false);

		$this->addBindable($bindable);
		return $bindable;
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
	function getBindable(AttributePath $attributePath): ?Bindable {
		return $this->bindables[(string) $attributePath] ?? null;
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
