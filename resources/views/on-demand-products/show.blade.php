@extends('../themes/echo')

@section('subhead')
    <title>ShiperSales | Productos a pedido | Sistema de ventas</title>
@endsection

@section('subcontent')
    <livewire:on-demand-products.edit-on-demand-product :id="$id" />
@endsection
