<?php
/**
 * LogicaTest.php
 * -----------------------------------------------------------
 * Pruebas unitarias con PHPUnit para las funciones de logica.php.
 * Cada metodo que empieza con "test" es una prueba.
 *
 * Para correrlas (desde la carpeta del proyecto):
 *   vendor\bin\phpunit tests\LogicaTest.php
 * -----------------------------------------------------------
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../logica.php';

class LogicaTest extends TestCase
{
    // ---------- Pruebas de saldo ----------

    public function testRecargaSumaAlSaldo()
    {
        // Si tengo $100 y recargo $50, el saldo debe quedar en $150
        $this->assertEquals(150, calcularNuevoSaldo(100, 50));
    }

    public function testRecargaSobreSaldoCero()
    {
        // Si el saldo esta en 0 y recargo $30, debe quedar en $30
        $this->assertEquals(30, calcularNuevoSaldo(0, 30));
    }

    public function testRecargaConDecimales()
    {
        // Los montos con centavos tambien deben sumar bien
        $this->assertEquals(75.50, calcularNuevoSaldo(50.25, 25.25));
    }

    // ---------- Pruebas de monto ----------

    public function testMontoPositivoEsValido()
    {
        $this->assertTrue(montoEsValido(100));
    }

    public function testMontoCeroNoEsValido()
    {
        // El monto debe ser mayor a cero
        $this->assertFalse(montoEsValido(0));
    }

    public function testMontoNegativoNoEsValido()
    {
        $this->assertFalse(montoEsValido(-50));
    }

    // ---------- Pruebas de metodo de pago ----------

    public function testMetodoTarjetaEsValido()
    {
        $this->assertTrue(metodoPagoEsValido('tarjeta'));
    }

    public function testMetodoOxxoEsValido()
    {
        $this->assertTrue(metodoPagoEsValido('oxxo'));
    }

    public function testMetodoInventadoNoEsValido()
    {
        // Un metodo que no esta en la lista debe rechazarse
        $this->assertFalse(metodoPagoEsValido('paypal'));
    }

    // ---------- Pruebas de tarjeta ----------

    public function testNumeroTarjetaValido()
    {
        // 16 digitos es valido
        $this->assertTrue(numeroTarjetaEsValido('4111111111111111'));
    }

    public function testNumeroTarjetaConEspaciosValido()
    {
        // Aunque venga con espacios debe limpiarse y aceptarse
        $this->assertTrue(numeroTarjetaEsValido('4111 1111 1111 1111'));
    }

    public function testNumeroTarjetaCortoNoEsValido()
    {
        // Menos de 13 digitos no sirve
        $this->assertFalse(numeroTarjetaEsValido('12345'));
    }

    public function testVencimientoFormatoCorrecto()
    {
        $this->assertTrue(vencimientoEsValido('12/27'));
    }

    public function testVencimientoFormatoIncorrecto()
    {
        $this->assertFalse(vencimientoEsValido('2027-12'));
    }

    public function testNipDeTresDigitos()
    {
        $this->assertTrue(nipEsValido('123'));
    }

    public function testNipMuyLargoNoEsValido()
    {
        $this->assertFalse(nipEsValido('1234'));
    }

    public function testEnmascararTarjetaDejaUltimos4()
    {
        // Debe ocultar todo menos los ultimos 4 digitos
        $this->assertEquals('**** **** **** 1111', enmascararTarjeta('4111111111111111'));
    }

    // ---------- Pruebas de registro ----------

    public function testRegistroValidoNoDaError()
    {
        // Datos completos y correctos: no debe haber mensaje de error
        $error = validarRegistro('Juan', 'Perez', 'juan@correo.com', '5512345678', 'clave123', 'clave123');
        $this->assertEquals('', $error);
    }

    public function testRegistroCampoVacioDaError()
    {
        // Falta el nombre: debe regresar el error de campos obligatorios
        $error = validarRegistro('', 'Perez', 'juan@correo.com', '5512345678', 'clave123', 'clave123');
        $this->assertEquals('Todos los campos obligatorios deben estar completos.', $error);
    }

    public function testRegistroContrasenasNoCoinciden()
    {
        $error = validarRegistro('Juan', 'Perez', 'juan@correo.com', '5512345678', 'clave123', 'otraclave');
        $this->assertEquals('Las contrasenas no coinciden.', $error);
    }

    public function testRegistroContrasenaMuyCorta()
    {
        // Menos de 4 caracteres debe rechazarse
        $error = validarRegistro('Juan', 'Perez', 'juan@correo.com', '5512345678', 'abc', 'abc');
        $this->assertEquals('La contrasena debe tener al menos 4 caracteres.', $error);
    }
}
