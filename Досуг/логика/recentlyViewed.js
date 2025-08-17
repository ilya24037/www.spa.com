
/*FILE   : LIB.JS*/

var isLocalStorageAvailable = function () {
    try {
        return 'localStorage' in window && window['localStorage'] !== null;
    } catch (e) {
        return false;
    }
}

var rvAddListing = function(listing) {
    var rv_listings = rvGetListings();
    var new_rv_listings = [];

    if (rv_listings) {
        for (var i = 0; i < rv_listings.length; i++) {
            if ( rv_listings[i][0] != listing[0] ) {
                new_rv_listings.splice(i, 0, rv_listings[i]);
            }
        }
    }

    new_rv_listings.splice(0, 0, listing);

    if (new_rv_listings.length > rv_total_count) {
        var difference = (new_rv_listings.length - rv_total_count);
        new_rv_listings.splice(rv_total_count, difference);
    }

    try {
        localStorage.setItem('rv_listings_' + storage_item_name, JSON.stringify(new_rv_listings));
    } catch (e) {
        if (e == QUOTA_EXCEEDED_ERR) {
            console.log('Error. Web storage is full');
        }
    }
}

var rvRemoveListing = function(listing_id) {
    var rv_listings = rvGetListings();
    var new_rv_listings = [];

    if (rv_listings) {
        for (var i = 0; i < rv_listings.length; i++) {
            if (rv_listings[i][0] != listing_id) {
                new_rv_listings.splice(i, 0, rv_listings[i]);
            }
        }
    }

    try {
        localStorage.setItem('rv_listings_' + storage_item_name, JSON.stringify(new_rv_listings));
    } catch (e) {
        if (e == QUOTA_EXCEEDED_ERR) {
            console.log('Error. Web storage is full');
        }
    }
}

var rvGetListings = function() {
    return JSON.parse(localStorage.getItem('rv_listings_' + storage_item_name));
}

var rvRemoveListings = function() {
    localStorage.removeItem('rv_listings_' + storage_item_name);
}

var addTriggerToIcons = function() {
    $('#listings .fieldset').addClass('hide');

    var rv_storage_ids = [];
    var rv_storage     = rvGetListings();

    if (rv_storage) {
        for (var i = rv_storage.length - 1; i >= 0; i--) {
            rv_storage_ids.unshift(rv_storage[i][0]);
        }
    }

    rv_storage_ids = rv_storage_ids.join(',');

    $('.rv_remove span').each(function(){
        $(this).flModal({
            caption: notice,
            content: rv_del_listing_notice,
            prompt: 'ajaxRemoveRvListing(' + $(this).parent().attr('id').split('_')[1] + ')',
            width: 'auto',
            height: 'auto'
        });
    });

    $('.rv_del_listings').each(function(){
        $(this).flModal({
            caption: notice,
            content: rv_del_listings_notice,
            prompt: 'ajaxRemoveAllRvListings()',
            width: 'auto',
            height: 'auto'
        });
    });

    flFavoritesHandler();

    // @todo 1.2.3 - Remove this when "compatible" will be more than 4.6.2
    if (rlConfig['hisrc']) {
        dosugbarTpl.hisrc();
    }
}

var syncListings = function() {
    if (!isLogin) {
        return;
    }

    var rv_storage = rvGetListings();
    var rv_ids     = '';

    if (rv_storage) {
        for (var i = rv_storage.length - 1; i >= 0; i--) {
            rv_ids = rv_ids ? rv_storage[i][0] + ',' + rv_ids : rv_storage[i][0];
        }
    }

    $.post(rlConfig['ajax_url'], {mode: 'rvSyncListings', item: rv_ids, lang: rlLang},
        function(response){
            if (response && (response.status || response.message)) {
                if (response.status == 'OK' && response.data) {
                    localStorage.setItem('rv_listings_' + storage_item_name, response.data);

                    if (rlPageInfo.controller == 'rv_listings') {
                        document.location.href = rv_history_link;
                    } else {
                        loadRvListingsToBlock();
                    }
                } else if (response.message) {
                    setTimeout(function(){ printMessage('error', response.message); }, 500);
                }
            }
        },
        'json'
    );
}

