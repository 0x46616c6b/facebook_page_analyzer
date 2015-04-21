<?php


namespace Falsch\FacebookBundle\Handler;

use Elastica\Client;
use Elastica\Connection;
use Elastica\Document;
use Elastica\Exception\ResponseException;
use Elastica\Index;
use Elastica\Type;

/**
 * @author louis <louis@systemli.org>
 */
class ElasticHandler
{
    /**
     * @var string
     */
    private $host;
    /**
     * @var int
     */
    private $port;

    /**
     * Constructor
     *
     * @param $host
     * @param $port
     */
    public function __construct($host, $port)
    {
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * @param Document[] $objects
     * @param string $name
     * @param string $type
     */
    public function process(array $objects, $name, $type)
    {
        try {
            $type = new Type($this->getIndex($name), $type);
            $type->addDocuments($objects);
        } catch (ResponseException $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }

    /**
     * @param $name
     * @return Index
     */
    private function getIndex($name)
    {
        return new Index(new Client(array('host' => $this->host, 'port' => $this->port)), $name);
    }
}
