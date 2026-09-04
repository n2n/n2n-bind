<?php

namespace n2n\bind\mapper\impl\string;

use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\bind\plan\Bindable;
use n2n\bind\plan\BindBoundary;
use n2n\util\magic\MagicContext;
use n2n\util\type\TypeConstraints;
use n2n\util\StringUtils;
use n2n\bind\mapper\MapperUtils;
use n2n\validation\validator\Validator;
use n2n\validation\validator\impl\Validators;
use n2n\spec\valobj\scalar\StringValueObject;
use Stringable;
use Closure;
use n2n\util\type\ArgUtils;
use n2n\validation\validator\impl\ValidationUtils;
use n2n\util\io\IoUtils;
use InvalidArgumentException;
use n2n\util\magic\impl\MagicMethodInvoker;

class GenericGeneratedValueMapper extends SingleMapperAdapter {
	private ?Closure $uniqueTester;
	private bool $lowerCase = true;
	private bool $noSpecialChars = true;
	private string $fillStr = 'path';

	/**
	 * the default lowerCase and noSpecialChars restrictions could be disabled via fluent setter
	 * because this class is designed for "generic" path generation, the default ar lowercase with no special chars
	 */
	function __construct(private int $minlength, private int $maxlength,
			private StringValueObject|Stringable|string|null $generationIfNullBaseName, string $fillStr, ?Closure $uniqueTester) {
		$this->uniqueTester = $uniqueTester;
		$this->setFillStr($fillStr);
		if ($this->minlength !== null && $this->maxlength !== null && $this->minlength > $this->maxlength) {
			throw new InvalidArgumentException('Maxlength need to be greater or equal to minlength.');
		}

		if ($this->maxlength < 6) {
			throw new InvalidArgumentException('maxlength must be greater than 5.');
		}
	}

	protected function mapSingle(Bindable $bindable, BindBoundary $bindBoundary, MagicContext $magicContext): bool {
		$value = $this->readSafeValue($bindable, TypeConstraints::type(['string', Stringable::class, StringValueObject::class, null]));
		$generationBase = $value ?? $this->generationIfNullBaseName;
		$generationBase = StringUtils::strOrNullOf($generationBase);
		$bindable->setValue($this->generateValue($generationBase, $magicContext));

		MapperUtils::validate([$bindable], $this->createValidators(), $bindBoundary->getBindContext(), $magicContext);

		return true;
	}

	function setFillStr(string $fillStr): static {
		ArgUtils::assertTrue(ValidationUtils::isNotShorterThan($fillStr, 1),
				'Invalid fill str, make sure it is at least 1 char long: ' . $fillStr);

		$fillStr = StringUtils::clean($fillStr);
		if ($this->noSpecialChars) {
			$fillStr = IoUtils::stripSpecialChars($fillStr);
		}
		if ($this->lowerCase) {
			$fillStr = mb_strtolower($fillStr);
		}
		$this->fillStr = $fillStr;
		return $this;
	}

	public function setLowerCase(bool $lowerCase): static {
		$this->lowerCase = $lowerCase;
		return $this;
	}

	public function setNoSpecialChars(bool $noSpecialChars): GenericGeneratedValueMapper {
		$this->noSpecialChars = $noSpecialChars;
		return $this;
	}

	private function generateValue(?string $generationBaseName, MagicContext $magicContext): ?string {
		$cleanBase = null;
		if ($generationBaseName !== null) {
			$cleanBase = StringUtils::clean($generationBaseName);
			if ($this->noSpecialChars) {
				$cleanBase = IoUtils::stripSpecialChars($cleanBase);
			}
			if ($this->lowerCase) {
				$cleanBase = mb_strtolower($cleanBase);
			}
		}

		if (StringUtils::isEmpty($cleanBase)) {
			$cleanBase = $this->fillStr;
		}

		while (mb_strlen($cleanBase) < $this->minlength) {
			$cleanBase .= '-' . $this->fillStr;
		}

		$cleanBase = StringUtils::reduce($cleanBase, $this->maxlength);

		if ($this->uniqueTester === null) {
			return $cleanBase;
		}

		$invoker = new MagicMethodInvoker($magicContext);
		$invoker->setReturnTypeConstraint(TypeConstraints::bool());

		$valueBase = $cleanBase;

		for ($i = 2; !$invoker->invoke(null, $this->uniqueTester, [$cleanBase]); $i++) {
			$cleanBase = StringUtils::reduce($valueBase, $this->maxlength - (mb_strlen($i) + 1)) . '-' . $i;

			if ($i > 9999) {
				return null;
			}
		}

		return $cleanBase;
	}


	/**
	 * @return Validator[]
	 */
	private function createValidators(): array {
		$validators = [];

		$validators[] = Validators::minlength($this->minlength);
		$validators[] = Validators::maxlength($this->maxlength);
		$validators[] = Validators::mandatory();

		return $validators;
	}
}