var loadRvListingsToBlock = function() {
    var rv_listings = rvGetListings();

    if (rv_listings && rv_listings.length > 0) {
        /* Комментарий поменял class чтобы было width:100% */
        var content = '<div class="col-xs-12"';
        content += '><section id="rv_listings" class="side_block no-header';
        content += (template_name.substr(template_name.length - 5) == '_wide' ? ' wide' : '');
        content += (template_name.indexOf('_flatty') > 0 && template_name.indexOf('escort_') == 0 ? ' escort' : '');
        content += '"><div>';
        /* Комментарий поменял добавил ниже 2 строки */
/*        content += '<div class="item rv_first">' + rv_lang_listings + '</div>';*/
        content+='<div class="rvdisplayCounter">'+rv_lang_listings+'</div>';
        content+='<div class="controlsRight"><a href="'+rv_history_link+'" target="_self">'+rv_lang_history+'</a></div>';
        content += '<div class="rv_items">';

        var max_count = rv_listings.length <= 11 ? rv_listings.length : 11;

        for (var i = 0; i < max_count; i++) {
            var real_image_path = '';

            if (rv_listings[i][1]) {
                real_image_path = rlConfig['files_url'] + rv_listings[i][1];
            }

            var image_path = rlConfig['tpl_base'] + 'img/no-picture.png';

            /* build listing path */
            var listing_path = '';

            if (rv_listings[i].length == 6 && rv_listings[i][5] != '') {
                listing_path = rv_listings[i][5];
            } else {
                listing_path = rlConfig['seo_url'];
                listing_path += rlConfig['mod_rewrite'] ? rv_listings[i][2] + '/' + rv_listings[i][3] + '-' + rv_listings[i][0] + '.html' : '?page=' + rv_listings[i][2] + '&amp;id=' + rv_listings[i][0];
            }

            content += '<div class="item"><a href="' + listing_path + '" target="_blank"><img ';
            content += 'accesskey="' + real_image_path + '" ';
            content += 'class="hint ' + (template_name.indexOf('_flatty') > 0 && template_name.indexOf('escort_') == 0 ? 'escort' : '') + '"';
            content += 'src="' + rlConfig['tpl_base'] + 'img/blank.gif" style="background-image: url(\'' + image_path + '\')" ';
            content += 'title="' + rv_listings[i][4] + '" alt="' + rv_listings[i][4] + '">';
            content += '</a></div>';
        }

        content += '</div>';
       /*  Комментарий перенес выше */
/*        content += '<div class="rv_last"><a href="' + rv_history_link + '" target="_self">' + rv_lang_history + '</a></div>';*/
        content+='<div class="rvdisplayCounter tr"></div>'; /* Комментарий передел блок и css */
        content += '</div></section></div>';

        $('section#main_container > div.inside-container section#content').after(content);

         /* Комментарий убрал подсказку анкет просмотренный, задваивался черный квадрат */
        if (rlPageInfo['key'] == 'search' || rlPageInfo['key'] == 'my_notshows') {
        var tmp_style = jQuery.extend({}, qtip_style);
        tmp_style.tip = 'bottomMiddle';

        $('#rv_listings .hint').each(function(){
            $(this).qtip({
                content: $(this).attr('title') ? $(this).attr('title') : $(this).prev('div.qtip_cont').html(),
                show: 'mouseover',
                hide: 'mouseout',
                position: {
                    corner: {
                        target: 'topMiddle',
                        tooltip: 'bottomMiddle'
                    }
                },
                style: tmp_style
            }).attr('title', '');
        });
        }

        // check of existing listing photos
        $('.rv_items .item img').each(function(){
            var image_url = $(this).attr('accesskey');

            if (image_url) {
                $.get(image_url)
                    .done(function() {
                        $('.rv_items .item img').each(function(){
                            if ($(this).attr('accesskey') == image_url) {
                                $(this).attr('style', 'background-image: url(' + image_url + ')');
                                $(this).removeAttr('accesskey');
                            }
                        })
                    })
                    .fail(function(){
                        $('.rv_items .item img').each(function(){
                            if ($(this).attr('accesskey') == image_url) {
                                $(this).removeAttr('accesskey');
                            }
                        })
                    });
            }
        });
    }
}

