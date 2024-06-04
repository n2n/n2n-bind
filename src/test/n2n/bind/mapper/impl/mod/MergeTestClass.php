<?php
namespace n2n\bind\mapper\impl\mod;

use n2n\bind\build\impl\BindTest;
use n2n\bind\build\impl\target\mock\BindTestClassB;
use n2n\bind\build\impl\target\mock\BindTestClassA;

class MergeTestClass {
	public ?string $string = null;
	public int $int = 0;
	public array $array = [];
}