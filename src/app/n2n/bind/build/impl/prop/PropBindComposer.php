<?php

namespace n2n\bind\build\impl\prop;

use n2n\validation\plan\Validator;
use n2n\validation\build\impl\compose\prop\PropValidationComposer;

class PropBindComposer {

	function __construct(private PropBindableSource $propBindableResolver) {
	}

	/**
	 *
	 * @param string $expression
	 * @param Validator ...$validators
	 * @return PropValidationComposer
	 */
	function prop(string $expression, Validator ...$validators) {
		return $this->props([$expression], ...$validators);
	}

	/**
	 * @param string[] $expressions
	 * @param Validator ...$validators
	 * @return PropValidationComposer
	 */
	function props(array $expressions, Validator ...$validators) {
		$this->assembleValidationGroup($expressions, $validators, true);
		return $this;
	}

	/**
	 *
	 * @param string $expression
	 * @param Validator ...$validators
	 * @return PropValidationComposer
	 */
	function optProp(string $expression, Validator ...$validators) {
		return $this->optProps([$expression], ...$validators);
	}

	/**
	 * @param string[] $expressions
	 * @param Validator ...$validators
	 * @return PropValidationComposer
	 */
	function optProps(array $expressions, Validator ...$validators) {
		$this->assembleValidationGroup($expressions, $validators, false);
		return $this;
	}

	/**
	 *
	 * @param string $expression
	 * @param bool $mustExist
	 * @param Validator ...$validators
	 * @return PropValidationComposer
	 */
	function dynProp(string $expression, bool $mustExist, Validator ...$validators) {
		return $this->dynProps([$expression], $mustExist, ...$validators);
	}

	/**
	 * @param string[] $expressions
	 * @param bool $mustExist
	 * @param Validator ...$validators
	 * @return PropValidationComposer
	 */
	function dynProps(array $expressions, bool $mustExist, Validator ...$validators) {
		$this->assembleValidationGroup($expressions, $validators, $mustExist);
		return $this;
	}
}