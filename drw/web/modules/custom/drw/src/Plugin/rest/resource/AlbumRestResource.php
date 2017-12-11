<?php

namespace Drupal\drw\Plugin\rest\resource;

use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\node\Entity\Node;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "album_rest_resource",
 *   label = @Translation("Album rest resource"),
 *   uri_paths = {
 *     "canonical" = "/albums"
 *   }
 * )
 */
class AlbumRestResource extends ResourceBase {

    /**
     * A current user instance.
     *
     * @var \Drupal\Core\Session\AccountProxyInterface
     */
    protected $currentUser;

    protected $database;
    protected $fileDir;

    /**
     * Constructs a new AlbumRestResource object.
     *
     * @param array $configuration
     *   A configuration array containing information about the plugin instance.
     * @param string $plugin_id
     *   The plugin_id for the plugin instance.
     * @param mixed $plugin_definition
     *   The plugin implementation definition.
     * @param array $serializer_formats
     *   The available serialization formats.
     * @param \Psr\Log\LoggerInterface $logger
     *   A logger instance.
     * @param \Drupal\Core\Session\AccountProxyInterface $current_user
     *   A current user instance.
     */
    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        array $serializer_formats,
        LoggerInterface $logger,
        AccountProxyInterface $current_user,
        Connection $database,
        PublicStream $stream
    ) {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

        $this->currentUser = $current_user;
        $this->database = $database;
        $this->fileDir = $stream->getDirectoryPath();
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->getParameter('serializer.formats'),
            $container->get('logger.factory')->get('drw'),
            $container->get('current_user'),
            $container->get('database'),
            $container->get('stream_wrapper.public')
        );
    }

    /**
     * Responds to GET requests.
     *
     * Returns a list of bundles for specified entity.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws exception expected.
     */
    public function get() {

        // You must to implement the logic of your REST Resource here.
        // Use current user after pass authentication to validate access.
        if (!$this->currentUser->hasPermission('access content')) {
            throw new AccessDeniedHttpException();
        }

        // Let's collect the albums from our collection
        // If we can't find any, we'll return a BadRequestHttpException
        try {
            $query = \Drupal::entityQuery('node');
            $query->condition('type', 'album')
                ->condition('status', 1);

            $albums_nids = $query->execute();

            $albums = Node::loadMultiple($albums_nids);

            /*
                  $query = $this->database->select('node_field_data', 'nfd');
                  $query->condition('nfd.type', 'album');

                  $query->join('node__field_artist', 'n_fa', 'n_fa.entity_id = nfd.nid');
            //    $query->join('node__field_released', 'n_fr', 'n_fr.entity_id = nfd.nid');
                  $query->join('node__field_cover', 'n_fc', 'n_fc.entity_id = nfd.nid');
                  $query->join('file_managed', 'f', 'f.fid = n_fc.field_cover_target_id');

                  $query->addField('nfd', 'title');
                  $query->addField('n_fa', 'field_artist_value', 'artist');
            //    $query->addExpression("DATE_FORMAT(n_fr.field_released_date, '%Y')", 'released');
                  $query->addExpression("REPLACE(f.uri, 'public:/', ':base_path')", 'cover', [':base_path', $this->fileDir]);

                  // You can use getQueryString() to see the raw query,
                  // for optimization and debugging.
                  $string = $query->execute()->getQueryString();

                  //$albums = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
            */

            // Create a resource to configure our caching and depencencies.
            $response = new ResourceResponse(['albums' => $albums]);
//      $response = new ResourceResponse(['albums' => $string]);

            // Let's set the cache of this resource to 1 day
            $response->setMaxAge(strtotime('1 day', 0));

            return $response;
        }
        catch (\Exception $e) {
            throw new BadRequestHttpException($this->t('Could not find any albums: ' . $e->getMessage()), $e);
        }
    }

}