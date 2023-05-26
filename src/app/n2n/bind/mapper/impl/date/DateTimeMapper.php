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
use DateTime;

class DateTimeMapper extends SingleMapperAdapter {
	function __construct(private bool $mandatory, private ?\DateTimeInterface $min = null,
			private ?\DateTimeInterface $max = null) {
	}

	protected function mapSingle(Bindable $bindable, BindContext $bindContext, MagicContext $magicContext): bool {
		$value = $this->readSafeValue($bindable, TypeConstraints::type([DateTime::class, 'string', 'null']));

		if ($value === null) {
			$bindable->setValue($value);
			return true;
		}

		if ($value instanceof \DateTimeImmutable) {
			$bindable->setValue($value);
		} else if (!$this->applyStringToBindable($value, $bindable)) {
			return false;
		}

		$validationGroup = new ValidationGroup($this->createValidators(), [$bindable], $bindContext);
		$validationGroup->exec($magicContext);

		return true;
	}

	/**
	 * @param string $dateStr
	 * @param Bindable $bindable
	 * @return DateTime
	 */
	private function applyStringToBindable(string $dateStr, Bindable $bindable): bool {
		try {
			$bindable->setValue(DateUtils::sqlToDateTime($dateStr));
			return true;
		} catch (\InvalidArgumentException $e) {
			$bindable->addError(Message::create(ValidationMessages::invalid(), Message::SEVERITY_ERROR));
			return false;
		}
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