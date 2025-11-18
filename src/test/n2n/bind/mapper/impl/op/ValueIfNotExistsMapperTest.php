<?php

namespace n2n\bind\mapper\impl\op;

use n2n\util\type\attrs\DataMap;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\magic\MagicContext;
use PHPUnit\Framework\TestCase;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\BindMismatchException;
use n2n\util\type\attrs\InvalidAttributeException;
use n2n\util\type\attrs\MissingAttributeFieldException;

class ValueIfNotExistsMapperTest extends TestCase {

	/**
	 * @throws UnresolvableBindableException
	 * @throws InvalidAttributeException
	 * @throws BindTargetException
	 * @throws MissingAttributeFieldException
	 * @throws BindMismatchException
	 */
	function testAttrs() {
		$sdm = new DataMap();
		$tdm = new DataMap();

		$result = Bind::attrs($sdm)->toAttrs($tdm)
				->optProp('holeradio', Mappers::valueIfNotExists('holeradio'))
				->optProp('hahaha', Mappers::valueIfNotExists(function() {
					return 'hahaha';
				}))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());

		$this->assertEquals('holeradio', $tdm->reqString('holeradio'));
		$this->assertEquals('hahaha', $tdm->reqString('hahaha'));
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testAttrsValExists() {
		$sdm = new DataMap(['holeradio' => 'hmm', 'hahaha' => 'hii']);
		$tdm = new DataMap();

		$result = Bind::attrs($sdm)->toAttrs($tdm)
				->optProp('holeradio', Mappers::valueIfNotExists('holeradio'))
				->optProp('hahaha', Mappers::valueIfNotExists(function() {
					return 'hahaha';
				}))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());

		$this->assertEquals('hmm', $tdm->reqString('holeradio'));
		$this->assertEquals('hii', $tdm->reqString('hahaha'));
	}
}