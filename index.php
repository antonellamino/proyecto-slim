<?php
namespace App\Models;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use App\Models\Db;

//se encarga de cargar automaticamente todas las clases y dependencias definidas en tu proyecto PHP

require __DIR__ . '/vendor/autoload.php';
include ('./src/Models/Db.php');

$db = new Db();
$db = $db->connect();

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Hello world!");
    return $response;
});


//A) CREAR UN NUEVO GENERO
$app->post('/genero', function(Request $request, Response $response){ //post porque es para crear //agrega aun cuando hay error
    // $generoInsertar = $args["genero"]; es una forma de hacerlo poniengo $args = [] como parametro de la funcion cuando viene por 
    global $db;
    $nuevoGenero = $request->getParsedBody(); //QUE CHEQUEOS TENGO QUE AGREGAR
    try{
        $cons = $db->prepare($sql = "INSERT INTO generos (nombre) VALUES (?)");
        $cons->bindParam(1, $nuevoGenero['nombre']);
        $cons->execute();
        $response->getBody()->write(json_encode('Se agrego un genero'));
        return $response->withStatus(200);
    } catch (\PDOException $err){
        $response->getBody()->write($err->getMessage());
        return $response->withStatus(400); 
    }
    
});


//B) ACTUALIZAR INFORMACION DE UN GENERO
$app->put('/genero/{id}', function(Request $request, Response $response){ //funciona si mando el id por url, no me actualiza el nombre, el form data solo lo parsea con post, para put usar raw json o x
    global $db;
    $campos = $request->getParsedBody();

    try{
        $id = $request->getAttribute('id'); //cuando el parametro viene en la url
        $cons = $db->prepare("SELECT * FROM generos WHERE id=?"); //el prepare prepara una plantilla de la sentencia sql que se envia a la bd con valores sin especificar (?), se guarda el resultado sin ejecutarlo
        $cons->bindParam(1, $id); //enlaza los parametros con la consulta sql y le dice a la bd que parametros son
        $cons->execute(); //con execute la app enlaza los valores con los parametros que habia quedado con ? en la consulta sql, se puede ejecutar con param diferentes tantas veces como se quiera
        
        if($cons->rowCount() != 0){
            $cons = $db->prepare("UPDATE generos SET  nombre=? WHERE id=?");
            $cons->bindParam(1, $campos['nombre']);
            $cons->bindParam(2, $id);
            $cons->execute();

            $response->getBody()->write((json_encode('Se actualizo un genero')));
            return $response->withStatus(200); 
        } else {
            $response->getBody()->write(json_encode('No se encontro un genero con el id: ' . $id));
            return $response->withStatus(400); 
        }

    } catch (\PDOException $err){
        $response->getBody()->write($err->getMessage());
        return $response->withStatus(400); 
    }
});



//C) ELIMINAR UN GENERO
//ok
//faltan chequeos, preguntar
$app->delete('/genero/{id}', function(Request $request, Response $response){
    global $db;
    
    try{
        $id = $request->getAttribute('id');
        $cons = $db->prepare("SELECT * FROM generos WHERE id=?");
        $cons->bindParam(1, $id);
        $cons->execute();

        if($cons->rowCount() != 0){
            $cons = $db->prepare("DELETE FROM generos WHERE id=?");
            $cons->bindParam(1, $id);
            $cons->execute();

            $response->getBody()->write(json_encode('Se elimino un genero'));
            return $response->withStatus(200);
        } else {
            $response->getBody()->write(json_encode('No se encontro el genero con id: ' . $id));
            return $response->withStatus(400);
        }
    } catch (\PDOException $err){
        $response->getBody()-write($err->getMessage());
        return $response->withStatus(400);
    }
});


//D) OBTENER TODOS LOS GENEROS
//OK
$app->get('/genero', function(Request $request, Response $response){
    global $db;
    $sql = "SELECT * FROM generos";
    try{
        $resul = $db->query($sql);
        if($resul->rowCount() > 0){
            $generos = $resul->fetchAll(\PDO::FETCH_OBJ); //PDO::FETCH_OBJ - Obtiene la siguiente fila y la devuelve como un objeto
            $response->getBody()->write(json_encode($generos));
        } else {
            $response->getBody()->write(json_encode('No se encontraron generos'));
        }
        $resul = null;
        $db = null;
        return $response;
    } catch (\PDOException $e){
        $response->getBody()->write($e->getMessage());
        return $response->withStatus(200);
    }
});


//E) CREAR UNA NUEVA PLATAFORMA
//ok
//faltan chequeos
$app->post('/plataforma', function(Request $request, Response $response){
    global $db;
    $nuevaPlataforma = $request->getParsedBody();
    try{
        $cons = $db->prepare("INSERT INTO plataformas (nombre) VALUES (?)");
        $cons->bindPAram(1, $nuevaPlataforma['nombre']);
        $cons->execute();
        $response->getBody()->write(json_encode('Se agrego una plataforma'));
        return $response->withStatus(200);
    } catch (\PDOException $err){
        $response->getBody()->write($err->getMessage());
        return $response->withStatus(400);
    }
});


//F) ACTUALIZAR INFORMACION DE UNA PLATAFORMA
//ok, para probar usar lo mismo que en genero
$app->put('/plataforma/{id}', function(Request $request, Response $response){
    global $db;
    $campos = $request->getParsedBody();

    try{
        $id = $request->getAttribute('id');
        $cons = $db->prepare("SELECT * FROM plataformas WHERE id=?"); 
        $cons->bindParam(1, $id);
        $cons->execute();
        
        if($cons->rowCount() != 0){
            $cons = $db->prepare("UPDATE plataformas SET  nombre=? WHERE id=?");
            $cons->bindParam(1, $campos['nombre']);
            $cons->bindParam(2, $id);
            $cons->execute();

            $response->getBody()->write((json_encode('Se actualizo una plataforma')));
            return $response->withStatus(200); 
        } else {
            $response->getBody()->write(json_encode('No se encontro una plataforma con el id: ' . $id));
            return $response->withStatus(400); 
        }

    } catch (\PDOException $err){
        $response->getBody()->write($err->getMessage());
        return $response->withStatus(400); 
    }
});


