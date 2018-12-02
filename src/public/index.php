<?php
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

$container['HomeController'] = function($c) {
  return new App\Controllers\HomeController();
};

$app->get('/hello/{name}', function(Request $request, Response $response, array $args){
  //$name = $args['name'];
  //$response->getBody()->write("Hello, $name");
  $this->logger->addInfo('Something interesting happened');
  $stmt = $this->db->prepare("SELECT * FROM users");
  $table = array();
  if($stmt->execute()){
    while($row = $stmt->fetch()){
      $table[] = $row;
    }
  }

  if(sizeof($table) > 0){
    $newResponse = $response->withJson($table, 201);
  }else{
    $newResponse = $response;
  }
return $newResponse;
});

//$app->get('/helloo', \HomeController::class . ':home');

/*Routes needed to get videos*/
$app->get('/videos', function(Request $request, Response $response){
  $stmt = $this->db->prepare("SELECT `video_id`,`user_id`,`title`,`description`,`thumb_src`, `views` FROM `videos` WHERE `loaded` = 1");
  $table = array();
  if($stmt->execute()){
    while($row = $stmt->fetch()){
      $table[] = $row;
    }
  }
    return $response->withHeader('Access-Control-Allow-Origin','*')->withJson($table, 201);
});
$app->get('/videos/{id}', function(Request $request, Response $response, array $args){
  $video_id = $args['id'];
  $stmt = $this->db->prepare("SELECT `video_id`,`user_id`,`title`,`description`,`video_src`, `views` FROM `videos` WHERE `video_id` = ? AND `loaded` = 1 LIMIT 1");
  if($stmt->execute([$video_id])){
    $data = $stmt->fetch();
    $stmt = $this->db->prepare("UPDATE `videos` SET `views` = `views` + 1 WHERE `video_id` = ?");
    $stmt->execute([$video_id]);
    return $response->withHeader('Access-Control-Allow-Origin','*')->withJson($data, 201);
  }
  return $response->withHeader('Access-Control-Allow-Origin','*')->withJson(array([]), 201);
});
/*Routes needed to upload a video!*/
$app->post('/upload', function(Request $request, Response $response){
  $user_id = $request->getParam('userId');
  $title = $request->getParam('videoTitle');
  $description = $request->getParam('videoDescription');
  $stmt = $this->db->prepare("INSERT INTO videos (`user_id`,`title`,`description`) VALUES (?,?,?)");
  if($stmt->execute([$user_id,$title,$description])){
    $info['inserted_id'] = $this->db->lastInsertId();
  }
  $newResponse = $response->withHeader('Access-Control-Allow-Origin','*')->withJson($info, 201);
  return $newResponse;
});

$app->post('/upload/video', function(Request $request, Response $response){
  $user_id = $request->getParam('userId');
  $video_id = $request->getParam('videoId');
  $files = $request->getUploadedFiles();
  $video = $files['videoFile'];
  $info = array();
  if($video->getError() === UPLOAD_ERR_OK){
    $extension = pathinfo($video->getClientFilename(), PATHINFO_EXTENSION);
    //$basename = bin2hex(random_bytes(8));
    //$filename = sprintf('%s.%0.8s',$basename,$extension);
    $filename = 'video_' . $user_id . '_' . $video_id . '.' . $extension;
    $path = __DIR__ . '/uploads/videos' . DIRECTORY_SEPARATOR . $filename;
    $video->moveTo($path);

    $stmt = $this->db->prepare("UPDATE `videos` SET `video_src` = ? WHERE `video_id` = ? AND `user_id` = ?");
    $stmt->execute([$filename, $video_id, $user_id]);
    $info['ext'] = $extension;
    $info['filename'] = $filename;
    $info['path'] = $path;
  }
  return $response->withHeader('Access-Control-Allow-Origin','*')->withJson($info, 201);
});

$app->post('/upload/thumb', function(Request $request, Response $response){
  $user_id = $request->getParam('userId');
  $video_id = $request->getParam('videoId');
  $files = $request->getUploadedFiles();

  $thumb = $files['thumbFile'];
  $info = array();
  if($thumb->getError() === UPLOAD_ERR_OK){
    $extension = pathinfo($thumb->getClientFilename(), PATHINFO_EXTENSION);
    $filename = 'thumb_' . $user_id . '_' . $video_id . '.' . $extension;
    $path = __DIR__ . '/uploads/thumbs' . DIRECTORY_SEPARATOR . $filename;
    $thumb->moveTo($path);

    $stmt = $this->db->prepare("UPDATE `videos` SET `thumb_src` = ?, `loaded` = 1 WHERE `video_id` = ? AND `user_id` = ?");
    $stmt->execute([$filename, $video_id, $user_id]);

    $info['ext'] = $extension;
    $info['filename'] = $filename;
    $info['path'] = $path;
  }

  return $response->withHeader('Access-Control-Allow-Origin','*')->withJson($info, 201);
});

$app->run();
?>
