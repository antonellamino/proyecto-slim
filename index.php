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
$app->addBodyParsingMiddleware();

$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Hello world!");
    return $response;
});


//A) CREAR UN NUEVO GENERO
//probar en el body, x-www con el nombre a agregar
//si mando dos param lo agrega igual
$app->post('/generos', function(Request $request, Response $response){ //post porque es para crear //agrega aun cuando hay error
    // $generoInsertar = $args["genero"]; es una forma de hacerlo poniengo $args = [] como parametro de la funcion cuando viene por
    $db = new Db();
    $db = $db->connect();
    $nuevoGenero = $request->getParsedBody(); //agregar chequeo para vacio?
    $respuesta = array();
    $respuesta["exito"] = true;
    try{
        $cons = $db->prepare($sql = "INSERT INTO generos (nombre) VALUES (?)");
        $cons->bindParam(1, $nuevoGenero['nombre']);
        $cons->execute();

        $json = array('mensaje' => 'Se agrego un genero', 'exito' => true);
        $response->getBody()->write(json_encode($json));
        return $response->withStatus(200);
    } catch (\PDOException $e){
        $response->getBody()->write($e->getMessage());
        return $response->withStatus(500); 
    }
    
});



//B) ACTUALIZAR INFORMACION DE UN GENERO
//anda, prueba con x-www el con el nombre nuevo
//si no se pone parametro tira error 500, hago algo mas con eso?
$app->put('/generos/{id}', function(Request $request, Response $response){ //funciona si mando el id por url, no me actualiza el nombre, el form data solo lo parsea con post, para put usar raw json o x
    $db = new Db();
    $db = $db->connect();
    $campos = $request->getParsedBody();
    $respuesta = array();
    $respuesta["exito"] = true;
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

            $json = array('mensaje' => 'Se actualizo un genero', 'exito' => true);
            $response->getBody()->write((json_encode($json)));
            return $response->withStatus(200); 
        } else {
            $json = array('mensaje' => 'No se encontro un genero con el id: '. $id, 'exito' => false);
            $response->getBody()->write((json_encode($json)));
            return $response->withStatus(400); 
        }

    } catch (\PDOException $e){
        $json = array('mensaje' => $e, 'exito' => false);
        $response->getBody()->write((json_encode($json)));
        return $response->withStatus(500); 
    }
});



//C) ELIMINAR UN GENERO //VER//
//ok
//que pasa si no se envia parametro
$app->delete('/generos/{id}', function(Request $request, Response $response){
    $db = new Db();
    $db = $db->connect();
    try{
        $id = $request->getAttribute('id');
        //traigo los juegos
        $juegos = $db->prepare("SELECT * FROM juegos WHERE id_genero=?");
        $juegos->bindParam(1, $id);
        $juegos->execute();

        //traigo los generos para chequear que exista y eliminar
        $gen = $db->prepare("SELECT * FROM generos WHERE id=?");
        $gen->bindParam(1, $id);
        $gen->execute();

        //si el id esta siendo usado, no se puede eliminar
        if ($juegos->rowCount() > 0){
            $json = array('mensaje' => 'Genero en uso, no se puede eliminar', 'exito' => false);
            $response->getBody()->write((json_encode($json)));
            return $response->withStatus(400);
        } else if ($gen->rowCount() == 0){
            $json = array('mensaje' => 'No se encontro el genero con id: ' . $id, 'exito' => false);
            $response->getBody()->write((json_encode($json)));
            return $response->withStatus(400);
        } else { //si existe y no esta en uso se elimina
            $gen = $db->prepare("DELETE FROM generos WHERE id=?");
            $gen->bindParam(1, $id);
            $gen->execute();
            
            $json = array('mensaje' => 'Se elimino el genero con id: ' . $id, 'exito' => false);
            $response->getBody()->write((json_encode($json)));
            return $response->withStatus(200);
        }
    } catch (\PDOException $err){
        $json = array('mensaje' => $e->getMessage(), 'exito' => false);
        $response->getBody()->write((json_encode($json)));
        return $response->withStatus(500);
    }
});



