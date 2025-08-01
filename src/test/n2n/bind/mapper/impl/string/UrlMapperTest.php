<?php

namespace n2n\bind\mapper\impl\string;

use n2n\util\type\attrs\DataMap;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\magic\MagicContext;
use n2n\util\uri\Url;
use PHPUnit\Framework\TestCase;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\BindMismatchException;
use n2n\util\type\attrs\InvalidAttributeException;
use n2n\util\type\attrs\MissingAttributeFieldException;

class UrlMapperTest extends TestCase {
    function testAttrs() {
        $sdm = new DataMap(['url1' => 'https://example.com', 'url2' => 'http://test.com', 'url3' => 'https://sub.example.com']);
        $tdm = new DataMap();

        $result = Bind::attrs($sdm)->toAttrs($tdm)->props(['url1', 'url2', 'url3'], Mappers::url(true))
                ->exec($this->getMockBuilder(MagicContext::class)->getMock());

        $this->assertTrue($result->isValid());

        $this->assertInstanceOf(Url::class, $tdm->req('url1'));
        $this->assertEquals('https://example.com', (string) $tdm->req('url1'));
        $this->assertInstanceOf(Url::class, $tdm->req('url2'));
        $this->assertEquals('http://test.com', (string) $tdm->req('url2'));
        $this->assertInstanceOf(Url::class, $tdm->req('url3'));
        $this->assertEquals('https://sub.example.com', (string) $tdm->req('url3'));
    }

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testAttrsValFail() {
        $sdm = new DataMap(['url1' => 'invalid-url', 'url2' => 'http://', 'url3' => 'https://']);
        $tdm = new DataMap();

        $result = Bind::attrs($sdm)->toAttrs($tdm)
				->props(['url1', 'url2', 'url3'], Mappers::url(true))
                ->exec($this->getMockBuilder(MagicContext::class)->getMock());

        $this->assertFalse($result->isValid());

        $this->assertTrue($tdm->isEmpty());

        $errorMap = $result->getErrorMap();
        $this->assertCount(1, $errorMap->getChild('url1')->getMessages());
        $this->assertCount(1, $errorMap->getChild('url2')->getMessages());
        $this->assertCount(1, $errorMap->getChild('url3')->getMessages());
    }

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
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

	/**
	 * @throws UnresolvableBindableException
	 * @throws InvalidAttributeException
	 * @throws BindTargetException
	 * @throws MissingAttributeFieldException
	 * @throws BindMismatchException
	 */
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
        $result = Bind::attrs($sdm)->toAttrs($tdm)
				->props(['url1', 'url2', 'url3'], Mappers::url(true, null, false))
                ->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());
        $this->assertInstanceOf(Url::class, $tdm->req('url1'));
        $this->assertEquals('https://example.com', (string) $tdm->req('url1'));
        $this->assertInstanceOf(Url::class, $tdm->req('url2'));
        $this->assertEquals('example.com', (string) $tdm->req('url2'));
        $this->assertInstanceOf(Url::class, $tdm->req('url3'));
        $this->assertEquals('www.example.com', (string) $tdm->req('url3'));
    }

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws MissingAttributeFieldException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	function testLongUrl() {
        $longUrl = 'https://www.google.ch/maps/place/Sportanlage+Stadion+Deutweg+Winterthur/@47.4959508,8.7439466,17z/data=!3m1!4b1!4m6!3m5!1s0x479a999396b14655:0xee63d446a7b7bfd7!8m2!3d47.4959508!4d8.7465215!16s%2Fg%2F120_g2n7?entry=ttu&g_ep=EgoyMDI1MDYwMy4wIKXMDSoASAFQAw%253';
        $sdm = new DataMap(['url1' => $longUrl]);
        $tdm = new DataMap();

        $result = Bind::attrs($sdm)->toAttrs($tdm)->props(['url1'], Mappers::url(true, null, true, 2048))
                ->exec($this->getMockBuilder(MagicContext::class)->getMock());

        $this->assertTrue($result->isValid());
        $this->assertInstanceOf(Url::class, $tdm->req('url1'));
        $this->assertEquals($longUrl, (string) $tdm->req('url1'));
    }

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws MissingAttributeFieldException
	 * @throws BindMismatchException
	 */
	function testLongTruncatedUrl() {
		$truncatedUrlStr = 'https://www.google.ch/maps/place/Sportanlage+Stadion+Deutweg+Winterthur/@47.4959508,8.7439466,17z/data=!3m1!4b1!4m6!3m5!1s0x479a999396b14655:0xee63d446a7b7bfd7!8m2!3d47.4959508!4d8.7465215!16s%2Fg%2F120_g2n7?entry=ttu&g_ep=EgoyMDI1MDYwMy4wIKXMDSoASAFQAw%2';
		$resultingUrlStr = 'https://www.google.ch/maps/place/Sportanlage+Stadion+Deutweg+Winterthur/@47.4959508,8.7439466,17z/data=!3m1!4b1!4m6!3m5!1s0x479a999396b14655:0xee63d446a7b7bfd7!8m2!3d47.4959508!4d8.7465215!16s%2Fg%2F120_g2n7?entry=ttu&g_ep=EgoyMDI1MDYwMy4wIKXMDSoASAFQAw%252';
		$sdm = new DataMap(['url1' => $truncatedUrlStr]);
		$tdm = new DataMap();

		$result = Bind::attrs($sdm)->toAttrs($tdm)->props(['url1'], Mappers::url(true, maxLength: 257))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());
		$this->assertEquals($resultingUrlStr, $tdm->req('url1')->__toString());

		$result = Bind::attrs($sdm)->toAttrs($tdm)->props(['url1'], Mappers::url(true, maxLength: 256))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
		$this->assertFalse($result->isValid());

		$this->assertStringContainsString('maxlength',
				(string) $result->getErrorMap()->getChild('url1')->getMessages()[0]);
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testUrlTooLong() {
        $tooLongUrl = 'https://example.com/' . str_repeat('a', 2050);
        $sdm = new DataMap(['url1' => $tooLongUrl]);
        $tdm = new DataMap();

        $result = Bind::attrs($sdm)->toAttrs($tdm)->props(['url1'], Mappers::url(true, null, true))
                ->exec($this->getMockBuilder(MagicContext::class)->getMock());

        $this->assertFalse($result->isValid());
        $this->assertTrue($tdm->isEmpty());

        $errorMap = $result->getErrorMap();
        $this->assertCount(1, $errorMap->getChild('url1')->getMessages());
    }

	/**
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	function testCustomMaxLength() {
        $sdm = new DataMap(['url1' => 'https://example.com/' . str_repeat('a', 2050)]);
        $tdm = new DataMap();

        $result = Bind::attrs($sdm)->toAttrs($tdm)->props(['url1'], Mappers::url(true, null, true, 2500))
                ->exec($this->getMockBuilder(MagicContext::class)->getMock());

        $this->assertTrue($result->isValid());

		$sdm = new DataMap(['url1' => 'https://example.com/' . str_repeat('a', 2050)]);
		$tdm = new DataMap();

		$result = Bind::attrs($sdm)->toAttrs($tdm)->props(['url1'], Mappers::url(true, null, true, 25))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertFalse($result->isValid());
    }
} 