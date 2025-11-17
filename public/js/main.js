/* 
 * Developer: Marvin Villanea
 * All Rigth Reserved 2023 
 */
const URL_BASED = document.querySelector('.URL_HOST').getAttribute('data-url') + '/';

var main = {
    modalOpen: function (title,html,footer, action, size = '') {

        $('.modalOpenCustom').find('.modal-dialog').addClass(size);


        $('.modalOpenCustom').modal('show');
       // Set the form's action attribute
        $('.modalOpenCustom').find('form').attr('action', action);

        // Set the title
        $('.modalOpenCustom').find('.modal-title').text(title);
    
        // Set the HTML content
        $('.modalOpenCustom').find('.modal-body').html(html);

        $('.modalOpenCustom').find('.modal-footer').html(footer);

    
 

    },
    alertMessage: function (icon, title, text ) {
        swal({
            title: title,
            text: text,
            icon: icon,
        });
    },
    confirmMessage: function (icon,title,text, fn, data) {
        swal({
            title: title,
            text: text,
            icon: icon,
            buttons: true,
            dangerMode: false,
          })
        .then((willDelete) => {
            if (willDelete) {
                if (typeof window[fn] == 'function') {
                    window[fn](data);
                } 
            } 
        });
    },
    send_ajax: function (data, url, type, form = false) {
        if (form) {
            return $.ajax(
                    {
                        type: type,
                        url: url,
                        data: data,
                        dataType: 'json',
                        processData: false,
                        cache: false,
                        contentType: false
                    }
            );
        } else {
            return $.ajax(
                    {
                        type: type,
                        url: url,
                        data: data,
                        dataType: 'json',
                        processData: false
                    }
            );
    }
    },
    form_ajax : function (form_id) {
        var form = $(form_id);

        var data = new FormData($(form_id)[0]);
        var action = form.attr('action');
        var request = this.send_ajax(data, action, 'POST', true);


        request.done(function (data) {
            if (typeof data.error !== "undefined") {
                main.alertMessage('warning', 'System Error!', 'Please Contact The Administrator.');
                return false;
            }
            if (typeof window[data.function] == 'function') {
                window[data.function](data);
            } else {
                main.alertMessage(data.type, data.title, data.text);
            }


        });
        request.fail(function (xhr, ajaxOptions, thrownError) {
            main.alertMessage('warning', 'System Error!', 'Please Contact The Administrator.');
        });

      
    }
};



function reloadPage() {
    window.location.reload(true);
}

function removeNotificationSystem() {
    var $element = $(".boxings");

    // Check if the element exists
    if ($element.length > 0) {
        // Set a timeout to remove it after 3 seconds
        setTimeout(function () {
            $element.remove();
        }, 3000); // 3000ms = 3 seconds
    }
}


removeNotificationSystem();





$(document).on('click', '.close', function() {
    // Remove a row when the delete button is clicked
    $('.modalOpenCustom').modal('hide');

});
