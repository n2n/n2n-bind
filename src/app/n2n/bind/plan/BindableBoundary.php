<?php

namespace n2n\bind\plan;

use n2n\util\type\ArgUtils;

class BindableBoundary {
	/**
	 * @var Bindable[]
	 */
	private array $bindable = [];

	/**
	 * @param BindableGroupSource $bindableGroupSource
	 */
	function __construct(private BindableGroupSource $bindableGroupSource) {
		ArgUtils::valArray($this->bindables, Bindable::class);

		foreach ($this->bindableGroupSource->acquireDefaultBindables() as $bindable) {
			$this->addBindable($bindable);
		}
	}

	private function addBindable(Bindable $bindable) {
		$this->bindables[(string) $bindable->getName()] = $bindable;
	}

	/**
	 * @return Bindable[]
	 */
	function getBindables(): array {
		return $this->bindables;
	}

	/**
	 * @param string $name
	 * @return Bindable
	 */
	function acquireBindable(string $name): Bindable {
		if (isset($this->bindables[$name])) {
			return $this->bindables[$name];
		}

		$bindable = $this->bindableGroupSource->acquireBindable($name);
		$this->addBindable($bindable);

		return $bindable;
	}
}