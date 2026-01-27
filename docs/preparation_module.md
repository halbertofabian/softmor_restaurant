# Módulo de Áreas de Preparación

Este módulo permite administrar las áreas operativas del restaurante (Cocina, Barra, Parrilla, etc.) y enrutaron los productos de las comandas para su preparación.

## Estructura de Base de Datos

### Tabla: `preparation_areas`
| Campo | Tipo | Descripción |
| :--- | :--- | :--- |
| `id` | BigInt (PK) | Identificador único |
| `name` | String | Nombre del área (ej. "Cocina Caliente") |
| `description` | Text | Descripción opcional |
| `status` | Boolean | Activo (1) / Inactivo (0) |
| `sort_order` | Integer | Orden de prioridad para visualización |
| `print_ticket` | Boolean | Define si esta área debe imprimir ticket (1/0) |
| `created_at` | Timestamp | Fecha de creación |
| `updated_at` | Timestamp | Fecha de actualización |

## Relaciones

### Producto y Área
- **Relación:** Un producto pertenece a **una sola** área de preparación.
- **Clave Foránea:** `product.preparation_area_id` -> `preparation_areas.id`

### Comanda y Área
- Al agregar un producto a la comanda (`order_details`), se guarda una **instantánea** (snapshot) del `preparation_area_id` del producto en ese momento. Esto asegura que si el producto cambia de área en el futuro, las comandas históricas no se vean afectadas.

## Lógica de Agrupación
El modelo `Order` cuenta con un atributo dinámico `grouped_details` que agrupa los items de la comanda por el nombre del área de preparación asignada.

```php
// Ejemplo de uso
$itemsPorArea = $order->grouped_details;

foreach ($itemsPorArea as $areaNombre => $items) {
    echo "Área: " . $areaNombre;
    foreach ($items as $item) {
        echo $item->product_name . " x " . $item->quantity;
    }
}
```

## Flujo Operativo
1.  **Alta de Áreas:** El administrador crea áreas (ej. Cocina, Barra) desde el menú "Áreas de Preparación".
2.  **Asignación:** Al crear/editar un producto, se selecciona su área correspondiente.
3.  **Venta:** Al tomar la comanda, cada ítem guarda su área.
4.  **Impresión/Despacho:** (Implementación Futura) El sistema agrupará los ítems por `preparation_area_id` y decidirá si imprimir ticket basándose en el campo `print_ticket` del área.
