<?php

namespace n2n\bind\plan;

class BendableBoundary {

	function prepare() {

	}

	/**
	 * @return Bindable[]
	 */
	function getBindables(): array;

	/**
	 * @param string $name
	 * @return Bindable
	 */
	function acquireBindable(string $name): Bindable;
}