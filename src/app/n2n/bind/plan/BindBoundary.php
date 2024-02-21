<?php

namespace n2n\bind\plan;

use n2n\util\type\attrs\AttributePath;
use n2n\bind\err\UnresolvableBindableException;
use n2n\util\ex\IllegalStateException;
use n2n\bind\err\BindMismatchException;

class BindBoundary {
	/**
	 * @var Bindable[]
	 */
	private array $bindables = [];

	private array $paths = [];

	/**
	 * @param BindSource $bindSource
	 * @param BindContext $bindContext
	 * @param Bindable[] $bindables
	 */
	function __construct(private BindSource $bindSource, private BindContext $bindContext, array $bindables) {
		foreach ($bindables as $bindable) {
			$this->addBindable($bindable);
		}
	}

	private function addBindable(Bindable $bindable): void {
		$path = $bindable->getPath();

		$this->bindables[(string) $path] = $bindable;

		$this->addPath($path);
	}

	private function addPath(AttributePath $path): void {
		$this->paths[(string) $path] = $path;
	}

	/**
	 * @return Bindable[]
	 */
	function getBindables(): array {
		return $this->bindables;
	}

	function getBindable(AttributePath $path): ?Bindable {
		return $this->bindables[(string) $path] ?? null;
	}

	function pathToRelativeName(AttributePath $name): string {
		$contextName = $this->bindContext->getPath();

		if ($contextName->isEmpty()) {
			return $name->__toString();
		}

		if ($name->startsWith($contextName)) {
			return $name->slice($contextName->size())->__toString();
		}

		throw new \InvalidArgumentException('"' . $name . '" is not part of context "' . $contextName . '"');
	}


	function acquireBindableByRelativeName(string $relativeName): Bindable {
		$name = $this->bindContext->getPath()->ext(AttributePath::create($relativeName));

		$bindable = $this->bindSource->getBindable($name) ??
				IllegalStateException::try(fn () => $this->bindSource->createBindable($name, false));

		$this->addBindable($bindable);

		return $bindable;
	}

	function unwarpBindSource(): BindSource {
		return $this->bindSource;
	}

	function getBindContext(): BindContext {
		return $this->bindContext;
	}

	function createBindMismatch(string $reason = null, \Throwable $previous = null): BindMismatchException {
		$message = 'Bindables could not be mapped: ' . join(', ', array_keys($this->paths));
		if ($reason !== null || $previous !== null) {
			$message .= ' Reason: ' . $reason ?? $previous->getMessage();
		}

		return new BindMismatchException($message, $previous);
	}

}

