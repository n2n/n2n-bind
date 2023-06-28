<?php
namespace n2n\bind\build\impl\target\mock;

class BindTestClassB {
	public ?string $value = null;

	private ?string $value2 = null;

	public function getValue2(): ?string {
		return $this->value2;
	}

	public function setValue2(?string $value2): BindTestClassB {
		$this->value2 = $value2;
		return $this;
	}

}