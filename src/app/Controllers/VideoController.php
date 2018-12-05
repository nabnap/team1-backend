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
      $stmt = $this->container->db->prepare("SELECT `video_id`,`user_id`,`title`,`description`,`thumb_src`, `views` FROM `videos` WHERE `loaded` = 1");
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
      $stmt = $this->container->db->prepare("SELECT `video_id`,`user_id`,`title`,`description`,`video_src`, `views` FROM `videos` WHERE `video_id` = ? AND `loaded` = 1 LIMIT 1");
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
