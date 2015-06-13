<?php

namespace Falsch\FacebookBundle\Command;

use Elastica\Document;
use Facebook\FacebookRequest;
use Falsch\FacebookBundle\Handler\ElasticHandler;
use Falsch\FacebookBundle\Handler\ElasticTransformer;
use Falsch\FacebookBundle\Handler\FacebookHandler;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author louis <louis@systemli.org>
 */
class FetchCommand extends ContainerAwareCommand
{
    const PROGRAM_NAME = 'fetch';
    const OPTION_ONLY_POSTS = 'only-posts';
    const OPTION_FETCH_LIKES = 'fetch-likes';
    const OPTION_FETCH_COMMENTS = 'fetch-comments';
    const OPTION_LIMIT = 'limit';
    const OPTION_SINCE = 'since';

    /**
     * @var int
     */
    private $limit;
    /**
     * @var int
     */
    private $since;
    /**
     * @var string
     */
    private $indexName;
    /**
     * @var InputInterface
     */
    private $input;
    /**
     * @var OutputInterface
     */
    private $output;
    /**
     * @var FacebookHandler
     */
    private $facebookHandler;
    /**
     * @var ElasticTransformer
     */
    private $elasticTransformer;
    /**
     * @var ElasticHandler
     */
    private $elasticHandler;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::PROGRAM_NAME)
            ->setDescription('Fetch a Page from Facebook')
            ->addOption(
                self::OPTION_ONLY_POSTS,
                null,
                InputOption::VALUE_NONE,
                'If set, the task fetch only posts'
            )
            ->addOption(
                self::OPTION_FETCH_LIKES,
                null,
                InputOption::VALUE_NONE,
                'If set, the task fetch the likes'
            )
            ->addOption(
                self::OPTION_FETCH_COMMENTS,
                null,
                InputOption::VALUE_NONE,
                'If set, the task fetch the comments'
            )
            ->addOption(
                self::OPTION_LIMIT,
                null,
                InputOption::VALUE_OPTIONAL,
                'Paging for Facebook Request',
                250
            )
            ->addOption(
                self::OPTION_SINCE,
                null,
                InputOption::VALUE_OPTIONAL,
                'Fetch only data since a special "strtotime" term, like "yesterday"'
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->indexName = $this->getContainer()->getParameter('fb_page_name');
        $this->input = $input;
        $this->output = $output;
        $this->limit = $input->getOption(self::OPTION_LIMIT);
        $this->facebookHandler = $this->getContainer()->get('handler.facebook');
        $this->elasticTransformer = $this->getContainer()->get('transformer.elastic');
        $this->elasticHandler = $this->getContainer()->get('handler.elastic');

        if (null !== $since = $input->getOption(self::OPTION_SINCE)) {
            $this->since = strtotime($since);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $timerStart = microtime(true);

        if (null !== $since = $input->getOption(self::OPTION_SINCE)) {
            $this->since = strtotime($since);
        }

        $posts = $this->fetchPosts();

        if (null === $posts) {
            $output->writeln('<info>No posts found.');

            return;
        }

        if ($input->getOption(self::OPTION_FETCH_LIKES) && !$input->getOption(self::OPTION_ONLY_POSTS)) {
            $this->fetchLikes($posts);
        }

        if ($input->getOption(self::OPTION_FETCH_COMMENTS) && !$input->getOption(self::OPTION_ONLY_POSTS)) {
            $this->fetchComments($posts);
        }

        $timerEnd = microtime(true);
        $duration = $timerEnd - $timerStart;

        if (OutputInterface::VERBOSITY_DEBUG === $output->getVerbosity()) {
            $output->writeln('<info>[DEBUG] Command took '. $duration .' seconds');
        }
    }

    /**
     * @return Document[]|null
     */
    private function fetchPosts()
    {
        $this->output->writeln('Fetching posts from the Facebook Page');

        $request = $this->getRequest(sprintf('/%s/posts', $this->getContainer()->getParameter('fb_page_id')));
        $data = $this->facebookHandler->fetchObjects($request);

        if (null === $data) {
            return null;
        }

        $this->output->writeln('Fetched '.count($data).' posts');

        $posts = $this->elasticTransformer->transformPosts($data);
        $this->elasticHandler->process($posts, $this->indexName, 'post');

        return $posts;
    }

    /**
     * @param Document[] $posts
     */
    private function fetchLikes(array $posts)
    {
        $this->fetchByType($posts, 'like');
    }

    /**
     * @param Document[] $posts
     */
    private function fetchComments(array $posts)
    {
        $this->fetchByType($posts, 'comment');
    }

    /**
     * @param Document[] $posts
     * @param $type
     */
    private function fetchByType(array $posts, $type)
    {
        if (!in_array($type, array('like', 'comment'))) {
            $this->output->writeln('<error>Unsupported Type!</error>');

            return;
        }

        $this->output->writeln('Fetch all '.$type.' for posts');

        foreach ($posts as $post) {
            $postId = $post->getId();

            $this->writeVerbosePostProgress($postId);

            $request = $this->getRequest(sprintf('/%s/%ss', $postId, $type));
            $objects = $this->facebookHandler->fetchObjects($request);

            if (empty($objects)) {
                if (OutputInterface::VERBOSITY_VERBOSE === $this->output->getVerbosity()) {
                    $this->output->writeln('<info>[INFO] No '.$type.' found!</info>');
                }

                continue;
            }

            if ($type === 'like') {
                $data = $this->elasticTransformer->transformLikes($objects, $postId);
            } else {
                $data = $this->elasticTransformer->transformComments($objects, $postId);
            }

            $this->elasticHandler->process($data, $this->indexName, $type);
        }
    }

    /**
     * @param $postId
     */
    private function writeVerbosePostProgress($postId)
    {
        if (OutputInterface::VERBOSITY_VERBOSE === $this->output->getVerbosity()) {
            $this->output->writeln('<fg=black;bg=cyan>[VERBOSE]</fg=black;bg=cyan> Process Post: '.$postId);
        }
    }

    /**
     * @param $path
     * @return FacebookRequest
     */
    private function getRequest($path)
    {
        return new FacebookRequest(
            FacebookHandler::$session,
            'GET',
            $path,
            $this->getRequestParams()
        );
    }

    /**
     * @return array
     */
    private function getRequestParams()
    {
        $params = array(
            self::OPTION_LIMIT => $this->limit,
        );

        if (null !== $this->since) {
            $params[self::OPTION_SINCE] = $this->since;
        }

        return $params;
    }
}
