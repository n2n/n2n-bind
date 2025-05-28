<?php

namespace n2n\bind\mapper\impl\string;

use n2n\util\type\attrs\DataMap;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\magic\MagicContext;
use PHPUnit\Framework\TestCase;

class UrlMapperTest extends TestCase {
    function testAttrs() {
        $sdm = new DataMap(['url1' => 'https://example.com', 'url2' => 'http://test.com', 'url3' => 'https://sub.example.com']);
        $tdm = new DataMap();

        $result = Bind::attrs($sdm)->toAttrs($tdm)->props(['url1', 'url2', 'url3'], Mappers::url(true))
                ->exec($this->getMockBuilder(MagicContext::class)->getMock());

        $this->assertTrue($result->isValid());

        $this->assertEquals('https://example.com', $tdm->reqString('url1'));
        $this->assertEquals('http://test.com', $tdm->reqString('url2'));
        $this->assertEquals('https://sub.example.com', $tdm->reqString('url3'));
    }

    function testAttrsValFail() {
        $sdm = new DataMap(['url1' => 'invalid-url', 'url2' => 'http://', 'url3' => 'https://']);
        $tdm = new DataMap();

        $result = Bind::attrs($sdm)->toAttrs($tdm)->props(['url1', 'url2', 'url3'], Mappers::url(true))
                ->exec($this->getMockBuilder(MagicContext::class)->getMock());

        $this->assertFalse($result->isValid());

        $this->assertTrue($tdm->isEmpty());

        $errorMap = $result->getErrorMap();
        $this->assertCount(1, $errorMap->getChild('url1')->getMessages());
        $this->assertCount(1, $errorMap->getChild('url2')->getMessages());
        $this->assertCount(1, $errorMap->getChild('url3')->getMessages());
    }

	function testAttrsValSingleInvalid() {
		$sdm = new DataMap(['url1' => 'http://test.com', 'url2' => 'https://test.com', 'url3' => 'https://']);
		$tdm = new DataMap();

		$result = Bind::attrs($sdm)->toAttrs($tdm)->props(['url1', 'url2', 'url3'], Mappers::url(true))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertFalse($result->isValid());

		$this->assertTrue($tdm->isEmpty());

		$errorMap = $result->getErrorMap();

		$this->assertCount(1, $errorMap->getChild('url3')->getMessages());
	}

    function testAllowedSchemas() {
        $sdm = new DataMap([
            'url1' => 'https://example.com',
            'url2' => 'http://test.com',
            'url3' => 'ftp://example.com'
        ]);
        $tdm = new DataMap();

        $result = Bind::attrs($sdm)->toAttrs($tdm)->props(['url1', 'url2', 'url3'], 
                Mappers::url(true, ['https']))
                ->exec($this->getMockBuilder(MagicContext::class)->getMock());

        $this->assertFalse($result->isValid());
        $this->assertTrue($tdm->isEmpty());

        $errorMap = $result->getErrorMap();
        $this->assertCount(1, $errorMap->getChild('url2')->getMessages()); // http is not allowed
        $this->assertCount(1, $errorMap->getChild('url3')->getMessages()); // ftp is not allowed
    }

    function testSchemaMandatory() {
        $sdm = new DataMap([
            'url1' => 'https://example.com',
            'url2' => 'example.com',
            'url3' => 'www.example.com'
        ]);
        $tdm = new DataMap();

        $result = Bind::attrs($sdm)->toAttrs($tdm)->props(['url1', 'url2', 'url3'], 
                Mappers::url(true, null, true))
                ->exec($this->getMockBuilder(MagicContext::class)->getMock());

        $this->assertFalse($result->isValid());
        $this->assertTrue($tdm->isEmpty());

        $errorMap = $result->getErrorMap();
        $this->assertCount(1, $errorMap->getChild('url2')->getMessages()); // no scheme
        $this->assertCount(1, $errorMap->getChild('url3')->getMessages()); // no scheme

        $tdm = new DataMap();
        $result = Bind::attrs($sdm)->toAttrs($tdm)->props(['url1', 'url2', 'url3'], 
                Mappers::url(true, null, false))
                ->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());
        $this->assertEquals('https://example.com', $tdm->reqString('url1'));
        $this->assertEquals('example.com', $tdm->reqString('url2'));
        $this->assertEquals('www.example.com', $tdm->reqString('url3'));
    }
} 