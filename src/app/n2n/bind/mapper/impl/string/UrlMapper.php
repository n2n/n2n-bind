<?php
namespace n2n\bind\mapper\impl\string;

use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\bind\plan\Bindable;
use n2n\bind\plan\BindBoundary;
use n2n\util\magic\MagicContext;
use n2n\validation\validator\impl\Validators;
use n2n\util\type\TypeConstraints;
use n2n\util\StringUtils;
use n2n\bind\mapper\MapperUtils;
use n2n\util\type\ArgUtils;
use n2n\util\uri\Url;
use n2n\validation\lang\ValidationMessages;

class UrlMapper extends SingleMapperAdapter {
    public function __construct(private bool $mandatory = false, private ?array $allowedSchemes = null,
			private bool $schemeRequired = true, private ?int $maxlength = null) {
		ArgUtils::valArray($this->allowedSchemes, 'string', true);
    }

    protected function mapSingle(Bindable $bindable, BindBoundary $bindBoundary, MagicContext $magicContext): bool {
        $value = $this->readSafeValue($bindable, TypeConstraints::string(true));

		$url = null;
        if ($value !== null) {
			try {
				$url = Url::create(StringUtils::clean($value));
			} catch (\InvalidArgumentException $e) {
				$bindable->addError(ValidationMessages::url());
			}
            $bindable->setValue($url);
        }

        MapperUtils::validate([$bindable],
				$this->createValidators(),
				$bindBoundary->getBindContext(), $magicContext);

		$bindable->setValue($url);

        return true;
    }

    private function createValidators(): array {
        $validators = [];

        if ($this->mandatory) {
            $validators[] = Validators::mandatory();
        }

		$validators[] = Validators::url($this->schemeRequired, $this->allowedSchemes);

		if ($this->maxlength !== null) {
			$validators[] = Validators::maxlength($this->maxlength);
		}

        return $validators;
    }
} 