//D) OBTENER TODOS LOS GENEROS
//OK
$app->get('/generos', function(Request $request, Response $response){
    $db = new Db();
    $db = $db->connect();
    $sql = "SELECT * FROM generos";
    try{
        $resul = $db->query($sql);
        if($resul->rowCount() > 0){
            $generos = $resul->fetchAll(\PDO::FETCH_OBJ); //PDO::FETCH_OBJ - Obtiene la siguiente fila y la devuelve como un objeto
            $json = array('mensaje' => 'Generos disponibles', 'exito' => true, 'Generos: ' => $generos);
            $response->getBody()->write((json_encode($json)));
            return $response->withStatus(200);
        } else {
            $json = array('mensaje' => 'No se encontraron generos', 'exito' => false);
            $response->getBody()->write((json_encode($json)));
            return $response->withStatus(400);
        }
        return $response;
    } catch (\PDOException $e){
        $json = array('mensaje' => $e->getMessage(), 'exito' => false);
        $response->getBody()->write((json_encode($json)));
        return $response->withStatus(500);
    }
});


//E) CREAR UNA NUEVA PLATAFORMA
//ok, probar sin barra al final si no da error, por x-www el nombre a crear
//faltan chequeos, q pasa si ya existe el nombre
//no esta especificado, como yo quiera
$app->post('/plataformas', function(Request $request, Response $response){
    $db = new Db();
    $db = $db->connect();
    $nuevaPlataforma = $request->getParsedBody();
    try{
        $cons = $db->prepare("INSERT INTO plataformas (nombre) VALUES (?)");
        $cons->bindPAram(1, $nuevaPlataforma['nombre']);
        $cons->execute();

        $json = array('mensaje' => 'Se agrego una plataforma', 'exito' => true);
        $response->getBody()->write((json_encode($json)));
        return $response->withStatus(200);
    } catch (\PDOException $e){
        $json = array('mensaje' => $e->getMessage(), 'exito' => false);
        $response->getBody()->write((json_encode($json)));
        return $response->withStatus(500);
    }
});



//F) ACTUALIZAR INFORMACION DE UNA PLATAFORMA
//ok, para probar, enviar por x-www el nombre a actualizar
//si no se envia nada, error 500, esta ok?
$app->put('/plataformas/{id}', function(Request $request, Response $response){
    $db = new Db();
    $db = $db->connect();
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

            $json = array('mensaje' => 'Se actualizo una plataforma', 'exito' => true);
            $response->getBody()->write((json_encode($json)));
            return $response->withStatus(200);
        } else {
            $json = array('mensaje' => 'No se encontro una plataforma con el id: ' . $id, 'exito' => false);
            $response->getBody()->write((json_encode($json)));
            return $response->withStatus(400);
        }

    } catch (\PDOException $err){
        $json = array('mensaje' => $e->getMessage(), 'exito' => false);
        $response->getBody()->write((json_encode($json)));
        return $response->withStatus(500); 
    }
});


//G) ELIMINAR UNA PLATAFORMA    //VER//
//ok
//ver que pasa cuando no se envia una plat
$app->delete('/plataformas/{id}', function(Request $request, Response $response){
    $db = new Db();
    $db = $db->connect();
    
    try{
        $id = $request->getAttribute('id');
        //traigo los juegos
        $juegos = $db->prepare("SELECT * FROM juegos WHERE id_plataforma=?");
        $juegos->bindParam(1, $id);
        $juegos->execute();

        //traigo las plataformas para chequear que exista y eliminar
        $plat = $db->prepare("SELECT * FROM plataformas WHERE id=?");
        $plat->bindParam(1, $id);
        $plat->execute();

        //si el id esta siendo usado, no se puede eliminar
        if ($juegos->rowCount() > 0){
            $response->getBody()->write(json_encode('Plataforma en uso, no se puede eliminar'));
            return $response->withStatus(400);
            //si el id no existe, tampoco
        } else if ($plat->rowCount() == 0){
            $json = array('mensaje' => 'No se encontro una plataforma con el id: ' . $id, 'exito' => false);
            $response->getBody()->write((json_encode($json)));
            return $response->withStatus(400);
        } else { //si existe y no esta en uso se elimina
            $plat = $db->prepare("DELETE FROM plataformas WHERE id=?");
            $plat->bindParam(1, $id);
            $plat->execute();
            $json = array('mensaje' => 'Se elimino la plataforma con id:' . $id, 'exito' => false);
            $response->getBody()->write((json_encode($json)));
            return $response->withStatus(200);
        }
    } catch (\PDOException $e){
        $json = array('mensaje' => $e->getMessage(), 'exito' => false);
        $response->getBody()->write((json_encode($json)));
        return $response->withStatus(500);
    }
});



