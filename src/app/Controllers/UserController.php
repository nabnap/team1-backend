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
      $sql = "INSERT IGNORE INTO users (`auth0_id`,`username`) VALUES (?,'Matt Schultz')";
      $stmt = $this->container->db->prepare($sql);
      $stmt->execute([$auth0_id]);
      return $response;
    }
}
?>
