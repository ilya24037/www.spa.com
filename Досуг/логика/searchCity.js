function capitalizeFirstLetter(string){
    return string && string[0].toUpperCase() + string.slice(1);
}
setInterval(function() {
    if($('#search-city-id').val() != "") {
        $('.town').attr("style", "display: none;");
        value = capitalizeFirstLetter($('#search-city-id').val());
        $('.town:contains("' + value + '")').each(function() {
                $(this).attr("style", "display: block;");
        });
    }
    else {
        $('.town').attr("style", "display: block;");
    }
}, 200);
setInterval(function() {
    if($('#search-rayon-id').val() != "") {
        $('div#sf_field_rayon .row span.custom-input.col-xs-12.col-sm-12').attr("style", "display: none;");
        value = capitalizeFirstLetter($('#search-rayon-id').val());
        $('div#sf_field_rayon .row span.custom-input.col-xs-12.col-sm-12:contains("' + value + '")').each(function() {
                $(this).attr("style", "display: block;");
        });
    }
    else {
        $('div#sf_field_rayon .row span.custom-input.col-xs-12.col-sm-12').attr("style", "display: block;");
    }
}, 200);
setInterval(function() {
    if($('#search-metro-id').val() != "") {
        $('div#sf_field_metro .row span.custom-input.col-xs-12.col-sm-4').attr("style", "display: none;");
        value = capitalizeFirstLetter($('#search-metro-id').val());
        $('div#sf_field_metro .row span.custom-input.col-xs-12.col-sm-4:contains("' + value + '")').each(function() {
                $(this).attr("style", "display: block;");
        });
    }
    else {
        $('div#sf_field_metro .row span.custom-input.col-xs-12.col-sm-4').attr("style", "display: block;");
    }
}, 200);
setInterval(function() {
    if($('#search-nationality-id').val() != "") {
        $('div#sf_field_nationality .row span.custom-input.col-xs-12.col-sm-4').attr("style", "display: none;");
        value = capitalizeFirstLetter($('#search-nationality-id').val());
        $('div#sf_field_nationality .row span.custom-input.col-xs-12.col-sm-4:contains("' + value + '")').each(function() {
                $(this).attr("style", "display: block;");
        });
    }
    else {
        $('div#sf_field_nationality .row span.custom-input.col-xs-12.col-sm-4').attr("style", "display: block;");
    }
}, 200);

function enter() {
    createCookie('content_accepted', 'true', 31);
    location.reload();
}