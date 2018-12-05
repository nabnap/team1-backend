<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';

$config = require '../app/config.php';
$app = new \Slim\App($config);
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

$app->add(function ($req, $res, $next) {
    $response = $next($req, $res);
    return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

$container['VideoController'] = function($c) {
  return new App\Controllers\VideoController($c);
};
$container['UserController'] = function($c) {
  return new App\Controllers\UserController($c);
};
$container['RatingController'] = function($c) {
  return new App\Controllers\RatingController($c);
};
$container['CommentController'] = function($c) {
  return new App\Controllers\CommentController($c);
};
//$app->get('/helloo', \HomeController::class . ':home');
$app->get('/videos', \VideoController::class . ':getAll');
$app->get('/videos/{id}', \VideoController::class . ':get');
$app->post('/users',  \UserController::class . ':post');
$app->post('/ratings', \RatingController::class . ':post');
$app->post('/comments', \CommentController::class . ':post');
$app->get('/comments/{id}', \CommentController::class . ':get');

/*Routes needed to upload a video!*/
$app->post('/upload', function(Request $request, Response $response){
  $auth0_id = $request->getParam('userId');
  $title = $request->getParam('videoTitle');
  $description = $request->getParam('videoDescription');
  $stmt = $this->db->prepare("INSERT INTO videos (`user_id`,`title`,`description`) VALUES ((SELECT user_id FROM users WHERE auth0_id = ?),?,?)");
  if($stmt->execute([$auth0_id,$title,$description])){
    $info['inserted_id'] = $this->db->lastInsertId();
  }
  $newResponse = $response->withHeader('Access-Control-Allow-Origin','*')->withJson($info, 201);
  return $newResponse;
});

$app->post('/upload/video', function(Request $request, Response $response){
  $video_id = $request->getParam('videoId');
  $files = $request->getUploadedFiles();
  $video = $files['videoFile'];
  $info = array();
  if($video->getError() === UPLOAD_ERR_OK){
    $extension = pathinfo($video->getClientFilename(), PATHINFO_EXTENSION);
    //$basename = bin2hex(random_bytes(8));
    //$filename = sprintf('%s.%0.8s',$basename,$extension);
    $filename = 'video_' . $video_id . '.' . $extension;
    $path = __DIR__ . '/uploads/videos' . DIRECTORY_SEPARATOR . $filename;
    $video->moveTo($path);

    $stmt = $this->db->prepare("UPDATE `videos` SET `video_src` = ? WHERE `video_id` = ?");
    $stmt->execute([$filename, $video_id]);
    $info['ext'] = $extension;
    $info['filename'] = $filename;
    $info['path'] = $path;
  }
  return $response->withJson($info, 201);
});

$app->post('/upload/thumb', function(Request $request, Response $response){
  $video_id = $request->getParam('videoId');
  $files = $request->getUploadedFiles();

  $thumb = $files['thumbFile'];
  $info = array();
  if($thumb->getError() === UPLOAD_ERR_OK){
    $extension = pathinfo($thumb->getClientFilename(), PATHINFO_EXTENSION);
    $filename = 'thumb_' . $video_id . '.' . $extension;
    $path = __DIR__ . '/uploads/thumbs' . DIRECTORY_SEPARATOR . $filename;
    $thumb->moveTo($path);

    $stmt = $this->db->prepare("UPDATE `videos` SET `thumb_src` = ?, `loaded` = 1 WHERE `video_id` = ?");
    $stmt->execute([$filename, $video_id]);

    $info['ext'] = $extension;
    $info['filename'] = $filename;
    $info['path'] = $path;
  }

  return $response->withJson($info, 201);
});

$app->run();
?>
