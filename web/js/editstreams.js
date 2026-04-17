$(document).ready(function() {
    $('#streams-table').DataTable({
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
        location.href='./editStreamPage.php?idstream='+id;
    }
    if (action == "delete") {
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            dataType: "text",
            url: 'deleteStream.php',
            type: 'POST',
            data: {idstream: id, csrf_token: csrfToken},
            success: function(data){
                if (data.localeCompare("success") == 0) {
                    showNotification("success", "Stream was deleted successfully");
                    showNotification("information", "Reloading page...");
                    setTimeout(function () {location.reload();}, 2000);         
                }
                if (data.localeCompare("error") == 0) {
                    showNotification("error", "ERROR: Stream was not deleted");
                }
            }
        },function(error){
            showNotification("error", "ERROR: an error occurred while processing your request");
        });
    }
});