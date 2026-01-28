<?php
// Simple standalone PHP Print Server
// Usage: php -S localhost:8000 server.php

// CORS Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

// Router Logic for PHP Built-in Server
$uri = $_SERVER['REQUEST_URI'];
// If the requested file exists physically, return false to let PHP serve it.
if (file_exists(__DIR__ . $uri) && $uri !== '/') {
    return false; 
}
// Otherwise, continue execution of this script (router)

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require 'vendor/autoload.php';

use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;

// Check if request is for listing printers
$uri = $_SERVER['REQUEST_URI'] ?? '/';
if ($uri === '/api/printer/list' || $uri === '/printer/list') {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        exit;
    }

    try {
        // Run PowerShell command to list printers
        // Adding -ErrorAction SilentlyContinue to avoid stderr noise
        // Using UTF8 output encoding to handle special chars properly
        $command = 'powershell -Command "$OutputEncoding = [Console]::OutputEncoding = [System.Text.Encoding]::UTF8; Get-Printer | Select-Object -ExpandProperty Name"';
        $output = [];
        $return_var = 0;
        exec($command, $output, $return_var);
        
        // Filter out empty lines and trim whitespace
        $printers = [];
        foreach ($output as $line) {
            $trimmed = trim($line);
            if (!empty($trimmed)) {
                $printers[] = $trimmed;
            }
        }
        
        // If empty, try a fallback command (wmic) just in case powershell is restricted
        if (empty($printers)) {
             exec('wmic printer get name', $outputWmic);
             // Skip header line "Name"
             foreach ($outputWmic as $line) {
                $trimmed = trim($line);
                if (!empty($trimmed) && strtolower($trimmed) !== 'name') {
                    $printers[] = $trimmed;
                }
             }
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'success', 'printers' => $printers]);
        exit;

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// Check if request is for the printer endpoint
if ($uri !== '/api/printer/raw' && $uri !== '/printer/raw') {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Endpoint not found']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

try {
    // PRINTER CONFIGURATION
    // 1. Try to get printer name from the JSON request (sent from the cloud setting)
    // 2. Or fallback to local config/default
    $printerName = $data['printer_name'] ?? "POS-80";
    
    // Attempt to connect
    $connector = new WindowsPrintConnector($printerName);
    $printer = new Printer($connector);

    // --- PRINTING LOGIC ---
    
    // Check if this is a KITCHEN ticket (simpler format)
    if (isset($data['type']) && $data['type'] === 'kitchen') {
        // ========== KITCHEN TICKET FORMAT ==========
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setEmphasis(true);
        $printer->text("**************\n");
        $printer->text(" MESA " . strtoupper($data['table_name'] ?? '?') . "\n");
        $printer->text("**************\n");
        $printer->setEmphasis(false);
        $printer->text("\n");
        
        // Items
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                $qty = $item['quantity'] ?? 1;
                $name = $item['name'] ?? 'Producto';
                $notes = $item['notes'] ?? '';
                
                // Item line
                $printer->text("$qty x $name\n");
                
                // Notes (if any)
                if (!empty($notes)) {
                    $printer->text("   * $notes\n");
                }
                $printer->text("\n"); // Space between items
            }
        }
        
        // Footer with time and waiter
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("--- " . date('H:i') . " ---\n");
        $printer->text("Mesero: " . ($data['waiter_name'] ?? 'N/A') . "\n");
        $printer->text("\n\n");
        
    } else {
        // ========== STANDARD SALES TICKET FORMAT ==========
        // Header
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text(($data['header'] ?? "Ticket de Venta") . "\n");
        $printer->text("Sucursal: " . ($data['branch_name'] ?? 'Principal') . "\n");
        $printer->text(($data['date'] ?? date('d/m/Y H:i A')) . "\n");
        $printer->text("Ticket #: " . ($data['ticket_id'] ?? 'N/A') . "\n");
        $printer->text("--------------------------------\n");

        // Items
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                $name = $item['name'] ?? 'Producto';
                if(strlen($name) > 20) $name = substr($name, 0, 20);
                
                $qty = $item['quantity'] ?? 1;
                $price = $item['price'] ?? 0;
                
                $printer->text("$qty x $name\n");
                $printer->setJustification(Printer::JUSTIFY_RIGHT);
                $printer->text("$" . number_format($price * $qty, 2) . "\n");
                $printer->setJustification(Printer::JUSTIFY_LEFT);
            }
        }
        $printer->text("--------------------------------\n");
        
        // Total
        $printer->setJustification(Printer::JUSTIFY_RIGHT);
        $printer->setEmphasis(true);
        $printer->text("TOTAL: $" . number_format($data['total'] ?? 0, 2) . "\n");
        $printer->setEmphasis(false);
        $printer->text("\n\n");
    }

    // Cut
    $printer->cut();
    $printer->close();

    echo json_encode(['status' => 'success', 'message' => 'Printed successfully']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
