<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Permission::create(['name' => 'dashboard','description' => 'Dashboard']);
        Permission::create(['name' => 'store','description' => 'Almacén']);
        Permission::create(['name' => 'articles','description' => 'Artículos']);
        Permission::create(['name' => 'articles.create','description' => 'Nuevo Articulo']);
        Permission::create(['name' => 'categories','description' => 'Categorías']);
        Permission::create(['name' => 'brands','description' => 'Marcas']);
        Permission::create(['name' => 'purchases','description' => 'Compras']);
        Permission::create(['name' => 'purchases.index','description' => 'Listar compras']);
        Permission::create(['name' => 'providers','description' => 'Proveedores']);
        Permission::create(['name' => 'canceled_purchases','description' => 'Compras anuladas']);
        Permission::create(['name' => 'sales','description' => 'Ventas']);
        Permission::create(['name' => 'sales.index','description' => 'Listar ventas']);
        Permission::create(['name' => 'clients','description' => 'Clientes']);
        Permission::create(['name' => 'canceled','description' => 'Listar ventas']);
        Permission::create(['name' => 'documents','description' => 'Ventas Anuladas']);
        Permission::create(['name' => 'reports','description' => 'Reportes']);
        Permission::create(['name' => 'users','description' => 'Usuarios']);
        Permission::create(['name' => 'settings','description' => 'Configuración']);
        Permission::create(['name' => 'kardex','description' => 'Kardex']);

        Permission::create(['name' => 'update','description' => 'Editar']);
        Permission::create(['name' => 'delete','description' => 'Eliminar']);
        Permission::create(['name' => 'commissions','description' => 'Comisiones']);
    }
}
