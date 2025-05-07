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

class TimeMapper extends SingleMapperAdapter {
	public function __construct(private bool $mandatory, private ?Time $min = null,
			private ?Time $max = null) {
	}
	protected function mapSingle(Bindable $bindable, BindBoundary $bindBoundary, MagicContext $magicContext): bool {
		$value = $this->readSafeValue($bindable, TypeConstraints::type([Time::class, 'string', 'null']));

		if ($value !== null) {
			$value = $this->createValueFromInput($value, $bindable);
		}
		$bindable->setValue($value);
		MapperUtils::validate([$bindable], $this->createValidators(), $bindBoundary->getBindContext(), $magicContext);

		return true;
	}

	protected function createValueFromInput(string|Time $time, Bindable $bindable): ?Time {
		if (StringUtils::isEmpty($time)) {
			return null;
		}
		if ($time instanceof Time) {
			return $time;
		}
		$dateTime = DateTime::createFromFormat('H:i:s', $time)
				?: DateTime::createFromFormat('H:i', $time);

		if (!$dateTime) {
			$bindable->addError(Message::create(ValidationMessages::invalid(), Message::SEVERITY_ERROR));
			return null;
		}

		return new Time($dateTime->format('H:i:s'));
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
			$validators[] = Validators::valueClosure(fn($time) => $time >= $this->min
					? true : Message::create('Invalid: Value should be greater than '. $this->min->__toString()));
		}

		if ($this->max !== null) {
			$validators[] = Validators::valueClosure(fn($time) => $time <= $this->max
					? true : Message::create('Invalid: Value should be lesser than '. $this->max->__toString()));
		}

		return $validators;
	}
}