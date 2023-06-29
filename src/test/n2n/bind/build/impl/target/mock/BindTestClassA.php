<?php
namespace n2n\bind\build\impl\target\mock;

use n2n\bind\build\impl\BindTest;

class BindTestClassA {
	private string $string = '';
	private int $int = 0;
	private array $array = [];
	private BindTestClassA $a;
	public BindTestClassB $b;
	private BindTestClassB $pBb;
	private ?BindTestClassB $pBbb = null;
	private ?BindTestClassB $pBbbb = null;
	private $unaccessible;
	public int $getBbCallsCount = 0;

	private BindTestClassB $inaccessibleB;

	function __construct() {
		$this->pBb = new BindTestClassB();
		$this->inaccessibleB = new BindTestClassB();
	}

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
	 * @return BindTestClassA
	 */
	public function getA(): ?BindTestClassA {
		return $this->a ?? null;
	}

	/**
	 * @param BindTestClassA $obj
	 */
	public function setA(BindTestClassA $a): void {
		$this->a = $a;
	}

	function getBb(): BindTestClassB {
		$this->getBbCallsCount++;
		return $this->pBb;
	}

	function getBbb(): ?BindTestClassB {
		return null;
	}

	function getBbbb(): ?BindTestClassB {
		return $this->pBbbb;
	}

	function setBbbb(?BindTestClassB $bbbb): void {
		$this->pBbbb = $bbbb;
	}

}