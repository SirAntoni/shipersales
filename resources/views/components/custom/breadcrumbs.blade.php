@php
    // Configuración centralizada de breadcrumbs
    $breadcrumbsMap = [
        'dashboard' => [
            ['title' => 'Dashboard','route' => 'dashboard']
        ],
        'articles.index' => [
            ['title' => 'Artículos','route' => 'articles.index'],
            ['title' => 'Listar', 'active' => true]
        ],
        'articles.create' => [
            ['title' => 'Artículos','route' => 'articles.index'],
            ['title' => 'Crear', 'active' => true]
        ],
        'articles.show' => [
            ['title' => 'Artículos','route' => 'articles.index'],
            ['title' => 'Editar', 'active' => true]
        ],
        'categories.index' => [
            ['title' => 'Categorías','route' => 'categories.index'],
            ['title' => 'Listar', 'active' => true]
        ],
        'categories.create' => [
            ['title' => 'Categorías','route' => 'categories.index'],
            ['title' => 'Crear', 'active' => true]
        ],
        'categories.show' => [
            ['title' => 'Categorías','route' => 'categories.index'],
            ['title' => 'Editar', 'active' => true]
        ],
        'purchases.index' => [
            ['title' => 'Compras','route' => 'purchases.index'],
            ['title' => 'Listar', 'active' => true]
        ],
        'purchases.create' => [
            ['title' => 'Compras','route' => 'purchases.index'],
            ['title' => 'Crear', 'active' => true]
        ],
        'purchases.show' => [
            ['title' => 'Compras','route' => 'purchases.index'],
            ['title' => 'Editar', 'active' => true]
        ],
        'providers.index' => [
            ['title' => 'Proveedores','route' => 'providers.index'],
            ['title' => 'Listar', 'active' => true]
        ],
        'providers.create' => [
            ['title' => 'Proveedores','route' => 'providers.index'],
            ['title' => 'Crear', 'active' => true]
        ],
        'providers.show' => [
            ['title' => 'Proveedores','route' => 'providers.index'],
            ['title' => 'Editar', 'active' => true]
        ],
        'canceled_purchases' => [
            ['title' => 'Compras','route' => 'purchases.index'],
            ['title' => 'Anuladas', 'active' => true]
        ],
        'sales.index' => [
            ['title' => 'Ventas','route' => 'sales.index'],
            ['title' => 'Listar', 'active' => true]
        ],
        'sales.create' => [
            ['title' => 'Ventas','route' => 'sales.index'],
            ['title' => 'Crear', 'active' => true]
        ],
        'sales.show' => [
            ['title' => 'Ventas','route' => 'sales.index'],
            ['title' => 'Editar', 'active' => true]
        ],
        'canceled' => [
            ['title' => 'Ventas','route' => 'sales.index'],
            ['title' => 'Anuladas', 'active' => true]
        ],
        'commissions' => [
            ['title' => 'Ventas','route' => 'sales.index'],
            ['title' => 'Comisiones', 'active' => true]
        ],
        'clients.index' => [
            ['title' => 'Clientes','route' => 'clients.index'],
            ['title' => 'Listar', 'active' => true]
        ],
        'clients.create' => [
            ['title' => 'Clientes','route' => 'clients.index'],
            ['title' => 'Crear', 'active' => true]
        ],
        'clients.show' => [
            ['title' => 'Clientes','route' => 'clients.index'],
            ['title' => 'Editar', 'active' => true]
        ],
        'users.index' => [
            ['title' => 'Usuarios','route' => 'users.index'],
            ['title' => 'Listar', 'active' => true]
        ],
        'users.create' => [
            ['title' => 'Usuarios','route' => 'users.index'],
            ['title' => 'Crear', 'active' => true]
        ],
        'users.show' => [
            ['title' => 'Usuarios','route' => 'users.index'],
            ['title' => 'Editar', 'active' => true]
        ],
        'settings' => [
            ['title' => 'General']
        ],
        'vouchers.index' => [
            ['title' => 'Comprobantes','route' => 'vouchers.index'],
            ['title' => 'Listar', 'active' => true]
        ],
        'vouchers.create' => [
            ['title' => 'Comprobantes','route' => 'vouchers.index'],
            ['title' => 'Crear', 'active' => true]
        ],
        'vouchers.show' => [
            ['title' => 'Comprobantes','route' => 'vouchers.index'],
            ['title' => 'Editar', 'active' => true]
        ],
        'contacts.index' => [
            ['title' => 'Contactos','route' => 'contacts.index'],
            ['title' => 'Listar', 'active' => true]
        ],
        'contacts.create' => [
            ['title' => 'Contactos','route' => 'contacts.index'],
            ['title' => 'Crear', 'active' => true]
        ],
        'contacts.show' => [
            ['title' => 'Contactos','route' => 'contacts.index'],
            ['title' => 'Editar', 'active' => true]
        ],
        'payment-methods.index' => [
            ['title' => 'Métodos de pago'],
            ['title' => 'Listar', 'active' => true]
        ],
        'payment-methods.create' => [
            ['title' => 'Métodos de pago'],
            ['title' => 'Crear', 'active' => true]
        ],
        'payment-methods.show' => [
            ['title' => 'Métodos de pago'],
            ['title' => 'Editar', 'active' => true]
        ],
        'documents.index' => [
            ['title' => 'Documentos'],
            ['title' => 'Listar', 'active' => true]
        ],
        'documents.show' => [
            ['title' => 'Documentos'],
            ['title' => 'Emitir', 'active' => true]
        ],
        'kardex' => [
            ['title' => 'Kardex']
        ],
        'reports' => [
            ['title' => 'Reportes']
        ]
    ];

    $currentRoute = Route::currentRouteName();

    $breadcrumbs = $breadcrumbsMap[$currentRoute] ?? [];
@endphp

@foreach($breadcrumbs as $index => $breadcrumb)
    <x-base.breadcrumb.link
        :index="$index + 1"
        :href="isset($breadcrumb['route']) ? route($breadcrumb['route']) : '#'"
        :active="$breadcrumb['active'] ?? false">
        {{ $breadcrumb['title'] }}
    </x-base.breadcrumb.link>
@endforeach
