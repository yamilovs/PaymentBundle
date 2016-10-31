<?php

namespace Yamilovs\PaymentBundle\Tests\Component\HttpFoundation;

use Symfony\Component\HttpFoundation\Response;
use Yamilovs\PaymentBundle\Component\HttpFoundation\XmlResponse;

class XmlResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return XmlResponse
     */
    private function getXmlResponse()
    {
        return $this->getMockBuilder('Yamilovs\PaymentBundle\Component\HttpFoundation\XmlResponse')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }

    public function testThatXmlResponseIsInstanceOfResponse()
    {
        $response = $this->getXmlResponse();
        $response->__construct(array());
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testXmlResponseContent()
    {
        $response = $this->getXmlResponse();
        $data = array('foo' => 'bar', 'first' => array('second' => 'level'));
        $expectedData = "<?xml version=\"1.0\"?>\n<response><foo>bar</foo><second>level</second></response>\n";
        $response->__construct(array($data));

        $this->assertEquals($expectedData, $response->getContent());
    }
}