# Softmor Print Agent (.EXE sin PHP)

Este agente reemplaza `printer_server/server.php` y no requiere instalar PHP en la PC del cliente.

## Qué hace
- Escucha en `http://localhost:8000`
- Endpoint lista impresoras: `GET /api/printer/list`
- Endpoint imprime ticket: `POST /api/printer/raw`
- Compatible con el frontend actual de Gestional Food.

## Publicar EXE standalone (desde una máquina de build)

```powershell
dotnet publish .\print_agent_windows\SoftmorPrintAgent.csproj -c Release -r win-x64 --self-contained true -p:PublishSingleFile=true -p:IncludeNativeLibrariesForSelfExtract=true -o .\print_agent_windows\publish\win-x64
```

El ejecutable generado será:
- `print_agent_windows\publish\win-x64\SoftmorPrintAgent.exe`

## Crear instalador para cliente final (Setup.exe)

Requisitos para build:
- .NET SDK 8
- Inno Setup 6 ([descarga oficial](https://jrsoftware.org/isdl.php))

Compilar instalador:

```powershell
powershell -ExecutionPolicy Bypass -File .\print_agent_windows\installer\build-installer.ps1
```

Salida:
- `print_agent_windows\installer\SoftmorPrintAgentSetup.exe`

## Instalación en cliente

1. Ejecutar `SoftmorPrintAgentSetup.exe` como administrador.
2. Finalizar asistente.
3. El instalador:
- Copia `SoftmorPrintAgent.exe` en `C:\Program Files\Softmor\PrintAgent`
- Crea acceso directo en Inicio de Windows (autoarranque)
- Inicia el agente al terminar

## Prueba rápida

1. Salud del agente:
- `http://localhost:8000/`

2. Listar impresoras:
- `http://localhost:8000/api/printer/list`

3. En Gestional Food, configurar:
- `local_bridge_url = http://localhost:8000/api/printer/raw`

## Nota
La web en VPS (`https://gestionalfood.com`) sigue igual: el navegador llama a `localhost` para imprimir localmente.
