var wizzaroEventsNewElemTheme = jQuery('#wizzaro-events-new-elem-temp').html();

jQuery('.wizzaro-events-table').delegate('[data-action="wizzaro-events-add-remove-elem"]', 'click', function(e) {
    e.preventDefault();
    jQuery(this).parents('tr').remove();
});

jQuery('body').delegate('[data-action="wizzaro-events-add-new-elem"]', 'click', function(e) {
    e.preventDefault();
    jQuery('.wizzaro-events-table').append(wizzaroEventsNewElemTheme);
});