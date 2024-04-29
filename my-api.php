<?php
$servername = "localhost";
$username = "root";
$password = "";
$db_name = "mysql-practica";

$conn = new mysqli($servername, $username, $password, $db_name);

if ($conn -> connect_error) {
    die("connection failed: " . $conn->connect_error);
}

#----------- al hacer GET ------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $sql = "SELECT id, nombre, apellido, edad, correo, sexo FROM usuarios";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $usuarios = array();
        
        while($row = $result->fetch_assoc()) {
            $usuarios[] = $row;
        }
        echo json_encode(["usuarios" => $usuarios]);
    } else {
        echo json_encode(["message" => "No se encontraron usuarios"]);
    }
}

#----------- al hacer POST ---------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = json_decode(file_get_contents('php://input'), true);

    $nombre   = $data['nombre'];
    $apellido = $data['apellido'];
    $edad     = $data['edad'];
    $correo   = $data['correo'];
    $sexo     = $data['sexo'];

    $sql = "INSERT INTO usuarios (nombre, apellido, edad, correo, sexo) VALUES ('$nombre', '$apellido', $edad, '$correo', '$sexo')";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(["message" => "Nuevo registro creado exitosamente"]);
    } else {
        echo json_encode(["error" => "Error: " . $sql . "<br>" . $conn->error]);
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
} else {
    echo json_encode(["error" => "Error al intentar eliminar al usuario: " . $stmt->error]);
}
$conn->close();
?>