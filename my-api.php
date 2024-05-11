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

        if ($contraseña === $contraseñaAlmacenada) {
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

    $sql = "INSERT INTO usuarios (nombre, apellido, username, edad, correo, sexo, contraseña) VALUES (?,?,?,?,?,?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssisss", $nombre, $apellido, $userName, $edad, $correo, $sexo, $contraseña);

    if($stmt->execute()) {
        echo json_encode(["message" => "usuario $nombre $apellido registrado exitosamente"]);
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

if ($_SERVER['REQUEST_METHOD'] === 'GET' && strpos($_SERVER['REQUEST_URI'], '/user/')!== false) {
    $username = substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], '/user/') + 6);

    $sql = "SELECT * FROM usuarios WHERE username =?";
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


$conn->close();
?>