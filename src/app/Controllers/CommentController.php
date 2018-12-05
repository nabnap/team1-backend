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
      return $response->withRedirect('/comments/' . $video_id);
    }
    
    public function get($request, $response, $args){
      $video_id = $args['id'];
      $sql = "SELECT comment_id, users.user_id, username, comment FROM comments JOIN users ON users.user_id = comments.user_id WHERE video_id = ? ORDER BY comment_id DESC";
      $stmt = $this->container->db->prepare($sql);
      
      $table = array();
      if($stmt->execute([$video_id])){
        while($row = $stmt->fetch()){
          $table[] = $row;
        }
      }
      return $response->withJson($table, 201);
        
      /*$data = array();
      if($stmt->execute([$video_id])){
        $data = $stmt->fetch(); 
      }
      return $response->withJson($data, 201);*/
    }
}
?>
