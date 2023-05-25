<?php
namespace n2n\bind\mapper\impl\date;

use n2n\bind\plan\Bindable;
use n2n\bind\plan\BindContext;
use n2n\util\magic\MagicContext;
use n2n\util\type\TypeConstraints;
use n2n\validation\plan\ValidationGroup;
use n2n\validation\validator\Validator;
use n2n\validation\validator\impl\Validators;
use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\util\StringUtils;
use n2n\util\DateUtils;
use n2n\util\DateParseException;
use n2n\l10n\Message;
use n2n\validation\lang\ValidationMessages;

class DateTimeMapper extends SingleMapperAdapter {
	function __construct(private bool $mandatory, private ?\DateTime $min = null, private ?\DateTime $max = null) {
	}

	protected function mapSingle(Bindable $bindable, BindContext $bindContext, MagicContext $magicContext): bool {
		if (is_string($bindable->getValue())) {
			try {
				$bindable->setValue($this->convertDate($bindable->getValue()));
			} catch (\InvalidArgumentException $e) {
				$bindable->addError(Message::create(ValidationMessages::invalid(), Message::SEVERITY_ERROR));
				return false;
			}
		}

		$value = $this->readSafeValue($bindable, TypeConstraints::type(\DateTime::class));
		if ($value !== null) {
			$bindable->setValue($value);
		}

		$validationGroup = new ValidationGroup($this->createValidators(), [$bindable], $bindContext);
		$validationGroup->exec($magicContext);

		return true;
	}

	/**
	 * @param string|null $dateStr
	 * @return \DateTime|null
	 * @throws DateParseException
	 */
	private function convertDate(?string $dateStr) {
		if (StringUtils::isEmpty($dateStr)) {
			return null;
		}

		return DateUtils::sqlToDateTime($dateStr);
	}

	/**
	 * @return Validator[]
	 */
	private function createValidators(): array {
		$validators = [];

		if ($this->mandatory) {
			$validators[] = Validators::mandatory();
		}

		if ($this->min !== null) {
			$validators[] = Validators::valueClosure(fn($dateTime) => $dateTime >= $this->min);
		}

		if ($this->max !== null) {
			$validators[] = Validators::valueClosure(fn($dateTime) => $dateTime <= $this->max);
		}

		return $validators;
	}
}