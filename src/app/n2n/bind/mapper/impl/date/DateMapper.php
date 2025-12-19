<?php

namespace n2n\bind\mapper\impl\date;

use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\bind\plan\Bindable;
use n2n\bind\plan\BindBoundary;
use n2n\util\magic\MagicContext;
use n2n\util\type\TypeConstraints;
use n2n\util\calendar\Date;
use n2n\l10n\Message;
use n2n\validation\lang\ValidationMessages;
use n2n\validation\validator\impl\Validators;
use n2n\validation\validator\Validator;
use n2n\bind\mapper\MapperUtils;
use n2n\l10n\L10nUtils;
use n2n\l10n\N2nLocale;
use n2n\util\DateParseException;

class DateMapper extends SingleMapperAdapter {
	public function __construct(private bool $mandatory, private ?Date $min = null,
			private ?Date $max = null) {
	}

	protected function mapSingle(Bindable $bindable, BindBoundary $bindBoundary, MagicContext $magicContext): bool {
		$value = $this->readSafeValue($bindable, TypeConstraints::type([Date::class, 'string', 'null']));

		if (is_string($value) && null === ($value = $this->convertStrToDate($value, $bindable))) {
			return false;
		}

		$bindable->setValue($value);
		MapperUtils::validate([$bindable],
				$this->createValidators($magicContext->lookup(N2nLocale::class, false) ?? N2nLocale::getDefault()),
				$bindBoundary->getBindContext(), $magicContext);

		return true;
	}

	private function convertStrToDate(string $dateStr, Bindable $bindable): ?Date {
		try {
			return new Date($dateStr);
		} catch (DateParseException $e) {
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
			$validators[] = Validators::valueClosure(fn ($date) => $date->isGreaterThanOrEqualTo($this->min)
					? true
					: ValidationMessages::notEarlierThan(L10nUtils::formatDate($this->min->toDateTimeImmutable(), $n2nLocale)));
		}

		if ($this->max !== null) {
			$validators[] = Validators::valueClosure(fn($date) => $date->isLessThanOrEqualTo($this->max)
					? true
					: ValidationMessages::notLaterThan(L10nUtils::formatDate($this->max->toDateTimeImmutable(), $n2nLocale)));
		}

		return $validators;
	}
}