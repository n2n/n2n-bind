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
use DateTimeInterface;
use n2n\bind\plan\BindBoundary;
use n2n\bind\mapper\MapperUtils;
use n2n\l10n\L10nUtils;
use n2n\l10n\N2nLocale;

abstract class DateTimeInterfaceMapperAdapter extends SingleMapperAdapter {

	public function __construct(private bool $mandatory, private ?DateTimeInterface $min = null,
			private ?DateTimeInterface $max = null) {
	}

	protected function mapSingle(Bindable $bindable, BindBoundary $bindBoundary, MagicContext $magicContext): bool {
		$value = $this->readSafeValue($bindable, TypeConstraints::type([DateTimeInterface::class, 'string', 'null']));

		if (is_string($value) && null === ($value = $this->convertStrToDateTime($value, $bindable))) {
			return false;
		}

		if ($value !== null) {
			$value = $this->createValueFromDateTimeInterface($value);
		}

		$bindable->setValue($value);
		MapperUtils::validate([$bindable], $this->createValidators($magicContext->lookup(N2nLocale::class, false) ?? N2nLocale::getDefault()), $bindBoundary->getBindContext(), $magicContext);

		return true;
	}

	protected abstract function createValueFromDateTimeInterface(DateTimeInterface $value): DateTimeInterface;

	/**
	 * @param string $dateStr
	 * @param Bindable $bindable
	 * @return \DateTime|null
	 */
	private function convertStrToDateTime(string $dateStr, Bindable $bindable): ?\DateTime {
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
	protected function createValidators(N2nLocale $n2nLocale): array {
		$validators = [];

		if ($this->mandatory) {
			$validators[] = Validators::mandatory();
		}

		if ($this->min !== null) {
			$validators[] = Validators::valueClosure(fn ($dateTime) => $dateTime >= $this->min
					? true
					: ValidationMessages::notEarlierThan(L10nUtils::formatDateTime($this->min, $n2nLocale)));
		}

		if ($this->max !== null) {
			$validators[] = Validators::valueClosure(fn($dateTime) => $dateTime <= $this->max
					? true
					: ValidationMessages::notLaterThan(L10nUtils::formatDateTime($this->max, $n2nLocale)));
		}

		return $validators;
	}
}