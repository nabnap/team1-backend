<?php
namespace App\Controllers;
use Psr\Container\ContainerInterface;

class CommentController
{
  protected $container;

    // constructor receives container instance
    public function __construct(ContainerInterface $container) {
      $this->container = $container;
    }
    public function post($request, $response, $args) {
      $auth0_id = $request->getParam('authId');
      $video_id = $request->getParam('videoId');
      $comment = $request->getParam('comment');
      $sql = "INSERT INTO comments (video_id, user_id, comment) VALUES (?,(SELECT user_id FROM users WHERE auth0_id = ?),?)";
      $stmt = $this->container->db->prepare($sql);
      $stmt->execute([$video_id, $auth0_id, $comment]);
    }
    
    public function get($request, $response, $args){
      $video_id = $args['id'];
      $sql = "SELECT comment_id, username, comment FROM comments JOIN users ON users.user_id = comments.user_id WHERE video_id = ?";
      $stmt = $this->container->db->prepare($sql);
      $data = array();
      if($stmt->execute([$video_id])){
        $data = $stmt->fetch(); 
      }
      return $request->withJson($data, 201);
    }
}
?>
