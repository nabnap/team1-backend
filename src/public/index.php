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
$container = $app->getContainer();
$container['logger'] = function($c){
  $logger = new \Monolog\Logger('my_logger');
  $file_handler = new \Monolog\Handler\StreamHandler('../logs/app.log');
  $logger->pushHandler($file_handler);
  return $logger;
};

$container['db'] = function ($c) {
    $db = $c['settings']['db'];
    $pdo = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'],
        $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};

$container['HomeController'] = function($c) {
  return new HomeController();
};

$app->get('/hello/{name}', function(Request $request, Response $response, array $args){
  $name = $args['name'];
  $response->getBody()->write("Hello, $name");
  $this->logger->addInfo('Something interesting happened');
  //$stmt = $this->db->prepare("SELECT user_id FROM users");
  return $response;
});

$app->get('/helloo', \HomeController::class . ':home');
$app->run();
?>
