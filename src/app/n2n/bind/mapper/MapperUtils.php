<?php

namespace n2n\bind\mapper;

use n2n\validation\plan\ValidationGroup;
use n2n\bind\plan\BindContext;
use n2n\bind\plan\Bindable;
use n2n\validation\validator\Validator;
use n2n\util\magic\MagicContext;
use n2n\util\type\ArgUtils;

class MapperUtils {

	/**
	 * @param Bindable[] $binables
	 * @param Validator[] $validators
	 * @param BindContext $bindContext
	 * @param MagicContext $magicContext
	 * @return void
	 */
	static function validate(array $binables, array $validators, BindContext $bindContext, MagicContext $magicContext): void {
		$preValidBindables = array_filter($binables, fn (Bindable $b) => $b->isValid());

		$validationGroup = new ValidationGroup($validators, $binables, $bindContext);
		$validationGroup->exec($magicContext);

		foreach ($preValidBindables as $preValidBindable) {
			if (!$preValidBindable->isValid()) {
				$preValidBindable->setDirty(true);
			}
		}
	}

	/**
	 * @param Bindable[] $bindables
	 * @return bool
	 */
	static function spreadDirtyState(array $bindables): bool {
		$dirty = false;
		foreach ($bindables as $bindable) {
			if (!$bindable->isDirty()) {
				continue;
			}

			$dirty = true;
			break;
		}

		if (!$dirty) {
			return false;
		}

		foreach ($bindables as $bindable) {
			$bindable->setDirty(true);
		}

		return true;
	}

	static function pipe(array $mappers, ) {

	}

//	/**
//	 * @param Bindable[] $bindables
//	 * @param mixed $returnValue
//	 * @return mixed
//	 */
//	static function handleBindableCallbackReturn(array $bindables, mixed $returnValue): MapResult {
//		if (!($returnValue instanceof MapResult)) {
//			return new CallbackResult($returnValue, new MapResult(false, false));
//		}
//
//		ArgUtils::valArray($bindables, Bindable::class);
//
//		$chLogical = $returnValue->getChLogical();
//		if ($chLogical !== null) {
//			foreach ($bindables as $bindable) {
//				$bindable->setLogical($chLogical);
//			}
//		}
//
//
//		return new MapResult($returnValue->getValue());
//	}
}

//class CallbackResult {
//
//}