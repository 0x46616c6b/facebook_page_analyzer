<?php

namespace Falsch\FacebookBundle\Handler;

use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;
use Facebook\FacebookSession;

/**
 * @author louis <louis@systemli.org>
 */
class FacebookHandler
{
    static $session = null;
    private $appId;
    private $appSecret;
    private $accessToken;

    /**
     * @param string $appId
     * @param string $appSecret
     * @param string $accessToken
     */
    public function __construct($appId, $appSecret, $accessToken)
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->accessToken = $accessToken;

        static::initializeSession();
    }

    /**
     * @param FacebookRequest $request
     * @return array|null
     */
    public function fetchObjects(FacebookRequest $request)
    {
        if (null === static::$session) {
            static::initializeSession();
        }

        try {
            $response = $request->execute();
            $objects = $response->getGraphObjectList();

            $next = $response->getRequestForNextPage();

            if (null !== $next) {
                $nextObjects = self::fetchObjects($next);

                if ($nextObjects) {
                    $objects = array_merge($objects, $nextObjects);
                }
            }

            return $objects;
        } catch (FacebookRequestException $e) {
            echo "Exception occurred, code: ".$e->getCode();
            echo " with message: ".$e->getMessage();
            echo PHP_EOL;

            return null;
        }
    }

    /**
     * Initialize Facebook Session
     */
    private function initializeSession()
    {
        FacebookSession::setDefaultApplication($this->appId, $this->appSecret);
        static::$session = new FacebookSession($this->accessToken);
    }
}
