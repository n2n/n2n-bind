<?php

namespace n2n\bind\mapper\impl\string;

use n2n\reflection\magic\MagicMethodInvoker;
use n2n\util\type\TypeConstraints;
use n2n\bind\plan\Bindable;
use n2n\bind\plan\BindContext;
use n2n\util\magic\MagicContext;
use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\util\StringUtils;
use n2n\validation\validator\impl\Validators;
use n2n\validation\plan\ValidationGroup;
use n2n\util\io\IoUtils;
use Closure;
use n2n\util\type\ArgUtils;
use n2n\validation\validator\impl\ValidationUtils;
use InvalidArgumentException;
use n2n\l10n\Message;
use n2n\bind\plan\BindBoundary;


class PathPartMapper extends SingleMapperAdapter {
	private ?Closure $uniqueTester;
	private string $fillStr = 'path';
	private ?Message $mandatoryErrorMessage = null;
	private ?Message $minlengthErrorMessage = null;
	private ?Message $maxlengthErrorMessage = null;
	private ?Message $uniqueErrorMessage = null;
	private ?Message $noSpecialCharsErrorMessage = null;

	/**
	 *
	 * @param Closure|null $uniqueTester can be used to check if path is already used
	 * @param string|null $generationIfNullBaseName if not null a path part will be generated
	 *        based on Bindable or this argument. $fillStr will be added if below min,
	 *        truncated when above max, num count up to 9999 to stay unique
	 * @param int|null $minlength
	 * @param int|null $maxlength when use $generationIfNullBaseName it has to be 6 or greater
	 * @param bool $mandatory validation will fail, if true when Bindable and $generationIfNullBaseName are null
	 */
	public function __construct(?Closure $uniqueTester, private ?string $generationIfNullBaseName,
			private ?int $minlength, private ?int $maxlength, private bool $mandatory = false) {
		$this->uniqueTester = $uniqueTester;
		$this->validateBoundaryArgs();
	}

	public function getGenerationIfNullBaseName(): ?string {
		return $this->generationIfNullBaseName;
	}

	public function setGenerationIfNullBaseName(?string $generationIfNullBaseName): static {
		$this->generationIfNullBaseName = $generationIfNullBaseName;
		$this->validateBoundaryArgs();
		return $this;
	}

	public function getMinlength(): ?int {
		return $this->minlength;
	}

	public function setMinlength(?int $minlength): static {
		$this->minlength = $minlength;
		$this->validateBoundaryArgs();
		return $this;
	}

	public function getMaxlength(): ?int {
		return $this->maxlength;
	}

	public function setMaxlength(?int $maxlength): static {
		$this->maxlength = $maxlength;
		$this->validateBoundaryArgs();
		return $this;
	}

	public function isMandatory(): bool {
		return $this->mandatory;
	}

	public function setMandatory(bool $mandatory): static {
		$this->mandatory = $mandatory;
		return $this;
	}

	function setFillStr(string $fillStr): static {
		$fillStr = StringUtils::clean($fillStr);
		ArgUtils::assertTrue(ValidationUtils::isLowerCaseOnly($fillStr) && !IoUtils::hasSpecialChars($fillStr)
				&& ValidationUtils::isNotShorterThan($fillStr, 1),
				'Invalid fill str, make sure it is lowercase, contains no specialChars and is at least 1 char long: ' . $fillStr);
		$this->fillStr = $fillStr;
		return $this;
	}

	private function validateBoundaryArgs(): void {
		if ($this->minlength !== null && $this->maxlength !== null && $this->minlength > $this->maxlength) {
			throw new InvalidArgumentException('Maxlength need to be greater or equal to minlength.');
		}

		if ($this->generationIfNullBaseName !== null && $this->maxlength < 6) {
			throw new InvalidArgumentException('If path generation is enabled the maxlength must be greater than 5.');
		}
	}

	private function validate(Bindable $bindable, BindContext $bindContext, MagicContext $magicContext): void {
		$validationGroup = new ValidationGroup($this->createValidators(), [$bindable], $bindContext);
		$validationGroup->exec($magicContext);
	}

	function mapSingle(Bindable $bindable, BindBoundary $bindBoundary, MagicContext $magicContext): bool {
		$value = $this->readSafeValue($bindable, TypeConstraints::string(true));

		if ($value !== null) {
			$bindable->setValue(mb_strtolower(StringUtils::clean($value)));
			$this->validate($bindable, $bindBoundary->getBindContext(), $magicContext);
			return true;
		}

		if ($this->generationIfNullBaseName === null) {
			$this->validate($bindable, $bindBoundary->getBindContext(), $magicContext);
			return true;
		}

		$bindable->setValue($this->generatePathPart($this->generationIfNullBaseName, $magicContext));

		$this->validate($bindable, $bindBoundary->getBindContext(), $magicContext);
		return true;
	}

	private function generatePathPart(string $baseName, MagicContext $magicContext): ?string {
		$value = mb_strtolower(IoUtils::stripSpecialChars(StringUtils::clean($baseName)));
		if (StringUtils::isEmpty($value)) {
			$value = $this->fillStr;
		}

		if ($this->minlength !== null) {
			while (mb_strlen($value) < $this->minlength) {
				$value .= '-' . $this->fillStr;
			}
		}

		$value = StringUtils::reduce($value, $this->maxlength);

		if ($this->uniqueTester === null) {
			return $value;
		}

		$invoker = new MagicMethodInvoker($magicContext);
		$invoker->setReturnTypeConstraint(TypeConstraints::bool());

		$valueBase = $value;

		for ($i = 2; !$invoker->invoke(null, $this->uniqueTester, [$value]); $i++) {
			$value = StringUtils::reduce($valueBase, $this->maxlength - (mb_strlen($i) + 1)) . '-' . $i;

			if ($i > 9999) {
				return null;
			}
		}

		return $value;
	}

	private function createValidators(): array {
		$validators = [];

		if ($this->mandatory) {
			$validators[] = Validators::mandatory($this->mandatoryErrorMessage);
		}
		if ($this->minlength !== null) {
			$validators[] = Validators::minlength($this->minlength, $this->minlengthErrorMessage);
		}
		if ($this->maxlength !== null) {
			$validators[] = Validators::maxlength($this->maxlength, $this->maxlengthErrorMessage);
		}
		if ($this->uniqueTester !== null) {
			$validators[] = Validators::uniqueClosure($this->uniqueTester, $this->uniqueErrorMessage);
		}
		$validators[] = Validators::noSpecialChars($this->noSpecialCharsErrorMessage);


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

	public function setUniqueErrorMessage(mixed $uniqueErrorMessage): static {
		$this->uniqueErrorMessage = Message::build($uniqueErrorMessage);
		return $this;
	}

	public function getUniqueErrorMessage(): ?Message {
		return $this->uniqueErrorMessage;
	}

	public function setNoSpecialCharsErrorMessage(mixed $noSpecialCharsErrorMessage): static {
		$this->noSpecialCharsErrorMessage = Message::build($noSpecialCharsErrorMessage);
		return $this;
	}

	public function getNoSpecialCharsErrorMessage(): ?Message {
		return $this->noSpecialCharsErrorMessage;
	}
}
