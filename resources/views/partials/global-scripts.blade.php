{{-- Global Livewire event listeners (SweetAlert2, Toastify, Livewire dispatch) --}}
<script>
    window.addEventListener('success', event => {
        Swal.fire({
            title: 'Realizado',
            text: event.detail[0]['label'],
            icon: 'success',
            confirmButtonText: event.detail[0]['btn'],
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then((result) => {
            if(result.isConfirmed){
                window.location.href = event.detail[0]['route'];
            }
        });
    });

    window.addEventListener('successNotRoute', event => {
        Swal.fire({
            title: 'Realizado',
            text: event.detail[0]['label'],
            icon: 'success',
            allowOutsideClick: false,
            allowEscapeKey: false
        });
    });

    window.addEventListener('success_sale', event => {
        Swal.fire({
            title: 'Realizado',
            text: event.detail[0]['label'],
            icon: 'success',
            allowOutsideClick: false,
            allowEscapeKey: false
        });
    });

    window.addEventListener('topPage', event => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    window.addEventListener('error', event => {
        Swal.fire({
            title: 'Error',
            text: event.detail[0]['label'],
            icon: 'error',
            confirmButtonText: "Cancelar",
            allowOutsideClick: false,
            allowEscapeKey: false
        })
    });

    window.addEventListener('errorNotRoute', event => {
        Swal.fire({
            title: 'Error',
            text: event.detail[0]['label'],
            icon: 'error',
            confirmButtonText: 'Aceptar',
            allowOutsideClick: false,
            allowEscapeKey: false
        });
    });

    window.addEventListener('notification', event => {
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
            allowOutsideClick: false,
            allowEscapeKey: false
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
            allowOutsideClick: false,
            allowEscapeKey: false
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

    window.addEventListener('questionDeleteUsa', event => {
        Swal.fire({
            title: 'Eliminar registro',
            text: event.detail[0]['label'],
            icon: 'warning',
            confirmButtonText: 'Sí, eliminar',
            confirmButtonColor: "#e74c3c",
            showCancelButton: true,
            cancelButtonText: 'Cancelar',
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then((result) => {
            if(result.isConfirmed){
                Livewire.dispatch('confirmDeleteUsa',{id:event.detail[0]['id']})
            }
        });
    });

    window.addEventListener('questionBulkStatusUsa', event => {
        Swal.fire({
            title: 'Cambio masivo de estado',
            text: event.detail[0]['label'],
            icon: 'question',
            confirmButtonText: 'Sí, aplicar',
            confirmButtonColor: "#3085d6",
            showCancelButton: true,
            cancelButtonText: 'Cancelar',
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then((result) => {
            if(result.isConfirmed){
                Livewire.dispatch('confirmBulkStatusUsa', {status: event.detail[0]['status']})
            }
        });
    });

    window.addEventListener('questionReturnWithDocument', event => {
        Swal.fire({
            title: 'Venta con comprobante electrónico',
            text: event.detail[0]['label'],
            icon: 'warning',
            confirmButtonText: 'Emitir nota de crédito',
            confirmButtonColor: "#3085d6",
            showDenyButton: true,
            denyButtonText: 'Anular sin nota de crédito',
            denyButtonColor: "#e74c3c",
            showCancelButton: true,
            cancelButtonText: 'Cancelar',
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then((result) => {
            if(result.isConfirmed){
                window.location.href = event.detail[0]['ncUrl'];
            } else if(result.isDenied){
                Livewire.dispatch('processReturn',{id:event.detail[0]['id']})
            }
        });
    });

    window.addEventListener('questionConfirmReturn', event => {
        Swal.fire({
            title: 'Confirmar devolución',
            text: event.detail[0]['label'],
            icon: 'warning',
            confirmButtonText: 'Sí, anular',
            confirmButtonColor: "#e74c3c",
            showCancelButton: true,
            cancelButtonText: 'Cancelar',
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then((result) => {
            if(result.isConfirmed){
                Livewire.dispatch('processReturn',{id:event.detail[0]['id']})
            }
        });
    });

    window.addEventListener('questionRevertReturn', event => {
        Swal.fire({
            title: 'Revertir devolución',
            text: event.detail[0]['label'],
            icon: 'question',
            confirmButtonText: 'Sí, revertir',
            confirmButtonColor: "#3085d6",
            showCancelButton: true,
            cancelButtonText: 'Cancelar',
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then((result) => {
            if(result.isConfirmed){
                Livewire.dispatch('processRevertReturn',{id:event.detail[0]['id']})
            }
        });
    });

    window.addEventListener('questionReturn', event => {
        Swal.fire({
            title: 'Devolución',
            text: event.detail[0]['label'],
            icon: 'warning',
            confirmButtonText: 'Sí, marcar',
            confirmButtonColor: "#e67e22",
            showCancelButton: true,
            cancelButtonText: 'Cancelar',
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then((result) => {
            if(result.isConfirmed){
                Livewire.dispatch('confirmReturn',{id:event.detail[0]['id']})
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
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then((result) => {
            if(result.isConfirmed){
                Livewire.dispatch('save')
            }
        });
    });

    window.addEventListener('document_delete', event => {
        Swal.fire({
            title: 'Anular comprobante',
            text: event.detail[0]['label'],
            icon: 'warning',
            input: 'text',
            inputLabel: 'Motivo de la anulación',
            inputValue: 'ERROR EN LA EMISIÓN',
            inputValidator: (value) => {
                if (!value || !value.trim()) {
                    return 'Debe indicar el motivo de la anulación.';
                }
            },
            confirmButtonText: "Anular comprobante",
            confirmButtonColor: "red",
            showCancelButton: true,
            cancelButtonText: 'Cancelar',
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then((result) => {
            if(result.isConfirmed){
                // El resultado (éxito o error) lo confirma el backend con
                // los eventos 'successNotRoute' / 'error' tras responder SUNAT.
                Livewire.dispatch('document_destroy', {document:event.detail[0]['id'], motive: result.value})
            }
        });
    });

    window.addEventListener('abrir-nueva-pestania', event => {
        window.open(event.detail[0]['url'], '_blank');
    });
</script>
