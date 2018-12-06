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
      $sql = "SELECT video_id, users.user_id,username, title, thumb_src, views ";
      $sql .= "FROM videos JOIN users ON users.user_id = videos.user_id WHERE loaded = 1";
      $stmt = $this->container->db->prepare($sql);
      $table = array();
      if($stmt->execute()){
        while($row = $stmt->fetch()){
          $table[] = $row;
        }
      }
        return $response->withJson($table, 201);
    }
    
    public function get($request, $response, $args) {
      $video_id = $args['id'];
      $sql = "SELECT videos.video_id,users.user_id,picture,username,title,description,video_src,views, IFNULL(SUM(liked), 0) AS likes, COUNT(liked) AS total ";
      $sql .= "FROM videos JOIN users ON users.user_id = videos.user_id LEFT JOIN ratings ON ratings.video_id = videos.video_id ";
      $sql .= "WHERE videos.video_id = ? AND loaded = 1 GROUP BY videos.video_id LIMIT 1";
      $stmt = $this->container->db->prepare($sql);
      if($stmt->execute([$video_id])){
        $data = $stmt->fetch();
        $stmt = $this->container->db->prepare("UPDATE `videos` SET `views` = `views` + 1 WHERE `video_id` = ?");
        $stmt->execute([$video_id]);
        return $response->withJson($data, 201);
      }
      return $response->withJson(array([]), 201);
    }

    public function filter($request, $response, $args){
      $by = $args['by'];
      $sql = "";
      switch($by){
        case "views":
          $sql = "SELECT videos.video_id, users.user_id, username, title, thumb_src, views ";
          $sql .= "FROM videos JOIN users ON users.user_id = videos.user_id ";
          $sql .= "WHERE loaded = 1 ORDER BY views DESC LIMIT 5";
          break;
        case "ratings":
          $sql = "SELECT videos.video_id, users.user_id, username, title, thumb_src, views, IFNULL(SUM(liked),0) AS likes ";
          $sql .= "FROM videos JOIN users ON users.user_id = videos.user_id LEFT JOIN ratings ON ratings.video_id = videos.video_id ";
          $sql .= "WHERE loaded = 1 GROUP BY videos.video_id ORDER BY likes DESC LIMIT 5";
          break;
        case "new":
          $sql = "SELECT videos.video_id, users.user_id, username, title, thumb_src, views ";
          $sql .= "FROM videos JOIN users ON users.user_id = videos.user_id ";
          $sql .= "WHERE loaded = 1 ORDER BY video_id DESC LIMIT 5";
          break;
        default:
          return $response->withRedirect('/videos');
      }

      $stmt = $this->container->db->prepare($sql);
      $table = array();
      if($stmt->execute()){
        while($row = $stmt->fetch()){
          $table[] = $row;
        }
      }
      return $response->withJson($table, 201);
    }
}
?>
