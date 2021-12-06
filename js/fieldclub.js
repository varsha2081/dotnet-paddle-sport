function confirmDelete()
{
	return confirm("Are you sure you wish to delete?");
}


var last_sender = null;
function showEditBooking(sender, startTime, endTime, courtId) {
    var popup_div = $("#editBooking");
    if (sender.is(last_sender)) {
        popup_div.hide();
        last_sender = null;
    }
    else {
        var url = "editBooking.php?startTime=" + startTime + "&endTime=" + endTime + "&courtId=" + courtId;
        
        $.get(url, function(data) { 
                popup_div.html(data);
                popup_div.show();
                popup_div.position({"my":"left top", "at":"left bottom", "of":sender});
            });
        last_sender = sender;
    }
    return false;
}

$(document).ready(function() {
        jQuery('<div/>', {id : "editBooking"}).appendTo(document.body);
        $('#editBooking').hide();
        });
