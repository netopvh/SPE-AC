(function($) {
    "use strict";

    $('.form-ajax').submit(function (e) {

        //prevent submission default action
        e.preventDefault();

        // //bind elements
        let form = $(this);
        let formData = new FormData(this);

        let data = (form.attr("enctype") == "multipart/form-data") ? formData : form.serialize();

        let processData = ((form.attr("enctype") == "multipart/form-data") ? false : true);
        let contentType = ((form.attr("enctype") == "multipart/form-data") ? false : "application/x-www-form-urlencoded; charset=UTF-8");

        var btn = $(this).find('input[type="submit"], button[type="submit"]');

        btn.attr('disabled', 'true');

        function submit(swal_alert = false){
            //send data
            $.ajax({
                cache: false,
                url: form.attr('action'),
                type: 'POST',
                dataType: 'json',
                data: data,
                processData: processData,
                contentType: contentType,
                success: function (data) {
                    var message = data.message ? data.message : 'Ação realizada com sucesso.'
                    $.toast({
                        heading: 'Success',
                        text: message,
                        position: 'top-right',
                        showHideTransition: 'slide',
                        icon: 'success'
                    });
                    setTimeout(function () {
                        //redirect
                        if (form.attr('data-redirect') != null) {
                            window.location = form.attr('data-redirect');
                        }else{
                            if (form.attr('data-reload') != null) {
                                document.location.reload(true);
                            }
                        }
                    }, 1000);
                },
                error: function (data) {
                    var error = data.responseJSON ? data.responseJSON.errorMessage : 'Acontenceu algum erro. Entre em contato com o administrador do sistema.'
                    $.toast({
                        heading: 'Erro',
                        text: error,
                        position: 'top-right',
                        showHideTransition: 'slide',
                        icon: 'error'
                    });
                    btn.removeAttr('disabled');
                },
                complete: function (data) {
                    if(swal_alert){
                        swal.close();
                    }
                }
            });
        }

        if (form.attr('data-confirmation') != null) {
            swal({
                title: form.attr('data-confirmation'),
                text: 'Tem certeza que deseja fazer isso?',
                icon: 'warning',
                dangerMode: false,
                closeOnClickOutside: false,
                buttons: {
                    cancel: {
                        text: 'Cancelar',
                        value: null,
                        visible: true,
                        closeModal: true,
                    },
                    confirm: {
                        text: 'Ok',
                        value: true,
                        visible: true,
                        closeModal: false
                    }
                },
            }).then(function (result) {
                if (result) {
                    $('.swal-button--cancel').attr('disabled', 'disabled');
                    submit(true);
                }else{
                    btn.removeAttr('disabled');
                }
            });
        }else{
            submit();
        }
    });

    $(document).on('click','.delete_confirmation', function(){
        var btn = $(this);
        var url = btn.attr('data-url');
        var url_redirect = btn.attr('data-redirect');

        btn.attr('disabled', 'disabled');

        swal({
            title: 'Deletar',
            text: 'Tem certeza que deseja fazer isso?',
            icon: 'warning',
            dangerMode: true,
            closeOnClickOutside: false,
            buttons: {
                cancel: {
                    text: 'Cancelar',
                    value: null,
                    visible: true,
                    closeModal: true,
                },
                confirm: {
                    text: 'Ok',
                    value: true,
                    visible: true,
                    closeModal: false
                }
            },
        }).then(function (result) {
            if (result) {
                submit();
            }else{
                btn.removeAttr('disabled');
            }
        });

        function submit(){
            //send data
            $.ajax({
                cache: false,
                url,
                type: 'DELETE',
                success: function (data) {
                    if (url_redirect != null) {
                        window.location = url_redirect;
                    }else{
                        document.location.reload(true);
                    }
                },
                error: function (data) {
                    var error = data.responseJSON ? data.responseJSON.errorMessage : 'Acontenceu algum erro. Entre em contato com o administrador do sistema.'
                    $.toast({
                        heading: 'Error',
                        text: error,
                        position: 'top-right',
                        showHideTransition: 'slide',
                        icon: 'error'
                    });
                    btn.removeAttr('disabled');
                    swal.close();
                },
            });
        }
    });

})(jQuery);
