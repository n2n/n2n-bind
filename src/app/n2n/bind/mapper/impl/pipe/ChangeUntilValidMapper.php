<?php

namespace n2n\bind\mapper\impl\pipe;

use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\bind\plan\Bindable;
use n2n\bind\plan\BindBoundary;
use n2n\util\magic\MagicContext;
use n2n\util\type\ArgUtils;
use n2n\bind\mapper\Mapper;
use n2n\validation\lang\ValidationMessages;
use n2n\bind\mapper\impl\PipeMapper;
use n2n\bind\err\MisconfiguredMapperException;

class ChangeUntilValidMapper extends SingleMapperAdapter {

	/**
	 * @param RetryValueChanger $retryValueChanger
	 * @param Mapper[] $mappers
	 */
	public function __construct(public RetryValueChanger $retryValueChanger, private array $mappers) {
		ArgUtils::valArray($this->mappers, Mapper::class);
	}

	protected function mapSingle(Bindable $bindable, BindBoundary $bindBoundary, MagicContext $magicContext): bool {
		$state = new ChangeUntilLoopState($bindable->getValue(), $magicContext);

		$pipeMapper = new PipeMapper($this->mappers);
		for ($state->retryNo = 0; $state->retryNo <= $this->retryValueChanger->maxRetryNo; $state->retryNo++) {

			$mapResult = $pipeMapper->map(new BindBoundary($bindBoundary->getBindContext(), [$bindable]), $magicContext);
			if (!$mapResult->isOk()) {
				return false;
			}

			$value = $this->readSafeValue($bindable, $this->retryValueChanger->getValueTypeConstraint());
			$state->addMappedValue($value);

			$result = $this->retryValueChanger->processValue($value, $state);
			if ($result->valid) {
				return true;
			}

			if ($result->retryValueAvailable) {
				$bindable->setValue($result->retryValue);
				continue;
			}

			$bindable->addError(ValidationMessages::invalid());
			return true;

		}

		throw new MisconfiguredMapperException(get_class($this->retryValueChanger) . ' could not find a unique value after ' .
				$this->retryValueChanger->maxRetryNo . ' retries. Please increase maxRetryNo or review the mapper configuration.'
		);

	}
}