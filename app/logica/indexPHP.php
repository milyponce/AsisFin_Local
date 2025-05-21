<?php
include_once __DIR__ . "/tokenJWT.php";

switch ($_POST["accion"]) {
    case "cerrarSeion":
        // funcion para cerrar sesion, esta dentro de token JWT
        echo cerrarSesion();
        break;

    case "comprobarSesion":
         // funcion para validar el token y comprobar que haya inciado sesion, esta dentro de token JWT
        echo validarToken();
        break;
}
