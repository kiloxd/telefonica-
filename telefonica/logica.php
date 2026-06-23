<?php
/**
 * logica.php
 * -----------------------------------------------------------
 * Funciones puras con las MISMAS reglas de validacion que usa
 * el sistema (recargas, tarjetas, registro). Estan separadas
 * del HTML y de la base de datos para poder probarlas con PHPUnit.
 *
 * Cada funcion recibe datos y devuelve un resultado, sin tocar
 * $_POST, $_SESSION ni mysqli. Asi se pueden probar solas.
 * -----------------------------------------------------------
 */

/**
 * Calcula el nuevo saldo despues de una recarga.
 * Misma regla que: UPDATE telefono SET saldo = saldo + monto
 */
function calcularNuevoSaldo($saldoActual, $monto)
{
    return $saldoActual + $monto;
}

/**
 * Valida que el monto de una recarga sea correcto.
 * Misma regla que gestion_saldo.php: el monto debe ser mayor a cero.
 * Devuelve true si es valido, false si no.
 */
function montoEsValido($monto)
{
    return is_numeric($monto) && $monto > 0;
}

/**
 * Valida que el metodo de pago sea uno de los permitidos.
 * Misma lista que gestion_saldo.php.
 */
function metodoPagoEsValido($metodo)
{
    $permitidos = array('tarjeta', 'transferencia', 'deposito', 'oxxo');
    return in_array($metodo, $permitidos);
}

/**
 * Valida el numero de una tarjeta (solo digitos, de 13 a 19).
 * Misma regla que el preg_match de gestion_saldo.php.
 */
function numeroTarjetaEsValido($numero)
{
    $numeroLimpio = preg_replace('/\s+/', '', $numero);
    return (bool) preg_match('/^\d{13,19}$/', $numeroLimpio);
}

/**
 * Valida el formato de la fecha de vencimiento (MM/AA).
 */
function vencimientoEsValido($vencimiento)
{
    return (bool) preg_match('/^\d{2}\/\d{2}$/', $vencimiento);
}

/**
 * Valida el NIP de seguridad (exactamente 3 digitos).
 */
function nipEsValido($nip)
{
    return (bool) preg_match('/^\d{3}$/', $nip);
}

/**
 * Enmascara el numero de tarjeta dejando solo los ultimos 4 digitos.
 * Misma logica que gestion_saldo.php.
 */
function enmascararTarjeta($numero)
{
    $numeroLimpio = preg_replace('/\s+/', '', $numero);
    $ultimos4 = substr($numeroLimpio, -4);
    return "**** **** **** " . $ultimos4;
}

/**
 * Valida los datos basicos del registro de usuario.
 * Mismas reglas que registro.php. Devuelve "" si todo esta bien,
 * o el mensaje de error correspondiente.
 */
function validarRegistro($nombre, $apellidos, $correo, $numero, $pass, $pass2)
{
    if (empty($nombre) || empty($apellidos) || empty($correo) || empty($numero) || empty($pass)) {
        return "Todos los campos obligatorios deben estar completos.";
    } elseif ($pass != $pass2) {
        return "Las contrasenas no coinciden.";
    } elseif (strlen($pass) < 4) {
        return "La contrasena debe tener al menos 4 caracteres.";
    }
    return "";
}
