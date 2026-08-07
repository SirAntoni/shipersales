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

    window.addEventListener('toast', event => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: event.detail[0]['label'],
            showConfirmButton: false,
            timer: 2500,
            timerProgressBar: true,
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

    window.addEventListener('questionSaveToCatalog', event => {
        Swal.fire({
            title: 'Guardar en catálogo',
            text: event.detail[0]['label'],
            icon: 'question',
            confirmButtonText: 'Sí, guardar',
            confirmButtonColor: "#3085d6",
            showCancelButton: true,
            cancelButtonText: 'Cancelar',
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then((result) => {
            if(result.isConfirmed){
                Livewire.dispatch('processSaveToCatalog',{detailId:event.detail[0]['id']})
            }
        });
    });

    window.addEventListener('questionDeleteWithDocument', event => {
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
                Livewire.dispatch('openDeleteMotiveModal',{id:event.detail[0]['id']})
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
        const doc = event.detail[0];

        Swal.fire({
            title: 'Anular comprobante',
            text: doc['label'],
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
            if(!result.isConfirmed){
                return;
            }

            const motive = result.value;

            // Solo boletas cuya baja repondría stock: preguntar si reponerlo.
            // El resultado (éxito o error) lo confirma el backend con los
            // eventos 'successNotRoute' / 'error' tras responder SUNAT.
            if (doc['preguntarReposicion']) {
                Swal.fire({
                    title: 'Reposición de stock',
                    text: '¿Desea reponer el stock de los productos de la boleta al inventario?',
                    icon: 'question',
                    confirmButtonText: 'Sí, reponer stock',
                    confirmButtonColor: "#3085d6",
                    showDenyButton: true,
                    denyButtonText: 'No reponer',
                    denyButtonColor: "#e74c3c",
                    showCancelButton: true,
                    cancelButtonText: 'Cancelar',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then((r2) => {
                    if(r2.isConfirmed){
                        Livewire.dispatch('document_destroy', {document: doc['id'], motive: motive, reponerStock: true})
                    } else if(r2.isDenied){
                        Livewire.dispatch('document_destroy', {document: doc['id'], motive: motive, reponerStock: false})
                    }
                });
            } else {
                Livewire.dispatch('document_destroy', {document: doc['id'], motive: motive, reponerStock: true})
            }
        });
    });

    window.addEventListener('questionRestockCreditNote', event => {
        Swal.fire({
            title: 'Reposición de stock',
            text: event.detail[0]['label'],
            icon: 'question',
            confirmButtonText: 'Sí, reponer stock',
            confirmButtonColor: "#3085d6",
            showDenyButton: true,
            denyButtonText: 'No reponer',
            denyButtonColor: "#e74c3c",
            showCancelButton: true,
            cancelButtonText: 'Cancelar',
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then((result) => {
            if(result.isConfirmed){
                Livewire.dispatch('emitCreditNote', {reponerStock: true})
            } else if(result.isDenied){
                Livewire.dispatch('emitCreditNote', {reponerStock: false})
            }
        });
    });

    window.addEventListener('document_delete_pending', event => {
        const doc = event.detail[0];

        Swal.fire({
            title: 'Eliminar comprobante pendiente',
            html: `El comprobante <b>${doc['number']}</b> se eliminará definitivamente.<br>La venta asociada no se modifica: podrá emitir un comprobante nuevo.`,
            icon: 'warning',
            confirmButtonText: 'Eliminar',
            confirmButtonColor: 'red',
            showCancelButton: true,
            cancelButtonText: 'Cancelar',
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then((result) => {
            if (result.isConfirmed) {
                Livewire.dispatch('document_destroy_pending', {document: doc['id']})
            }
        });
    });

    window.addEventListener('document_edit_date', event => {
        const doc = event.detail[0];

        Swal.fire({
            title: 'Cambiar fecha de emisión',
            text: `El comprobante ${doc['number']} se reenviará a SUNAT con la nueva fecha de emisión.`,
            icon: 'warning',
            input: 'date',
            inputLabel: 'Nueva fecha de emisión',
            inputValue: doc['date'],
            inputAttributes: {
                min: doc['min'],
                max: doc['max'],
            },
            inputValidator: (value) => {
                if (!value) {
                    return 'Debe indicar la fecha de emisión.';
                }
                if (value < doc['min'] || value > doc['max']) {
                    return `La fecha debe estar entre ${doc['min']} y ${doc['max']}.`;
                }
            },
            confirmButtonText: 'Guardar y reenviar',
            showCancelButton: true,
            cancelButtonText: 'Cancelar',
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then((result) => {
            if (result.isConfirmed) {
                // El resultado (aceptado/rechazado/pendiente) lo confirma el
                // backend con 'successNotRoute' / 'error' tras responder SUNAT.
                Livewire.dispatch('document_change_date', {document: doc['id'], date: result.value})
            }
        });
    });

    window.addEventListener('abrir-nueva-pestania', event => {
        window.open(event.detail[0]['url'], '_blank');
    });

    window.addEventListener('questionLabel', event => {
        Swal.fire({
            html: `
                <style>
                    .swl-popup { border-radius: 18px !important; padding: 26px 26px 24px !important; }
                    .swl-hero {
                        width: 56px; height: 56px; margin: 0 auto 14px; border-radius: 16px;
                        background: linear-gradient(135deg, #3d079d 0%, #7c3aed 100%);
                        display: flex; align-items: center; justify-content: center;
                        box-shadow: 0 10px 22px rgba(61, 7, 157, .35);
                    }
                    .swl-hero i { color: #fff; font-size: 23px; }
                    .swl-title { font-size: 18px; font-weight: 700; color: #0f172a; letter-spacing: -.2px; }
                    .swl-sub { font-size: 12.5px; color: #94a3b8; margin-top: 5px; line-height: 1.45; }
                    .swl-field { position: relative; margin-top: 15px; text-align: left; }
                    .swl-field label {
                        display: block; font-size: 12.5px; font-weight: 600; color: #475569;
                        margin: 0 2px 6px; text-transform: uppercase; letter-spacing: .4px; font-size: 11px;
                    }
                    .swl-field .swl-ic {
                        position: absolute; left: 13px; bottom: 13px;
                        color: #a5a1b8; font-size: 14px; pointer-events: none; transition: color .15s;
                    }
                    .swl-field input {
                        width: 100%; box-sizing: border-box;
                        border: 1.5px solid #e2e8f0; border-radius: 11px;
                        padding: 11px 13px 11px 38px;
                        font-size: 13.5px; color: #1e293b; background: #f8fafc;
                        outline: none; transition: border-color .15s, box-shadow .15s, background .15s;
                    }
                    .swl-field input::placeholder { color: #b6bcc9; }
                    .swl-field input:focus {
                        border-color: #3d079d; background: #fff;
                        box-shadow: 0 0 0 3.5px rgba(61, 7, 157, .12);
                    }
                    .swl-field input:focus ~ .swl-ic, .swl-field:focus-within .swl-ic { color: #3d079d; }
                    .swl-actions { display: flex !important; width: 100%; gap: 10px; margin-top: 20px !important; padding: 0 !important; }
                    .swl-btn-cancel {
                        flex: 1; background: #fff; color: #475569;
                        border: 1.5px solid #e2e8f0; border-radius: 11px;
                        padding: 12px 14px; font-weight: 600; font-size: 13.5px; cursor: pointer;
                        transition: background .12s;
                    }
                    .swl-btn-cancel:hover { background: #f1f5f9; }
                    .swl-btn-confirm {
                        flex: 1.4; background: linear-gradient(135deg, #3d079d 0%, #5b21b6 100%); color: #fff;
                        border: 0; border-radius: 11px;
                        padding: 12px 14px; font-weight: 600; font-size: 13.5px; cursor: pointer;
                        box-shadow: 0 8px 16px rgba(61, 7, 157, .3);
                        transition: transform .12s, box-shadow .12s;
                    }
                    .swl-btn-confirm:hover { transform: translateY(-1px); box-shadow: 0 10px 20px rgba(61, 7, 157, .38); }
                    .swl-btn-confirm:active { transform: translateY(0); }
                </style>
                <div class="swl-hero"><i class="fa-solid fa-tag"></i></div>
                <div class="swl-title">Generar etiqueta de despacho</div>
                <div class="swl-sub">Estos datos se imprimirán en la etiqueta de la caja.<br>Ambos campos son opcionales.</div>

                <div class="swl-field">
                    <label for="swal-label-reference">Referencia</label>
                    <input id="swal-label-reference" placeholder="Ej. Edificio Gran Parque, dpto 709" autocomplete="off">
                    <i class="fa-solid fa-location-dot swl-ic"></i>
                </div>
                <div class="swl-field">
                    <label for="swal-label-comment">Comentario</label>
                    <input id="swal-label-comment" placeholder="Ej. Frágil, entregar en recepción" autocomplete="off">
                    <i class="fa-solid fa-comment-dots swl-ic"></i>
                </div>
            `,
            width: '26.5rem',
            focusConfirm: false,
            buttonsStyling: false,
            reverseButtons: true,
            showCancelButton: true,
            confirmButtonText: '<i class="fa-solid fa-print" style="margin-right:7px"></i>Generar etiqueta',
            cancelButtonText: 'Cancelar',
            customClass: {
                popup: 'swl-popup',
                actions: 'swl-actions',
                confirmButton: 'swl-btn-confirm',
                cancelButton: 'swl-btn-cancel',
            },
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                const ref = document.getElementById('swal-label-reference');
                ref.value = event.detail[0]['reference'] || '';
                ref.focus();
            },
            preConfirm: () => ({
                reference: document.getElementById('swal-label-reference').value.trim(),
                comment: document.getElementById('swal-label-comment').value.trim(),
            })
        }).then((result) => {
            if (result.isConfirmed) {
                const params = new URLSearchParams();
                if (result.value.reference) params.set('reference', result.value.reference);
                if (result.value.comment) params.set('comment', result.value.comment);
                const qs = params.toString();
                window.open(event.detail[0]['url'] + (qs ? '?' + qs : ''), '_blank');
            }
        });
    });
</script>