//H) OBTENER TODAS LAS PLATAFORMAS ASUMO
//OK
$app->get('/plataformas', function(Request $request, Response $response){
    $db = new Db();
    $db = $db->connect();
    $sql = "SELECT * FROM plataformas";
    try{
        $resul = $db->query($sql);
        if($resul->rowCount() > 0){
            $plataformas = $resul->fetchAll(\PDO::FETCH_OBJ); //PDO::FETCH_OBJ - Obtiene la siguiente fila y la devuelve como un objeto

            $json = array('mensaje' => 'Generos disponibles', 'exito' => true, 'Generos: ' => $plataformas);
            $response->getBody()->write((json_encode($json)));
            return $response->withStatus(200);
        } else {
            $json = array('mensaje' => 'No se encontraron plataformas', 'exito' => false);
            $response->getBody()->write((json_encode($json)));
            return $response->withStatus(400);
        }
    } catch (\PDOException $e){
        $response->getBody()->write($e->getMessage());
        return $response->withStatus(500);
    }
});


//I) CREAR UN NUEVO JUEGO   //ver//
//agregar los chequeos pertinentes como con js y php
//si mando todos los datos, todo bien
//probar con form-data, mandar los key y value y que coincidan los nombres de campos
$app->post('/juegos', function(Request$request, Response $response){
    $db = new Db();
    $db = $db->connect();
    $campos = $request->getParsedBody();

    try{
        //chequeo nombre
        $respuesta = array();
        $respuesta["exito"] = true;
        $respuesta["errores"] = array();

        if(!isset($campos['nombre']) or $campos['nombre'] == ""){
            $respuesta["exito"] = false;
            array_push($respuesta["errores"], "Se debe introducir un nombre");
        }

        //chequeo imagen, preguntar si asumo q viene en base 64
        if(!isset($campos['imagen']) or $campos['imagen'] == ""){
            array_push($respuesta["errores"], "Se debe introducir una imagen");
            $respuesta["exito"] = false;
        }
        //chequeo tipo de img
        if(!isset($campos['tipo_imagen']) or !(($campos['tipo_imagen'] == 'jpeg') or ($campos['tipo_imagen'] == 'jpg') or ($campos['tipo_imagen'] == 'png'))){
            $respuesta["exito"] = false;
            array_push($respuesta["errores"], "El tipo de imagen debe estar seteada y ser de tipo jpeg, jpg o png");
            
        }

        //chequeo descripcion
        if(!isset($campos['descripcion']) or strlen($campos['descripcion']) > 255){
            $respuesta["exito"] = false;
            array_push($respuesta["errores"], "La descripcion debe estar seteada y no puede superar los 255 caracteres");
        }

        //chequeo url
        if(!isset($campos['url']) or strlen($campos['url']) > 80){
            $respuesta["exito"] = false;
            array_push($respuesta["errores"], "La url no puede superar los 80 caracteres");
        }

        //chequeo del genero
        //tiene que ser un genero que exista
        if (!isset($campos['id_genero']) or $campos['id_genero'] == ""){
            $respuesta["exito"] = false;
            array_push($respuesta["errores"], "Se debe introducir el id de un genero");
            
        } else {
            $id = $campos['id_genero'];
            $gen = $db->prepare("SELECT * FROM generos where id=?");
            $gen->bindParam(1, $id);
            $gen->execute();

            if($gen->rowCount() == 0){
                array_push($respuesta["errores"], "El id ingresado no pertenece a un genero existente en la bd");
                $respuesta["exito"] = false;
            }
        }

        //chequeo de la plataforma
        if(!isset($campos['id_plataforma']) or $campos['id_plataforma'] == ""){
            $respuesta["exito"] = false;
            array_push($respuesta["errores"], "Se debe introducir el id de una plataforma");
            
        } else {
            $id = $campos['id_plataforma'];
            $gen = $db->prepare("SELECT * FROM generos where id=?");
            $gen->bindParam(1, $id);
            $gen->execute();

            if($gen->rowCount() == 0){
                array_push($respuesta["errores"], "El id ingresado no pertenece a una plataforma existente en la bd");
                $respuesta["exito"] = false;
            }
        }

        if ($respuesta["exito"]){
            $cons = $db->prepare("INSERT INTO juegos (nombre, imagen, tipo_imagen, descripcion, url, id_genero, id_plataforma) VALUES (?,?,?,?,?,?,?)");
            $cons->bindParam(1, $campos['nombre']);
            $cons->bindParam(2, $campos['imagen']); //viene como texto en base 64
            $cons->bindParam(3, $campos['tipo_imagen']); //agrego el tipo de imagen, asumo que lo envia el usuario?
            $cons->bindParam(4, $campos['descripcion']);
            $cons->bindParam(5, $campos['url']);
            $cons->bindParam(6, $campos['id_genero']);//id
            $cons->bindParam(7, $campos['id_plataforma']);//id
            $cons->execute();

            $json = array('mensaje' => 'Se agrego un juego', 'exito' => true);
            $response->getBody()->write(json_encode($json));
            return $response->withStatus(200);
        } else {
            $json = array('mensaje' => 'No se pudo agregar el juego.', "errores" => $respuesta["errores"], 'exito' => false);
            $response->getBody()->write((json_encode($json)));
            return $response->withStatus(400);
        }
    } catch (\PDOException $e){
        $respuesta= array('mensaje' => $e->getMessage(), 'exito' => false);
        $response->getBody()->write(json_encode($respuesta));
        return $response->withStatus(500);
    }
});


