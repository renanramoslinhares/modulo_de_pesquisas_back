<?php
declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

// MySQL
$pdo = configConnection();

return function (App $app) use ($pdo) {
    $app->options('/{routes:.*}',
    function (Request $request, Response $response) { return $response;});

    // GRUPO RELATIVO A TAREFAS
    $app->group('/task', function (Group $group) use($pdo) {
        // [POST] CRIAR TAREFA
        $group->post('', function (Request $request, Response $response) use ($pdo) {
            $body = $request->getParsedBody();
            if(empty($body['text']) || empty($body['dimensionId'])) $arrayParam = ["isSuccess" => false, "message" => "Todos os campos são obrigatórios."];
            else {
                $string = "INSERT INTO Tasks (Text, DimensionID) VALUES (?, ?)";
                $sql = $pdo->prepare($string);        
                $sql->execute([$body['text'],$body['dimensionId']]);
                $id = $pdo->lastInsertId();
                $arrayParam = ["isSuccess" => true, "message" => "Tarefa salva com sucesso. ID: $id."];
            }
            $response->getBody()->write(json_encode($arrayParam));
            return $response;
        });

        // [GET] OBTER TODAS AS TAREFAS
        $group->get('s', function (Request $request, Response $response) use ($pdo) {
            $query = $pdo->query("SELECT Tasks.TaskID, Tasks.Text TaskText, Tasks.Status TaskStatus, Tasks.DimensionID, Dimensions.Name DimensionName FROM filterDB.Tasks LEFT JOIN filterDB.Dimensions ON Dimensions.DimensionID = Tasks.DimensionID WHERE Tasks.IsExcluded IS NULL")->fetchAll();
            $response->getBody()->write(json_encode($query));
            return $response;
        });

        // [GET] OBTER TAREFA ESPECÍFICA
        $group->get('/{id}', function (Request $request, Response $response, array $args) use ($pdo) {
            $query = $pdo->query("SELECT * FROM Tasks WHERE IsExcluded IS NULL AND TaskID = $args[id]")->fetch();
            $response->getBody()->write(json_encode($query));
            return $response;
        });

        // [POST] EDITAR TAREFA
        $group->post('/{id}', function (Request $request, Response $response, array $args) use ($pdo) {
            $body = $request->getParsedBody();
            if(empty($body['status']) && (empty($body['text']) || empty($body['dimensionId']))) {
                $arrayParam = ["isSuccess" => false, "message" => "Todos os campos são obrigatórios."];
            } else if(!empty($body['status'])) {
                $string = "UPDATE Tasks SET Status = ? WHERE IsExcluded IS NULL AND TaskID = ?";
                $sql = $pdo->prepare($string);        
                $sql->execute([$body['status'], $args['id']]);
                $arrayParam = ["isSuccess" => true, "message" => "Status alterado. ID: $args[id]."];
            } else {
                $string = "UPDATE Tasks SET Text = ?, DimensionID = ? WHERE IsExcluded IS NULL AND TaskID = ?";
                $sql = $pdo->prepare($string);        
                $sql->execute([$body['text'], $body['dimensionId'], $args['id']]);
                $arrayParam = ["isSuccess" => true, "message" => "Tarefa salva com sucesso. ID: $args[id]."];
            }
            $response->getBody()->write(json_encode($arrayParam));
            return $response;       
        });

        // [DELETE] EXCLUIR TAREFA (Soft delete)
        $group->delete('/{id}', function (Request $request, Response $response, array $args) use ($pdo) {
            $sql = $pdo->prepare("UPDATE Tasks SET IsExcluded = 1 WHERE TaskID = ?");        
            $isSuccess = $sql->execute([$args['id']]);
            $arrayParam = ["isSuccess" => $isSuccess, "message" => "Tarefa removida. ID: $args[id]."];
            $response->getBody()->write(json_encode($arrayParam));
            return $response;
        });
    });

    // GRUPO RELATIVO A DIMENSÕES
    $app->group('/dimension', function (Group $group) use($pdo) {
        // [POST] CRIAR DIMENSÃO
        $group->post('', function (Request $request, Response $response) use ($pdo) {
            $body = $request->getParsedBody();
            if(empty($body['name'])) $arrayParam = ["isSuccess" => false, "message" => "Nome é obrigatório."];
            else {
                $sql = $pdo->prepare("INSERT INTO Dimensions (Name) VALUES (?)");        
                $sql->execute([$body['name']]);
                $id = $pdo->lastInsertId();
                $arrayParam = ["isSuccess" => true, "message" => "Salvo com sucesso. ID: $id."];
            }
            $response->getBody()->write(json_encode($arrayParam));
            return $response;
        });

        // [GET] OBTER TODAS AS DIMENSÕES
        $group->get('s', function (Request $request, Response $response) use ($pdo) {
            $string = 'SELECT DimensionID, Name FROM Dimensions WHERE IsExcluded IS NULL ORDER BY Name ASC';
            $query = $pdo->query($string)->fetchAll();
            $response->getBody()->write(json_encode($query));
            return $response;
        });

        // [GET] OBTER DIMENSÃO ESPECÍFICA
        $group->get('/{id}', function (Request $request, Response $response, array $args) use ($pdo) {
            $string = "SELECT DimensionID, Name FROM Dimensions WHERE IsExcluded IS NULL AND DimensionID = $args[id]";
            $query = $pdo->query($string)->fetch();
            $response->getBody()->write(json_encode($query));
            return $response;
        });

        // [POST] EDITAR DIMENSÃO
        $group->post('/{id}', function (Request $request, Response $response, array $args) use ($pdo) {
            $body = $request->getParsedBody();
            if(empty($body['name'])) {
                $arrayParam = ["isSuccess" => false, "message" => "Nome é obrigatório."];
            }
            else {
                $string = "UPDATE Dimensions SET Name = ? WHERE IsExcluded IS NULL AND DimensionID = ?";
                $sql = $pdo->prepare($string);        
                $sql->execute([$body['name'], $args['id']]);
                $arrayParam = ["isSuccess" => true, "message" => "Editado com sucesso."];
            }
            $response->getBody()->write(json_encode($arrayParam));
            return $response;
        });

        // [DELETE] EXCLUIR DIMENSÃO (Soft delete)
        $group->delete('/{id}', function (Request $request, Response $response, array $args) use ($pdo) {
            $string = "UPDATE Dimensions SET IsExcluded = 1 WHERE DimensionID = :id
            AND (SELECT TaskID FROM Tasks WHERE DimensionID = :id) IS NULL";
            $sql = $pdo->prepare($string);        
            $sql->execute([':id' => $args['id']]);
            $isSuccess = $sql->rowCount();
            $response->getBody()->write(json_encode([
                "isSuccess" => $isSuccess,
                "message" => $isSuccess
                    ? "Dimensão removida com sucesso."
                    : "Erro: Dimensão vinculada à uma tarefa."
            ]));
            return $response;
        });
    });
};

function configConnection() {
	$dbhost="localhost";
	$dbuser="root";
	$dbpass="1234";
	$dbname="filterDB";
	$pdo = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);  
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $pdo;
}