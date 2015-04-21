<?php

namespace Falsch\FacebookBundle\Handler;

use Elastica\Document;
use Facebook\GraphObject;

/**
 * @author louis <louis@systemli.org>
 */
class ElasticTransformer
{
    private $indexName;

    /**
     * @param $indexName
     */
    public function __construct($indexName)
    {
        $this->indexName = $indexName;
    }

    /**
     * @param GraphObject[] $objects
     *
     * @return array
     */
    public function transformPosts(array $objects)
    {
        $posts = array();

        foreach ($objects as $object) {
            if (!$object instanceof GraphObject) {
                continue;
            }

            $data = $object->asArray();

            $document = new Document();
            $document->setId($data['id']);
            $document->setIndex($this->indexName);
            $document->setType('post');
            $document->setData(
                array(
                    'id' => $data['id'],
                    'object_id' => isset($data['object_id']) ? $data['object_id'] : null,
                    'message' => isset($data['message']) ? $data['message'] : null,
                    'picture' => isset($data['picture']) ? $data['picture'] : null,
                    'caption' => isset($data['caption']) ? $data['caption'] : null,
                    'name' => isset($data['name']) ? $data['name'] : null,
                    'link' => isset($data['link']) ? $data['link'] : null,
                    'type' => isset($data['type']) ? $data['type'] : null,
                    'status_type' => isset($data['status_type']) ? $data['status_type'] : null,
                    'created_time' => $data['created_time'],
                    'updated_time' => $data['updated_time'],
                )
            );

            $posts[] = $document;
        }

        return $posts;
    }

    /**
     * @param GraphObject[] $objects
     * @param string $postId
     *
     * @return array
     */
    public function transformLikes(array $objects, $postId)
    {
        $likes = array();

        foreach ($objects as $object) {
            if (!$object instanceof GraphObject) {
                continue;
            }

            $data = $object->asArray();

            $document = new Document();
            $document->setId($postId.'_'.$data['id']);
            $document->setIndex($this->indexName);
            $document->setType('like');
            $document->setData(
                array(
                    'id' => $data['id'],
                    'name' => $data['name'],
                    'post_id' => $postId,
                )
            );

            $likes[] = $document;
        }

        return $likes;
    }

    /**
     * @param GraphObject[] $objects
     * @param string $postId
     *
     * @return array
     */
    public function transformComments(array $objects, $postId)
    {
        $comments = array();

        foreach ($objects as $object) {
            if (!$object instanceof GraphObject) {
                continue;
            }

            $data = $object->asArray();

            $document = new Document();
            $document->setId($data['id']);
            $document->setIndex($this->indexName);
            $document->setType('comment');
            $document->setData(
                array(
                    'id' => $data['id'],
                    'from_id' => $data['from']->id,
                    'from_name' => $data['from']->name,
                    'message' => $data['message'],
                    'created_time' => $data['created_time'],
                    'like_count' => $data['like_count'],
                    'post_id' => $postId,
                )
            );

            $comments[] = $document;
        }

        return $comments;
    }
}
