<!DOCTYPE html>
<!--
Template Name: Sistema de ventas | ShiperSales
Author: Antony Culqui
Website: https://www.inventrashop.com
Contact: a.culqui02@gmail.com
License: Uso comercial solo para ShiperSales
-->
<html
    class="opacity-0"
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
>
<!-- BEGIN: Head -->

<head>
    <meta charset="utf-8">
    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >
    <meta
        name="description"
        content="ShiperSales | Sistema de ventas "
    >
    <meta
        name="keywords"
        content="Sistema de ventas | ShiperSales"
    >
    <meta
        name="author"
        content="ANTONY CULQUI"
    >

    @yield('head')

    <!-- BEGIN: CSS Assets-->
    @stack('styles')
    <link rel="shortcut icon" href="{{asset('images/logo-icon.png')}}" />
    <!-- END: CSS Assets-->

    @vite('resources/css/app.css')
    @livewireStyles

    <style>
        .mark-green { color: #28a745; font-weight: bold; }
        .mark-red   { color: #dc3545; font-weight: bold; }
    </style>

</head>
<!-- END: Head -->



<body>

<x-theme-switcher/>

@yield('content')

<!-- BEGIN: Vendor JS Assets-->
@vite('resources/js/vendors/dom.js')
@vite('resources/js/vendors/tailwind-merge.js')
@stack('vendors')
<!-- END: Vendor JS Assets-->

<!-- BEGIN: Pages, layouts, components JS Assets-->
@vite('resources/js/components/base/theme-color.js')
@stack('scripts')

<!-- END: Pages, layouts, components JS Assets-->
<script src="https://kit.fontawesome.com/808596313d.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@livewireScripts
<script>
    window.addEventListener('success', event => {
        Swal.fire({
            title: 'Realizado',
            text: event.detail[0]['label'],
            icon: 'success',
            confirmButtonText: event.detail[0]['btn']
        }).then((result) => {
            if(result.isConfirmed){
                window.location.href = event.detail[0]['route'];
            }
        });
    });

    window.addEventListener('success_sale', event => {
        Swal.fire({
            title: 'Realizado',
            text: event.detail[0]['label'],
            icon: 'success'
        });
    });

    window.addEventListener('error', event => {
        Swal.fire({
            title: 'Error',
            text: event.detail[0]['label'],
            icon: 'error',
            confirmButtonText: "Cancelar",
        })
    });

    window.addEventListener('notification',event => {
        Toastify({
            node: $("#success-notification-content")
                .clone()
                .removeClass("hidden")[0],
            duration: 3000,
            newWindow: true,
            close: true,
            gravity: "bottom",
            position: "right",
            stopOnFocus: true,
        }).showToast();
    });

    window.addEventListener('delete', event => {
        Swal.fire({
            title: 'Alerta',
            text: event.detail[0]['label'],
            icon: 'warning',
            confirmButtonText: event.detail[0]['btn'],
            confirmButtonColor: "red",
            showCancelButton: true,
        }).then((result) => {
            if(result.isConfirmed){
                Swal.fire({
                    title: "Eliminado!",
                    text: "El registro se ha eliminado con exito!.",
                    icon: "success"
                });

                Livewire.dispatch('destroy',{id:event.detail[0]['id']})
            }
        });
    });

    window.addEventListener('question', event => {
        Swal.fire({
            title: 'Alerta',
            text: event.detail[0]['label'],
            icon: 'warning',
            confirmButtonText: event.detail[0]['btn'],
            confirmButtonColor: "red",
            showCancelButton: true,
        }).then((result) => {
            if(result.isConfirmed){
                Swal.fire({
                    title: "Editado",
                    text: "El estado se ha editado con éxito!.",
                    icon: "success"
                });
                Livewire.dispatch('changeStatus',{id:event.detail[0]['id']})
            }
        });
    });

    window.addEventListener('questionNumber', event => {
        Swal.fire({
            title: 'Alerta',
            text: event.detail[0]['label'],
            icon: 'warning',
            confirmButtonText: event.detail[0]['btn'],
            confirmButtonColor: "red",
            showCancelButton: true,
        }).then((result) => {
            if(result.isConfirmed){
                Livewire.dispatch('save')
            }
        });
    });

    window.addEventListener('document_delete', event => {
        Swal.fire({
            title: 'Alerta',
            text: event.detail[0]['label'],
            icon: 'warning',
            confirmButtonText: "Anular comprobante",
            confirmButtonColor: "red",
            showCancelButton: true,
        }).then((result) => {
            if(result.isConfirmed){
                Swal.fire({
                    title: "Documento Anulado!",
                    text: "El documento fue anulado con éxito!.",
                    icon: "success"
                });
                Livewire.dispatch('document_destroy', {document:event.detail[0]['id']})
            }
        });
    });

    window.addEventListener('abrir-nueva-pestania', event => {
        window.open(event.detail[0]['url'], '_blank');
    });

</script>

</body>

</html>
