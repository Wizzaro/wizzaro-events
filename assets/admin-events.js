var WizzaroEventLoader = {
    elem: jQuery('.wizzaro-events-loader'),
    show: function() {
        this.elem.show();
    },
    hide: function() {
        this.elem.hide();
    }
};

function WizzaroEvent($elem) {
    var toggleBtn = $elem.find('[data-action="wizzaro-events-toggle"]');
    var eventId = $elem.attr('data-event');
    var loader = WizzaroEventLoader;

    toggleBtn.on('click', function(e) {
        e.preventDefault();

        loader.show();
        toggleBtn.hide();

        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            dataType: 'json',
            data: {
                action: 'wizzaro_events_toggle',
                wizzaro_event: eventId
            },
            success: function(resp) {
                if(resp.status) {
                    if (resp.sended) {
                        toggleBtn.html('resetuj');
                        toggleBtn.addClass('button-primary');
                    } else {
                        toggleBtn.html('wyślij');
                        toggleBtn.removeClass('button-primary');
                    }
                } else {
                    alert('Wystąpił błąd podczas zmiany');    
                }
            },
            error: function() {
                alert('Wystąpił błąd podczas zmiany');
            },
            complete: function() {
                loader.hide();
                toggleBtn.show();
            }
        });
    });
}

jQuery(document).ready(function() {
    jQuery('.wizzaro-events-table tr').each(function() {
        new WizzaroEvent(jQuery(this));
    });
});