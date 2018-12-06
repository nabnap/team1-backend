<?php
namespace App\Controllers;
use Psr\Container\ContainerInterface;


class UserController
{
    protected $container;

    // constructor receives container instance
    public function __construct(ContainerInterface $container) {
      $this->container = $container;
    }
    public function post($request, $response, $args) {
      $auth0_id = $request->getParam('authId');
      $picture = $request->getParam('picture');
      $username = $request->getParam('username');
      $sql = "INSERT INTO users (`auth0_id`,`username`, `picture`) VALUES (?,?,?) ON DUPLICATE KEY UPDATE `username` = ?, `picture` = ?";
      $stmt = $this->container->db->prepare($sql);
      $stmt->execute([$auth0_id, $username, $picture, $username, $picture]);
      return $response;
    }
}
?>
