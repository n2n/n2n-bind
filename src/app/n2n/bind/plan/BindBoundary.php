<?php

namespace n2n\bind\plan;

use n2n\util\type\attrs\AttributePath;
use n2n\bind\err\UnresolvableBindableException;

class BindBoundary {
	/**
	 * @var Bindable[]
	 */
	private array $bindables = [];

	/**
	 * @param BindSource $bindSource
	 * @param BindContext $bindContext
	 * @param Bindable[] $bindables
	 */
	function __construct(private BindSource $bindSource, private BindContext $bindContext,
			array $bindables) {
		foreach ($bindables as $bindable) {
			$this->addBindable($bindable);
		}
	}

	private function addBindable(Bindable $bindable): void {
		$contextName = $this->bindContext->getPath();

		$this->bindables[(string) $bindable->getPath()] = $bindable;
	}

	/**
	 * @return Bindable[]
	 */
	function getBindables(): array {
		return $this->bindables;
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
	function acquireBindable(string $relativeName): Bindable {
		$name = $this->bindContext->getPath()->ext(AttributePath::create($relativeName));

		$bindable = $this->bindSource->acquireBindable($name, false);
		$this->addBindable($bindable);

		return $bindable;
	}

	function unwarpBindSource(): BindSource {
		return $this->bindSource;
	}

	function unwrapBindContext(): BindContext {
		return $this->bindContext;
	}

}

