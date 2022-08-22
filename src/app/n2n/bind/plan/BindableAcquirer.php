<?php

namespace n2n\bind\plan;

interface BindableAcquirer {
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