//J) ACTUALIZAR UN JUEGO
//OK
//si se manda otra cosa q no se aun itn que pasa, o no importa, el id en la bd es de tipo int
//se manda con x-www-form-urlencoded
$app->put('/juegos/{id}', function(Request$request, Response $response){
    $db = new Db();
    $db = $db->connect();
    $campos = $request->getParsedBody();
    try{
        $id = $request->getAttribute('id'); //cuando el parametro viene en la url
        $cons = $db->prepare("SELECT * FROM juegos WHERE id=?"); 
        $cons->bindParam(1, $id);
        $cons->execute();

        if($cons->rowCount() != 0){
            $cons = $db->prepare("UPDATE juegos SET  nombre=? WHERE id=?");
            $cons->bindParam(1, $campos['nombre']);
            $cons->bindParam(2, $id);
            $cons->execute();

            $json = array('mensaje' => 'Se actualizo un juego', 'exito' => true);
            $response->getBody()->write(json_encode($json));
            return $response->withStatus(200); 
        } else {
            $json = array('mensaje' => 'No hay un juego con el id: ' . $id, 'exito' => false);
            $response->getBody()->write(json_encode($json));
            return $response->withStatus(400);
        }

    } catch (\PDOException $e){
        $response->getBody()->write($e->getMessage());
        return $response->withStatus(500); 
    }
});


//K) ELIMINAR UN JUEGO
//ok, sin param, id en url
//preg si el mje de que no hay juego esta bien
$app->delete('/juegos/{id}', function(Request $request, Response $response){
    $db = new Db();
    $db = $db->connect();
    
    try{
        $id = $request->getAttribute('id');
        $juegos = $db->prepare("SELECT * FROM juegos WHERE id=?");
        $juegos->bindParam(1, $id);
        $juegos->execute();
        if($juegos->rowCount() > 0){ //si el juego con el id enviado existe
            $cons = $db->prepare("DELETE FROM juegos WHERE id=?");
            $cons->bindParam(1, $id);
            $cons->execute();

            $json = array('mensaje' => 'Se elimino el juego con id: ' . $id, 'exito' => true);
            $response->getBody()->write(json_encode($json ));
            return $response->withStatus(200);
        } else {
            $json = array('mensaje' => 'No hay un juego con id: ' . $id, 'exito' => false);
            $response->getBody()->write(json_encode($json));
            return $response->withStatus(400);
        }
    } catch (\PDOException $err){
        $response->getBody()-write($err->getMessage());
        return $response->withStatus(500);
    }
});




