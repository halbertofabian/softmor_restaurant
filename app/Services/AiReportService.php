<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AiReportService
{
    /**
     * Process a natural language question and generate a report
     */
    public function processQuestion(string $question, int $branchId): array
    {
        // Step 1: Get database schema context
        $schemaContext = $this->getDatabaseSchema();
        
        // Step 2: Ask AI to interpret the question and generate SQL
        $aiResponse = $this->generateSqlFromQuestion($question, $schemaContext);
        
        // Step 3: Validate and execute the SQL safely
        $results = $this->executeSafeSql($aiResponse['sql'], $branchId);
        
        // Step 4: Determine best visualization
        $chartType = $aiResponse['chart_type'] ?? 'table';
        
        return [
            'interpretation' => $aiResponse['interpretation'],
            'sql' => $aiResponse['sql'],
            'parameters' => $aiResponse['parameters'] ?? [],
            'results' => $results,
            'chart_type' => $chartType,
            'chart_config' => $this->generateChartConfig($results, $chartType),
        ];
    }

    /**
     * Get relevant database schema for AI context
     */
    private function getDatabaseSchema(): string
    {
        $tables = [
            'products' => ['id', 'name', 'price', 'category_id', 'branch_id', 'tenant_id'],
            'categories' => ['id', 'name', 'branch_id', 'tenant_id'],
            'orders' => ['id', 'total', 'status', 'created_at', 'branch_id', 'tenant_id'],
            'order_details' => ['id', 'order_id', 'product_id', 'quantity', 'price'],
            'payments' => ['id', 'order_id', 'amount', 'method', 'created_at', 'cash_register_id', 'branch_id', 'tenant_id'],
            'cash_registers' => ['id', 'opening_amount', 'closing_amount', 'status', 'opened_at', 'closed_at', 'branch_id', 'tenant_id'],
            'cash_movements' => ['id', 'cash_register_id', 'type', 'amount', 'description', 'created_at', 'tenant_id', 'branch_id'],
        ];

        $schema = "Database Schema:\n";
        foreach ($tables as $table => $columns) {
            $schema .= "Table: {$table}\n";
            $schema .= "Columns: " . implode(', ', $columns) . "\n\n";
        }

        return $schema;
    }

    /**
     * Get OpenAI Client with dynamic API Key
     */
    private function getClient()
    {
        // Try to get key from settings
        $apiKey = null;
        
        try {
            if (auth()->check()) {
                $setting = \App\Models\Setting::where('key', 'openai_api_key')
                    ->where('branch_id', session('branch_id'))
                    ->first();
                    
                if ($setting && !empty($setting->value)) {
                    $apiKey = $setting->value;
                }
            }
        } catch (\Exception $e) {
            // Fallback to configured key if DB error
        }

        if (empty($apiKey)) {
            $apiKey = config('openai.api_key');
        }

        if (empty($apiKey)) {
            throw new \Exception('No se ha configurado la API Key de OpenAI. Por favor ve a Configuración > Inteligencia Artificial y agrega tu clave.');
        }
        
        $apiKey = trim($apiKey);

        // Use direct client to avoid Laravel wrapper config checks
        return \OpenAI::client($apiKey);
    }

    /**
     * Use OpenAI to interpret question and generate SQL
     */
    private function generateSqlFromQuestion(string $question, string $schema): array
    {
        $tenantId = auth()->user()->tenant_id ?? 'default';
        
        $prompt = <<<PROMPT
Eres un asistente experto en SQL para un sistema de punto de venta de restaurante.

{$schema}

REGLAS DE SEGURIDAD CRÍTICAS (OBLIGATORIAS):
1. SOLO genera queries SELECT - NUNCA DELETE, UPDATE, INSERT, DROP, etc.
2. SIEMPRE incluye "WHERE branch_id = :branch_id" en TODAS las tablas que tengan branch_id
3. Si la tabla tiene tenant_id, incluye "WHERE tenant_id = :tenant_id" (el sistema lo agregará automáticamente si falta)
4. El usuario NO puede especificar tenant_id ni branch_id - estos se aplican automáticamente
5. Usa SOLO las tablas y columnas del schema proporcionado
6. Para fechas, usa funciones de MySQL como DATE(), CURDATE(), DATE_SUB(), etc.
7. NO uses INFORMATION_SCHEMA ni tablas del sistema
8. Cuando uses JOINs, asegúrate de usar alias de tabla y filtrar por branch_id en la tabla principal

IMPORTANTE: El sistema automáticamente filtra por:
- tenant_id = "{$tenantId}" (si la tabla lo tiene)
- branch_id = :branch_id (sucursal actual del usuario)

Retorna SOLO un objeto JSON válido con esta estructura:
{
  "interpretation": "Interpretación en español de lo que el usuario pregunta",
  "sql": "SELECT query SQL válido con filtros de seguridad",
  "parameters": {},
  "chart_type": "bar|line|pie|table",
  "reasoning": "Por qué elegiste este tipo de gráfico"
}

Pregunta del usuario: "{$question}"

Genera el SQL query apropiado con TODOS los filtros de seguridad.
PROMPT;

        try {
            $client = $this->getClient();

            $response = $client->chat()->create([
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'system', 'content' => 'Eres un experto en SQL y análisis de datos para restaurantes. SIEMPRE aplicas filtros de seguridad por tenant_id y branch_id.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.3,
            ]);

            $content = $response->choices[0]->message->content;
            
            // Extract JSON from response (handle markdown code blocks)
            $content = preg_replace('/```json\s*|\s*```/', '', $content);
            $content = trim($content);
            
            return json_decode($content, true) ?? [
                'interpretation' => 'No pude interpretar la pregunta (Error Parseo JSON)',
                'sql' => 'SELECT 1',
                'chart_type' => 'table',
            ];

        } catch (\Exception $e) {
            \Log::error('OpenAI Error: ' . $e->getMessage());
            
            return [
                'interpretation' => 'Error: ' . $e->getMessage(),
                'sql' => 'SELECT 1',
                'chart_type' => 'table',
                'error' => true 
            ];
        }
    }

    /**
     * Execute SQL safely with parameter binding and strict security checks
     */
    private function executeSafeSql(string $sql, int $branchId): array
    {
        try {
            // Security Check 1: Only SELECT queries allowed
            $sql = trim($sql);
            if (!preg_match('/^\s*SELECT\s+/i', $sql)) {
                throw new \Exception('Solo se permiten consultas SELECT por seguridad');
            }

            // Security Check 2: Block dangerous SQL keywords
            $dangerousKeywords = [
                'DELETE', 'DROP', 'TRUNCATE', 'ALTER', 'CREATE', 
                'INSERT', 'UPDATE', 'REPLACE', 'GRANT', 'REVOKE',
                'EXEC', 'EXECUTE', 'CALL', 'LOAD_FILE', 'OUTFILE',
                'DUMPFILE', 'INTO', 'INFORMATION_SCHEMA'
            ];
            
            foreach ($dangerousKeywords as $keyword) {
                if (preg_match('/\b' . $keyword . '\b/i', $sql)) {
                    throw new \Exception("Palabra clave no permitida detectada: {$keyword}");
                }
            }

            // Security Check 3: Ensure branch_id filter is present
            if (!stripos($sql, 'branch_id')) {
                throw new \Exception('La consulta debe filtrar por sucursal (branch_id)');
            }

            // Security Check 4: Add tenant_id filter if table has the column
            $tenantId = auth()->user()->tenant_id ?? 'default';
            
            // Split query by UNION to handle each part correctly
            $parts = preg_split('/(\bUNION\s+(?:ALL\s+)?\b)/i', $sql, -1, PREG_SPLIT_DELIM_CAPTURE);
            $processedParts = [];
            
            // Iterate through parts (parts will contain SQL segments and delimiters like "UNION ALL")
            foreach ($parts as $part) {
                // If this part is just a UNION keyword, keep it as is
                if (preg_match('/^\s*UNION\s+(?:ALL\s+)?\s*$/i', $part)) {
                    $processedParts[] = $part;
                    continue;
                }
                
                // Process the SQL segment
                $segment = $part;
                
                // Only add tenant_id if not already in query segment
                if (!stripos($segment, 'tenant_id')) {
                    // Extract main table name (handle aliases)
                    if (preg_match('/\bFROM\s+([`\'"]?\w+[`\'"]?)(?:\s+(?:AS\s+)?([`\'"]?\w+[`\'"]?))?/i', $segment, $matches)) {
                        $tableName = str_replace(['`', "'", '"'], '', $matches[1]);
                        $possibleAlias = isset($matches[2]) ? str_replace(['`', "'", '"'], '', $matches[2]) : null;
                        
                        // Check if alias is a reserved keyword
                        $reserved = ['WHERE', 'JOIN', 'LEFT', 'RIGHT', 'INNER', 'OUTER', 'CROSS', 'GROUP', 'ORDER', 'LIMIT', 'HAVING', 'OFFSET', 'ON'];
                        
                        $tableAlias = ($possibleAlias && !in_array(strtoupper($possibleAlias), $reserved)) 
                            ? $possibleAlias 
                            : $tableName;
                        
                        // Check if table has tenant_id column
                        if ($this->tableHasColumn($tableName, 'tenant_id')) {
                            $tableRef = $tableAlias;
                            
                            if (stripos($segment, 'WHERE')) {
                                // Add to existing WHERE clause (limit 1 to target the main WHERE of this segment)
                                $segment = preg_replace('/\bWHERE\b/i', "WHERE {$tableRef}.tenant_id = :tenant_id AND", $segment, 1);
                            } else {
                                // Add WHERE before ORDER BY, GROUP BY, LIMIT, etc.
                                if (preg_match('/\b(ORDER BY|GROUP BY|HAVING|LIMIT)\b/i', $segment)) {
                                    $segment = preg_replace('/\b(ORDER BY|GROUP BY|HAVING|LIMIT)\b/i', "WHERE {$tableRef}.tenant_id = :tenant_id $1", $segment, 1);
                                } else {
                                    // Add at the end
                                    $segment .= " WHERE {$tableRef}.tenant_id = :tenant_id";
                                }
                            }
                        }
                    }
                }
                $processedParts[] = $segment;
            }
            
            $sql = implode('', $processedParts);

            // Execute with bound parameters
            $params = [
                'branch_id' => $branchId,
            ];
            
            // Only add tenant_id param if it's in the query
            if (stripos($sql, ':tenant_id')) {
                $params['tenant_id'] = $tenantId;
            }

            $results = DB::select($sql, $params);
            
            return json_decode(json_encode($results), true);
            
        } catch (\Exception $e) {
            \Log::error('AI Report SQL Error: ' . $e->getMessage(), [
                'sql' => $sql,
                'user_id' => auth()->id(),
                'branch_id' => $branchId,
            ]);
            
            throw $e;
        }
    }

    /**
     * Check if a table has a specific column
     */
    private function tableHasColumn(string $table, string $column): bool
    {
        try {
            return Schema::hasColumn($table, $column);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Generate Chart.js configuration based on results
     */
    private function generateChartConfig(array $results, string $chartType): ?array
    {
        if (empty($results) || $chartType === 'table') {
            return null;
        }

        $firstRow = $results[0];
        $keys = array_keys($firstRow);
        
        // Assume first column is label, second is value
        $labelKey = $keys[0] ?? 'label';
        $valueKey = $keys[1] ?? 'value';

        $labels = array_column($results, $labelKey);
        $data = array_column($results, $valueKey);

        return [
            'type' => $chartType,
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => ucfirst($valueKey),
                    'data' => $data,
                    'backgroundColor' => $this->getChartColors(count($data)),
                ]]
            ],
            'options' => [
                'responsive' => true,
                'plugins' => [
                    'legend' => ['display' => $chartType === 'pie'],
                ]
            ]
        ];
    }

    /**
     * Get color palette for charts
     */
    private function getChartColors(int $count): array
    {
        $colors = [
            'rgba(54, 162, 235, 0.8)',
            'rgba(255, 99, 132, 0.8)',
            'rgba(255, 206, 86, 0.8)',
            'rgba(75, 192, 192, 0.8)',
            'rgba(153, 102, 255, 0.8)',
            'rgba(255, 159, 64, 0.8)',
        ];

        return array_slice(array_merge($colors, $colors, $colors), 0, $count);
    }
}
