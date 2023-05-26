<?php

namespace n2n\bind\mapper\impl\date;

use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\bind\plan\BindContext;
use n2n\bind\plan\Bindable;
use n2n\util\magic\MagicContext;
use n2n\util\DateUtils;
use n2n\l10n\Message;
use n2n\validation\lang\ValidationMessages;
use n2n\validation\validator\Validator;
use n2n\validation\validator\impl\Validators;
use n2n\util\type\TypeConstraints;
use n2n\validation\plan\ValidationGroup;

abstract class DateTimeInterfaceMapperAdapter extends SingleMapperAdapter {

	public function __construct(private bool $mutable, private bool $mandatory, private ?\DateTimeInterface $min = null,
			private ?\DateTimeInterface $max = null) {
	}

	protected function mapSingle(Bindable $bindable, BindContext $bindContext, MagicContext $magicContext): bool {
		$value = $this->readSafeValue($bindable, TypeConstraints::type([\DateTimeInterface::class, 'string', 'null']));

		if ($value === null) {
			$bindable->setValue(null);
			return true;
		}

		if (is_string($value)) {
			if (($value = $this->convertStrToDateTime($value, $bindable)) === null) {
				return false;
			}
		}

		if ($this->mutable) {
			$bindable->setValue(\DateTime::createFromInterface($value));
		} else {
			$bindable->setValue(\DateTimeImmutable::createFromInterface($value));
		}

		$validationGroup = new ValidationGroup($this->createValidators(), [$bindable], $bindContext);
		$validationGroup->exec($magicContext);

		return true;
	}

	/**
	 * @param string|null $dateStr
	 * @param Bindable $bindable
	 * @return \DateTime|null
	 */
	private function convertStrToDateTime(?string $dateStr, Bindable $bindable): ?\DateTime {
		try {
			return DateUtils::sqlToDateTime($dateStr);
		} catch (\InvalidArgumentException $e) {
			$bindable->addError(Message::create(ValidationMessages::invalid(), Message::SEVERITY_ERROR));
			return null;
		}
	}

	/**
	 * @return Validator[]
	 */
	protected function createValidators(): array {
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