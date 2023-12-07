<?php

namespace n2n\bind\plan;

use n2n\util\type\attrs\AttributePath;
use n2n\bind\err\UnresolvableBindableException;

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
	function __construct(private BindSource $bindSource, private BindContext $bindContext,
			array $bindables, array $paths) {
		foreach ($bindables as $bindable) {
			$this->addBindable($bindable);
		}

		foreach ($paths as $path) {
			$this->addPath($path);
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

	/**
	 * @return AttributePath[]
	 */
	function getPaths(): array {
		return $this->paths;
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


	/**
	 * @throws UnresolvableBindableException
	 */
	function acquireBindableByRelativeName(string $relativeName): Bindable {
		$name = $this->bindContext->getPath()->ext(AttributePath::create($relativeName));

		$bindable = $this->bindSource->acquireBindable($name, false);
		$this->addBindable($bindable);

		return $bindable;
	}

	function unwarpBindSource(): BindSource {
		return $this->bindSource;
	}

	function getBindContext(): BindContext {
		return $this->bindContext;
	}

}

