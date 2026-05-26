<?php
namespace n2n\bind\mapper\impl\string;

use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\bind\plan\Bindable;
use n2n\util\magic\MagicContext;
use n2n\util\type\TypeConstraints;
use n2n\validation\validator\impl\Validators;
use n2n\util\StringUtils;
use n2n\validation\validator\Validator;
use n2n\bind\plan\BindBoundary;
use n2n\bind\mapper\MapperUtils;

class PhoneMapper extends SingleMapperAdapter {
	function __construct(private bool $mandatory) {

	}

	protected function mapSingle(Bindable $bindable, BindBoundary $bindBoundary, MagicContext $magicContext): bool {
		$value = $this->readSafeValue($bindable, TypeConstraints::string(true));

		if ($value !== null) {
			$bindable->setValue(self::normalizeStr($value));
		}

		MapperUtils::validate([$bindable], $this->createValidators(), $bindBoundary->getBindContext(), $magicContext);

		return true;
	}

	/**
	 * @return Validator[]
	 */
	private function createValidators(): array {
		$validators = [];

		if ($this->mandatory) {
			$validators[] = Validators::mandatory();
		}

		$validators[] = Validators::phone();

		return $validators;
	}

	static function normalizeStr(string $phone): string {
		// we normalize as much as possible, trim, and clean String. normalize whitespaces by replace them with a space
		// remove unnecessary double whitespaces and the space between + and country-code
		// parentheses are allowed input but only kept when optional (0) is after country-code
		$phone = preg_replace(['/^00/', '/\\+\\s/', '/\\s+/'], ['+', '+', ' '], StringUtils::clean(($phone)));

		preg_match('/^(\+\d{1,3} ?\(0\))?(.*)/', $phone, $matches);
		return $matches[1] . str_replace(['(', ')'], '', $matches[2]);
	}
}