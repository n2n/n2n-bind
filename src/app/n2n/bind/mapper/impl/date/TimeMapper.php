<?php

namespace n2n\bind\mapper\impl\date;

use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\bind\plan\Bindable;
use n2n\bind\plan\BindBoundary;
use n2n\util\magic\MagicContext;
use n2n\util\type\TypeConstraints;
use n2n\util\calendar\Time;
use n2n\util\StringUtils;
use DateTime;
use n2n\l10n\Message;
use n2n\validation\lang\ValidationMessages;
use n2n\validation\validator\impl\Validators;
use n2n\validation\validator\Validator;
use n2n\bind\mapper\MapperUtils;
use n2n\util\DateUtils;
use n2n\l10n\L10nUtils;
use n2n\l10n\N2nLocale;

class TimeMapper extends SingleMapperAdapter {
	public function __construct(private bool $mandatory, private ?Time $min = null,
			private ?Time $max = null) {
	}

	protected function mapSingle(Bindable $bindable, BindBoundary $bindBoundary, MagicContext $magicContext): bool {
		$value = $this->readSafeValue($bindable, TypeConstraints::type([Time::class, 'string', 'null']));

		if (is_string($value) && null === ($value = $this->convertStrToTime($value, $bindable))) {
			return false;
		}

		$bindable->setValue($value);
		MapperUtils::validate([$bindable],
				$this->createValidators($magicContext->lookup(N2nLocale::class, false) ?? N2nLocale::getDefault()),
				$bindBoundary->getBindContext(), $magicContext);

		return true;
	}

//	protected function createValueFromInput(string|Time $time, Bindable $bindable): ?Time {
//		if ($time instanceof Time) {
//			return $time;
//		}
//
//		$dateTime = DateTime::createFromFormat('H:i:s', $time)
//				?: DateTime::createFromFormat('H:i', $time);
//
//		if (!$dateTime) {
//			$bindable->addError(Message::create(ValidationMessages::invalid(), Message::SEVERITY_ERROR));
//			return null;
//		}
//
//		return new Time($dateTime->format('H:i:s'));
//	}

	private function convertStrToTime(string $timeStr, Bindable $bindable): ?Time {
		try {
			return new Time($timeStr);
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
			$validators[] = Validators::valueClosure(fn ($time) => $time >= $this->min
					? true
					: ValidationMessages::notEarlierThan(L10nUtils::formatTime($this->min->toDateTimeImmuntable(), $n2nLocale)));
		}

		if ($this->max !== null) {
			$validators[] = Validators::valueClosure(fn($time) => $time <= $this->max
					? true
					: ValidationMessages::notLaterThan(L10nUtils::formatTime($this->max->toDateTimeImmuntable(), $n2nLocale)));
		}

		return $validators;
	}
}