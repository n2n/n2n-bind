<?php
namespace n2n\bind\build\impl\target\mock;

class BindTestClass {
	private string $string = '';
	private int $int = 0;
	private array $array = [];
	private ?BindTestClass $obj = null;
	private $unaccessible;

	/**
	 * @return string
	 */
	public function getString(): string {
		return $this->string;
	}

	/**
	 * @param string $string
	 */
	public function setString(string $string): void {
		$this->string = $string;
	}

	/**
	 * @return int
	 */
	public function getInt(): int {
		return $this->int;
	}

	/**
	 * @param int $int
	 */
	public function setInt(int $int): void {
		$this->int = $int;
	}

	/**
	 * @return array
	 */
	public function getArray(): array {
		return $this->array;
	}

	/**
	 * @param array $array
	 */
	public function setArray(array $array): void {
		$this->array = $array;
	}

	/**
	 * @return BindTestClass|null
	 */
	public function getObj(): ?BindTestClass {
		return $this->obj;
	}

	/**
	 * @param BindTestClass|null $obj
	 */
	public function setObj(?BindTestClass $obj): void {
		$this->obj = $obj;
	}
}