<?php

namespace Yamilovs\PaymentBundle\Component\HttpFoundation;

use Symfony\Component\HttpFoundation\Response;

class XmlResponse extends Response
{
    /**
     * Constructor.
     *
     * @param mixed  $data      The response data
     * @param string $rootNode  The xml root node
     * @param int    $status    The response status code
     * @param array  $headers   An array of response headers
     */
    public function __construct(array $data, $rootNode = 'response', $status = 200, $headers = array())
    {
        $xml = new \SimpleXMLElement("<$rootNode/>");
        array_walk_recursive(
            $data,
            function ($value, $key) use ($xml) {
                $xml->addChild($key, $value);
            }
        );
        $response = new Response($xml->asXML());
        $response->headers->set('Content-Type', 'text/xml');
        parent::__construct($xml->asXML(), $status, $headers);
    }
}