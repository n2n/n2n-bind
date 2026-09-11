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
use n2n\util\io\IoUtils;
use InvalidArgumentException;
use n2n\l10n\Message;

class NoSpecialCharsMapper extends SingleMapperAdapter {
	private ?Message $mandatoryErrorMessage = null;
	private ?Message $minlengthErrorMessage = null;
	private ?Message $maxlengthErrorMessage = null;
	private ?Message $noSpecialCharsErrorMessage = null;

	function __construct(private bool $mandatory, private bool $lowerCase, private ?int $minlength,
			private ?int $maxlength) {
		if ($this->minlength !== null && $this->maxlength !== null && $this->minlength > $this->maxlength) {
			throw new InvalidArgumentException('Maxlength need to be greater or equal to minlength.');
		}
	}

	protected function mapSingle(Bindable $bindable, BindBoundary $bindBoundary, MagicContext $magicContext): bool {
		$value = $this->readSafeValue($bindable, TypeConstraints::string(true, true));
		if ($value !== null) {
			$value = IoUtils::stripSpecialChars($value, true);
			if ($this->lowerCase) {
				$value = mb_strtolower($value);
			}
		}

		if ($value === '') {
			$value = null;
		}

		$bindable->setValue($value);
		MapperUtils::validate([$bindable], $this->createValidators(), $bindBoundary->getBindContext(), $magicContext);

		return true;
	}

	/**
	 * @return Validator[]
	 */
	private function createValidators(): array {
		$validators = [];
		//should never happen because mapper should strip any special Char, validator just here to ensure it
		$validators[] = Validators::noSpecialChars($this->noSpecialCharsErrorMessage);

		if ($this->mandatory) {
			$validators[] = Validators::mandatory($this->mandatoryErrorMessage);
		}

		if ($this->minlength !== null) {
			$validators[] = Validators::minlength($this->minlength, $this->minlengthErrorMessage);
		}

		if ($this->maxlength !== null) {
			$validators[] = Validators::maxlength($this->maxlength, $this->maxlengthErrorMessage);
		}

		return $validators;
	}

	public function setMandatoryErrorMessage(mixed $mandatoryErrorMessage): static {
		$this->mandatoryErrorMessage = Message::build($mandatoryErrorMessage);
		return $this;
	}

	public function getMandatoryErrorMessage(): ?Message {
		return $this->mandatoryErrorMessage;
	}

	public function setMinlengthErrorMessage(mixed $minlengthErrorMessage): static {
		$this->minlengthErrorMessage = Message::build($minlengthErrorMessage);
		return $this;
	}

	public function getMinlengthErrorMessage(): ?Message {
		return $this->minlengthErrorMessage;
	}

	public function setMaxlengthErrorMessage(mixed $maxlengthErrorMessage): static {
		$this->maxlengthErrorMessage = Message::build($maxlengthErrorMessage);
		return $this;
	}

	public function getMaxlengthErrorMessage(): ?Message {
		return $this->maxlengthErrorMessage;
	}

	public function setNoSpecialCharsErrorMessage(?Message $noSpecialCharsErrorMessage): static {
		$this->noSpecialCharsErrorMessage = $noSpecialCharsErrorMessage;
		return $this;
	}
}