//L) OBTENER TODOS LOS JUEGOS
//OK, sin parametros ni body
$app->get('/juegos', function (Request $request, Response $response){
    $db = new Db();
    $db = $db->connect();

    $respuesta = array();
    $respuesta["exito"] = true;
    $sql = "SELECT * FROM juegos";
    try{
        $resul = $db->query($sql);
        if($resul->rowCount() > 0){
            $juegos = $resul->fetchAll(\PDO::FETCH_OBJ);
            $json = array('mensaje' => 'Juegos disponibles', 'exito' => $respuesta["exito"], 'juegos' => $juegos); //PDO::FETCH_OBJ - Obtiene la siguiente fila y la devuelve como un objeto
            $response->getBody()->write(json_encode($json));
        } else {
            $json = array('mensaje' => 'No se encontraron juegos', 'exito' => false);
            $response->getBody()->write(json_encode($json ));
            return $response->withStatus(400);
        }
    } catch (\PDOException $e){
        $respuesta= array('mensaje' => $e->getMessage(), 'exito' => false);
        $response->getBody()->write(json_encode($respuesta));
        return $response->withStatus(500);
    }
});


//M) BUSCAR JUEGOS CON LOS FILTROS
$app->get('/juegos/buscar', function (Request $request, Response $response){
    $db = new Db();
    $db = $db->connect();

    $sql = "SELECT * FROM juegos"; //consulta principal, se crea la query dinamicamente

    try{    //para concatenar la consulta se puede usar un arreglo e ir agregandolos
        $parametros = $request->getQueryParams();//obtengo los parametros como un arreglo para chequeo de si existen o no
        $condicion = [];
        $setParam = [];

        if(isset($parametros['nombre'])){ //los nombres de los parametros en el arreglo TIENEN que coincidir con los de los nombres mandados por postman
            $nombre = $parametros['nombre'];
            $condicion[] = "nombre LIKE ?";
            $setParam[] = "%" . $nombre . "%";
        }

        if(isset($parametros['id_genero'])){
            $genero = $parametros['id_genero'];
            $condicion[] = "id_genero=?";
            $setParam[] = $genero;
        }

        if(isset($parametros['id_plataforma'])){
            $plataforma = $parametros['id_plataforma'];
            $condicion[] = "id_plataforma=?";
            $setParam[] = $plataforma;
        }

        if(isset($parametros['ordenar'])){
            if($parametros['ordenar'] == 'asc'){
                $orden = "ASC";
            } else {
                $orden = "DESC";
            }
        }

        if($condicion){
            $sql .= " WHERE ". implode(" AND ", $condicion); //implode une elementos de un array en un string, implode(string $separador, array $array) : string. el separador por defecto es un str vacio
        }

        if(isset($parametros['ordenar'])){
            $sql .= " ORDER BY nombre $orden";
        }

        $consulta = $db->prepare($sql);
        $consulta->execute($setParam);
        $juegos = $consulta->fetchAll(\PDO::FETCH_OBJ);
        //$datos = $consulta->fetchAll(); es lo mismo pero va pdo

        $json = array('mensaje' => 'Juegos que coinciden con la busqueda', 'exito' => true, 'juegos' => $juegos);
        $response->getBody()->write(json_encode($json));
        return $response->withStatus(200);

    } catch (\PDOException $e){
        $respuesta= array('mensaje' => $e->getMessage(), 'exito' => false);
        $response->getBody()->write(json_encode($respuesta));
        return $response->withStatus(500);
    }
});


$app->run();

//CONSULTA: COMO ES MEJOR PONERLE A LAS URL API ok
//CHEQUEOS QUE HAY QUE HACER ok
//uso el catch? o esta mal? si no dejar asi ok
//no se puede eliminar un genero q esta en uso ok
//agregar algo para malas url

//ver lo de los numeros de los errrores



//PDO, PHP DATA OBJECT, CAPA DE ABSTRACCION
//Es una especie de libreria para conectar a base de datos, hacer consultas, etc
//esta entre php y las base de datos, es un lenguaje orientado a objetos
//con pdo nos podemos conectar a distintas bases de datos a diferencia de mysqli y mysql o  mysql server
//pdo es una clase con funciones o metodos
//con pdo se suele usar el try / catch
//
?>

