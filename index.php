<?php
namespace App\Models;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use App\Models\Db;

//se encarga de cargar automaticamente todas las clases y dependencias definidas en tu proyecto PHP

require __DIR__ . '/vendor/autoload.php';
include ('./src/Models/Db.php');

$app = AppFactory::create();

$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Hello world!");
    return $response;
});

$app->get('/index', function (Request $request, Response $response, $args){
    $sql = "SELECT * FROM juegos";
    try{
        $db = new Db();
        $db = $db->connect();
        $resul = $db->query($sql);

        if($resul->rowCount() > 0){
            $juegos = $resul->fetchAll(\PDO::FETCH_OBJ);
            $response->getBody()->write(json_encode($juegos));
        } else {
            $response->getBody()->write(json_encode('No se encontraron juegos'));
        }
        $resul = null;
        $db = null;
        return $response;
    } catch (\PDOException $e){
        $response->getBody()->write($e->getMessage());
        return $response->withStatus(400);
    }
});

$app->run();

?>