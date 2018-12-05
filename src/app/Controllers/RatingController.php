<?php
namespace App\Controllers;
use Psr\Container\ContainerInterface;

class RatingController
{
  protected $container;

    // constructor receives container instance
    public function __construct(ContainerInterface $container) {
      $this->container = $container;
    }
    public function post($request, $response, $args) {
      $auth0_id = $request->getParam('authId');
      $video_id = $request->getParam('videoId');
      $liked = $request->getParam('liked');
      $sql = "INSERT INTO ratings (video_id, user_id, liked) VALUES (?,(SELECT user_id FROM users WHERE auth0_id = ?),?) ON DUPLICATE KEY UPDATE liked = ?";
      $stmt = $this->container->db->prepare($sql);
      $stmt->execute([$video_id, $auth0_id, $liked, $liked]);
      return $response;
    }
}
?>