/**
 * Remove all viewed listings from DB and storage
 */
var ajaxRemoveAllRvListings = function() {
    if (isLogin) {
        $.post(rlConfig['ajax_url'], {mode: 'rvRemoveAllListings', lang: rlLang},
            function(response){
                if (response && (response.status || response.message)) {
                    if (response.status == 'OK' && response.data) {
                        rvRemoveListings();
                        $('#controller_area').html('<div class="info">' + lang['rv_no_listings'] + '</div>');

                        setTimeout(function(){ printMessage('notice', response.data); }, 500);
                    } else if (response.message) {
                        setTimeout(function(){ printMessage('error', response.message); }, 500);
                    }
                }
            },
            'json'
        );
    } else {
        rvRemoveListings();
        $('#controller_area').html('<div class="info">' + lang['rv_no_listings'] + '</div>');
        setTimeout(function(){ printMessage('notice', lang['rv_del_listings_success']); }, 500);
    }
}

/**
 * Remove selected listing
 * 
 * @param {int} id
 */
var ajaxRemoveRvListing = function(id) {
    rvRemoveListing(id);

    if (isLogin) {
        $.post(rlConfig['ajax_url'], {mode: 'rvRemoveListing', item: id, lang: rlLang},
            function(response){
                if (response && (response.status || response.message)) {
                    if (response.status == 'OK' && response.data) {
                        setTimeout(function(){ printMessage('notice', response.data); }, 500);
                    } else if (response.message) {
                        setTimeout(function(){ printMessage('error', response.message); }, 500);
                    }
                }
            },
            'json'
        );
    } else {
        setTimeout(function(){ printMessage('notice', lang['rv_del_listing_success']); }, 500);
    }

    $('#rv_' + id + '.rv_remove').closest('article').remove();

    // redirect to first page
    if ((rvGetListings().length == 0 || $('#listings article').length == 0) && rv_history_link) {
        document.location.href = rv_history_link;
    }
}

/**
 * Load listings to page (for not logged users only)
 */
var ajaxLoadRvListings = function(){
    if (isLogin) {
        return;
    }

    var rv_storage = rvGetListings();
    var rv_ids     = '';

    if (rv_storage) {
        for (var i = rv_storage.length - 1; i >= 0; i--) {
            rv_ids = rv_ids ? rv_storage[i][0] + ',' + rv_ids : rv_storage[i][0];
        }
    }

    var $dataContainer = $('#controller_area');

    if (rv_ids) {
        $.post(
            rlConfig['ajax_url'], 
            {
                mode : 'rvLoadListings', 
                item : {
                    ids     : rv_ids, 
                    pg      : rv_pg,
                    storage : rv_storage
                }, 
                lang : rlLang
            },
            function(response){
                if (response && (response.status || response.message)) {
                    if (response.status == 'OK' && response.data) {
                        // update data of listings in storage
                        if (response.data.storage && response.data.listings) {
                            localStorage.setItem('rv_listings_' + storage_item_name, response.data.storage);
                        } else if (!response.data.storage && !response.data.listings) {
                            rvRemoveListings();
                        }

                        if (response.data.listings) {
                            $dataContainer.html(response.data.listings);
                            addTriggerToIcons();
                        } else {
                            $dataContainer.html('<div class="info">' + lang['rv_no_listings'] + '</div>');
                        }
                    } else if (response.message) {
                        setTimeout(function(){ printMessage('error', response.message); }, 500);
                    }
                }
            },
            'json'
        );
    } else {
        $dataContainer.html('<div class="info">' + lang['rv_no_listings'] + '</div>');
    }
}