//G) ELIMINAR UNA PLATAFORMA
//OK
//VER CHEQUEOS AS USUAL
$app->delete('/plataforma/{id}', function(Request $request, Response $response){
    global $db;
    
    try{
        $id = $request->getAttribute('id');
        $cons = $db->prepare("SELECT * FROM plataformas WHERE id=?");
        $cons->bindParam(1, $id);
        $cons->execute();

        if($cons->rowCount() != 0){
            $cons = $db->prepare("DELETE FROM plataformas WHERE id=?");
            $cons->bindParam(1, $id);
            $cons->execute();

            $response->getBody()->write(json_encode('Se elimino una plataforma'));
            return $response->withStatus(200);
        } else {
            $response->getBody()->write(json_encode('No se encontro la plataforma con id: ' . $id));
            return $response->withStatus(400);
        }
    } catch (\PDOException $err){
        $response->getBody()-write($err->getMessage());
        return $response->withStatus(400);
    }
});


//H) OBTENER TODAS LAS PLATAFORMAS ASUMO
//ok
$app->get('/plataforma', function(Request $request, Response $response){
    global $db;
    $sql = "SELECT * FROM plataformas";
    try{
        $resul = $db->query($sql);
        if($resul->rowCount() > 0){
            $plataformas = $resul->fetchAll(\PDO::FETCH_OBJ); //PDO::FETCH_OBJ - Obtiene la siguiente fila y la devuelve como un objeto
            $response->getBody()->write(json_encode($plataformas));
        } else {
            $response->getBody()->write(json_encode('No se encontraron plataformas'));
        }
        $resul = null;
        $db = null;
        return $response;
    } catch (\PDOException $e){
        $response->getBody()->write($e->getMessage());
        return $response->withStatus(200);
    }
});


//I) CREAR UN NUEVO JUEGO
$app->post('/juegos', function(Request$request, Response $response){
    global $db;
    $campos = $request->getParsedBody();

    try{
        $cons = $db->prepare("INSERT INTO juegos (nombre, imagen, descripcion, url, genero, plataforma) VALUES (?,?,?,?,?,?)");
        $cons->bindParam(1, $campos['nombre']);
        $cons->bindParam(2, $campos['imagen']);
        $cons->bindParam(3, $campos['descripcion']);
        $cons->bindParam(4, $campos['url']);
        $cons->bindParam(5, $campos['genero']);
        $cons->bindParam(6, $campos['plataforma']);
        $cons->execute();

        $response->getBody()->write(json_encode('Se agrego un juego'));
        return $response->withStatus(200);
    }catch (\PDOException $e){
        $response->getBody()->write($e->getMessage());
        return $response->withStatus(200);
    }
});


//J) ACTUALIZAR UN JUEGO
//lo mismo que en agregar juego, no se como hacer con algunos campos
// $appp->put('/juegos/{id}', function(Request$request, Response $response){
//     global $db;
//     $campos = $request->getParsedBody();

//     try{
//         $id = $request->getAttribute('id');
//         $id = $request->getAttribute('id'); //cuando el parametro viene en la url
//         $cons = $db->prepare("SELECT * FROM juegos WHERE id=?"); 
//         $cons->bindParam(1, $id);
//         $cons->execute();

//         if($cons->rowCount() != 0){
//             $cons = $db->prepare("UPDATE juegos SET  nombre=? WHERE id=?");
//             $cons->bindParam(1, $campos['nombre']);
//             $cons->bindParam(2, $id);
//             $cons->execute();

//             $response->getBody()->write((json_encode('Se actualizo una plataforma')));
//             return $response->withStatus(200); 
//         } else {
//             $response->getBody()->write(json_encode('No se encontro una plataforma con el id: ' . $id));
//             return $response->withStatus(400); 
//         }

//     } catch (\PDOException $err){
//         $response->getBody()->write($err->getMessage());
//         return $response->withStatus(400); 
//     }
// });


//K) ELIMINAR UN JUEGO
$app->delete('/juegos/{id}', function(Request $request, Response $response){
    global $db;
    
    try{
        $id = $request->getAttribute('id');
        $cons = $db->prepare("SELECT * FROM juegos WHERE id=?");
        $cons->bindParam(1, $id);
        $cons->execute();

        if($cons->rowCount() != 0){
            $cons = $db->prepare("DELETE FROM juegos WHERE id=?");
            $cons->bindParam(1, $id);
            $cons->execute();

            $response->getBody()->write(json_encode('Se elimino un juego'));
            return $response->withStatus(200);
        } else {
            $response->getBody()->write(json_encode('No se encontro el juego con id: ' . $id));
            return $response->withStatus(400);
        }
    } catch (\PDOException $err){
        $response->getBody()-write($err->getMessage());
        return $response->withStatus(400);
    }
});



//L) OBTENER TODOS LOS JUEGOS
$app->get('/juegos', function (Request $request, Response $response, $args){
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
        return $response->withStatus(200);
    }
});


$app->run();

//CONSULTA: COMO ES MEJOR PONERLE A LAS URL API
//CHEQUEOS QUE HAY QUE HACER
//HAY QUE DEVOLVER LOS MENSAJES PERSONALIZADOS? O ALCANZA CON EL RESPONSE
//uso el catch? o esta mal? si no dejar asi
?>

