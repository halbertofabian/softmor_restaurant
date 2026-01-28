=== Softmor Printer Server ===

Este es un pequeño servidor para imprimir tickets desde la nube a una impresora local.

INSTALACIÓN:
1. Instala PHP en la computadora del cliente (o usa el que ya viene con XAMPP).
2. Abre esta carpeta en una terminal.
3. Ejecuta: composer install

USO:
1. Ejecuta el servidor:
   php -S localhost:8000 server.php

2. Asegúrate de que la impresora esté compartida en Windows con el nombre "POS-80" (o edita server.php para cambiar el nombre).

LISTO! Tu sistema en la nube ahora puede imprimir en esta computadora.
