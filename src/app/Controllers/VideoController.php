<?php
namespace App\Controllers;
use Psr\Container\ContainerInterface;

class VideoController
{
    protected $container;

    // constructor receives container instance
    public function __construct(ContainerInterface $container) {
      $this->container = $container;
    }
    public function getAll($request, $response, $args) {
      $sql = "SELECT video_id, username, title, thumb_src, views ";
      $sql .= "FROM videos JOIN users ON users.user_id = videos.user_id WHERE loaded = 1"
      $stmt = $this->container->db->prepare($sql);
      $table = array();
      if($stmt->execute()){
        while($row = $stmt->fetch()){
          $table[] = $row;
        }
      }
        return $response->withHeader('Access-Control-Allow-Origin','*')->withJson($table, 201);
    }
    
    public function get($request, $response, $args) {
      $video_id = $args['id'];
      $sql = "SELECT videos.video_id,username,title,description,video_src,views ";
      $sql .= "FROM videos JOIN users ON users.user_id = videos.user_id LEFT JOIN ratings ON ratings.video_id = videos.video_id";
      $sql .= "WHERE video_id = ? AND loaded = 1 GROUP videos.video_id LIMIT 1";
      $stmt = $this->container->db->prepare($sql);
      if($stmt->execute([$video_id])){
        $data = $stmt->fetch();
        $stmt = $this->container->db->prepare("UPDATE `videos` SET `views` = `views` + 1 WHERE `video_id` = ?");
        $stmt->execute([$video_id]);
        return $response->withHeader('Access-Control-Allow-Origin','*')->withJson($data, 201);
      }
      return $response->withHeader('Access-Control-Allow-Origin','*')->withJson(array([]), 201);
    }
}
?>
