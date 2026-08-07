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
{{-- Font Awesome ya no viene del kit externo: se autohospeda desde
     resources/css/app.css (@fortawesome/fontawesome-free). --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@livewireScripts
@include('partials.global-scripts')

</body>

</html>
