<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../../vendor/autoload.php';

$config['displayErrorDetails'] = true;
$config['addContentLengthHeader'] = false;
$config['db']['host'] = '192.34.63.80';
$config['db']['user'] = '';
$config['db']['pass'] = '';
$config['db']['dbname'] = 'yetube';
$app = new \Slim\App(['settings' => $config]);

$app->get('/hello/{name}', function(Request $request, Response $response, array $args){
  $name = $args['name'];
  $response->getBody()->write("Hello, $name");
  return $response;
});
$app->run();
?>
