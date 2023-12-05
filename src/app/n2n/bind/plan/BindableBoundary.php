<?php

namespace n2n\bind\plan;

use n2n\util\type\ArgUtils;
use n2n\validation\plan\DetailedName;

class BindableBoundary {
	/**
	 * @var Bindable[]
	 */
	private array $bindables = [];

	/**
	 * @param BindableResolver $bindableGroupSource
	 */
	function __construct(private BindBindableResolver $bindableGroupSource) {
		foreach ($this->bindableGroupSource->acquireDefaultBindables() as $bindable) {
			$this->addBindable($bindable);
		}
	}

	private function addBindable(Bindable $bindable) {
		$this->bindables[(string) $bindable->getName()] = $bindable;
	}

	private function addSu

	/**
	 * @return Bindable[]
	 */
	function getBindables(): array {
		return $this->bindables;
	}

	function getSubBindableBoudaries(): array {
		return $this->bindableBoundaries;
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