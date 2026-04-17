$(document).ready(function() {
    $('#users-table').DataTable({
        scrollCollapse: true
    });
    $.fn.dataTable.tables( {visible: true, api: true} ).columns.adjust();
});

// popover
$("[data-toggle=popover]").popover({
    animation: true,
    html: true
})


function showNotification(type, text) {
    new Noty({
            theme: 'mint',
            type: type, /*alert, information, error, warning, notification, success*/
            text: text,
            timeout: 3000,
            layout: "topCenter",
          }).show();
  };


$(document).on('click', '.editdel', function (e) {
    e.preventDefault();
    var id_selected = $(this).attr("id"); // get id from element
    var aux = id_selected.split("_");
    var id = aux[0];
    var action = aux[1];
    if (action == "edit") {
        location.href='./editUserPage.php?iduser='+id;
    }
    if (action == "delete") {
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            dataType: "text",
            url: 'deleteUser.php',
            type: 'POST',
            data: {iduser: id, csrf_token: csrfToken},
            success: function(data){
                if (data.localeCompare("success") == 0) {
                    showNotification("success", "Users was deleted successfully");
                    showNotification("information", "Reloading page...");
                    setTimeout(function () {location.reload();}, 1000);         
                }
                if (data.localeCompare("error") == 0) {
                    showNotification("error", "ERROR: User was not deleted");
                }
            }
        },function(error){
            showNotification("error", "ERROR: an error occurred while processing your request");
        });
    }
});