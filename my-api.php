<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, PUT");
header("Access-Control-Allow-Headers: Content-Type");

$servername = "localhost";
$username = "root";
$password = "";
$db_name = "mysql-practica";

$conn = new mysqli($servername, $username, $password, $db_name);

if ($conn->connect_error) {
    die("connection failed: " . $conn->connect_error);
}


#----------- al hacer POST con action = signin -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['action'] === 'signin') {           
    $data = json_decode(file_get_contents('php://input'), true);

    $username = $data['username'];
    $contraseña = $data['contraseña'];

    $sql = "SELECT contraseña FROM usuarios WHERE username =?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $contraseñaAlmacenada = $result->fetch_assoc()['contraseña'];

        if (password_verify($contraseña, $contraseñaAlmacenada)) {
            echo json_encode(["message" => "ok"]);
        } else {
            echo json_encode(["message" => "el usuario ". $username." está registrado pero la contraseña es incorrecta"]);
        }
    } else {
        echo json_encode(["message" => "usuario no registrado"]);
    }
    $stmt->close();
} 

#----------- al hacer POST con action = register ---------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['action'] === 'register') {

    $data = json_decode(file_get_contents('php://input'), true);

    $nombre   = $data['nombre'];
    $apellido = $data['apellido'];
    $userName = $data['username'];
    $edad     = $data['edad'];
    $correo   = $data['correo'];
    $sexo     = $data['sexo'];
    $contraseña = $data['contraseña'];

    $passwordHash = password_hash($contraseña, PASSWORD_BCRYPT);

    $sql = "INSERT INTO usuarios (nombre, apellido, username, edad, correo, sexo, contraseña) VALUES (?,?,?,?,?,?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssisss", $nombre, $apellido, $userName, $edad, $correo, $sexo, $passwordHash);

    if($stmt->execute()) {
        echo json_encode(["message" => "usuario $userName registrado exitosamente"]);
    } else {
        $error = $stmt->error;
        if (strpos($error, 'Duplicate entry') !== false) {
            echo json_encode(["message" => "el correo de $nombre $apellido ya se encuentra en la base de datos"]);
        } else {
            echo json_encode(["message" => "Error al registrar usuario: " . $error]);
        }
    }
}

#------------ AL HACER DELETE ----------------------------

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), TRUE);

    $id = $data['id'];

    $sql = "DELETE FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0
        ) {
            echo json_encode(["message" => "usuario eliminado exitosamente"]);
        } else {
            echo json_encode(["message" => "no se encontro un usuario con el id especificado"]);
        }
    }
}

#--------------- AL HACER GET CON URL /user/{username}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && strpos($_SERVER['REQUEST_URI'], '/user/')!== false) {
    $username = substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], '/user/') + 6);

    $sql = "SELECT nombre, apellido, username FROM usuarios WHERE username =?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc(); 
        echo json_encode($user); 
    } else {
        echo json_encode(["message" => "usuario no encontrado"]);
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && strpos($_SERVER['REQUEST_URI'], '/todos/')!== false) {
    $username = substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], '/todos/') + 7);

    $sql = "SELECT contenido, listo FROM porhacer WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $todosList = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($todosList); 
    } else {
        echo json_encode(["message" => "no hay tareas"]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['action'] === 'addtodo') {
    $data = json_decode(file_get_contents('php://input'), true);
    $user = $_GET['user'];
    $TODO = $data['todo'];

    $sql = "INSERT INTO porhacer(username, contenido) VALUES(?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $user, $TODO);
    if($stmt->execute()){
        echo json_encode(["message" => "tarea guardada con exito"]);
    } else {
        echo json_encode(['message' => 'Error al guardar la tarea', 'error' => $stmt->error]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['action'] === 'deletetodo') { //de momento no quiere funcionar con el metodo DELETE, mientras tanto uso POST
    $username = $_GET['user'];
    $keyIndex = $_GET['key'];

    if ($keyIndex === 1) {
        $sql = "SELECT * FROM porhacer WHERE username = ? ORDER BY id LIMIT 1";
    } else {
        $sql = "SELECT * FROM porhacer WHERE username = ? ORDER BY id LIMIT 1 OFFSET ?";
    }
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $username, $keyIndex);
    $stmt->execute();

    $result = $stmt->get_result();

    $idResult = $result->fetch_assoc()['id'];

    $sql = "DELETE FROM porhacer WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idResult);
    if($stmt->execute()){
        echo json_encode(["message" => "tarea eliminada con exito"]);
    } else {
        echo json_encode(["message" => "error al eliminar la tarea"]);
    }
}

//------------------marcar tareas como finalizadas-----------
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && $_GET['action'] === "putlisto") {
    $index = $_GET['taskindex'];
    $user = $_GET['user'];

    if ($index === 1) {
        $sql = "SELECT id, listo FROM porhacer WHERE username =? ORDER BY id LIMIT 1";
    } else {
        $sql = "SELECT id, listo FROM porhacer WHERE username =? ORDER BY id LIMIT 1 OFFSET?";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $user, $index);
    $stmt->execute();

    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $idResult = $row['id'];
        $listo = $row['listo'];

        if ($listo === "falso") {
            $sql = "UPDATE porhacer SET listo = 'hecho' where id =?"; 
        } else {
            $sql = "UPDATE porhacer SET listo = 'falso' where id =?";
        }

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $idResult);
        if ($stmt->execute()) {
            echo json_encode(["message" => "fila alterada con exito"]);
        } else {
            echo json_encode(["message" => "error al alterar la fila"]);
        }
    } else {
        echo json_encode(["message" => "No se encontró ningún registro para el usuario especificado."]);
    }
}

$conn->close();
?>