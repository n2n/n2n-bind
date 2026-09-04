<?php

namespace n2n\bind\mapper\impl\closure;

use n2n\l10n\Message;
use Closure;
use n2n\bind\plan\Bindable;
use n2n\bind\plan\BindContext;
use n2n\util\magic\MagicContext;
use n2n\validation\plan\ValidationGroup;
use n2n\bind\plan\BindBoundary;
use n2n\util\type\TypeConstraints;
use n2n\validation\validator\impl\Validators;
use n2n\bind\mapper\impl\SingleMapperAdapter;

class UniqueMapper extends SingleMapperAdapter {
	public ?Message $uniqueErrorMessage = null;

	/**
	 * @param Closure $uniqueTester is used to check if input already used
	 */
	public function __construct(public Closure $uniqueTester) {
	}
	private function validate(Bindable $bindable, BindContext $bindContext, MagicContext $magicContext): void {
		$validationGroup = new ValidationGroup($this->createValidators(), [$bindable], $bindContext);
		$validationGroup->exec($magicContext);
	}

	function mapSingle(Bindable $bindable, BindBoundary $bindBoundary, MagicContext $magicContext): bool {
		$value = $this->readSafeValue($bindable, TypeConstraints::type(['string', \Stringable::class, null]));
		$this->validate($bindable, $bindBoundary->getBindContext(), $magicContext);
		return true;
	}


	private function createValidators(): array {
		$validators = [];
		$validators[] = Validators::uniqueClosure($this->uniqueTester, $this->uniqueErrorMessage);
		return $validators;
	}

	public function setUniqueErrorMessage(mixed $uniqueErrorMessage): static {
		$this->uniqueErrorMessage = Message::build($uniqueErrorMessage);
		return $this;
	}

	public function getUniqueErrorMessage(): ?Message {
		return $this->uniqueErrorMessage;
	}
}