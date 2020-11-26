var wizzaroEvents = {};

function wizzaroEventsStart() {
    jQuery.ajax({
        type: "POST",
        url: WizzaroEventsConfig.ajaxurl,
        dataType: 'json',
        data: {
            action: 'wizzaro_events_get',
        },
        success: function(resp) {
            wizzaroEvents = resp;
            observeChanges();
        },
        error: function() {
           wizzaroEventsStart();
        }
    });
}

function observeChanges() {
    jQuery.ajax({
        type: "POST",
        url: WizzaroEventsConfig.ajaxurl,
        dataType: 'json',
        data: {
            action: 'wizzaro_events',
        },
        success: function(resp) {
            if (typeof resp === 'object') {
                for(var event in resp) {
                    if(resp.hasOwnProperty(event)) {
                        if( (!wizzaroEvents.hasOwnProperty(event) || wizzaroEvents[event] != resp[event]) && true == resp[event] ) {
                            var elemId = '#' + event;
                            if (typeof DiviArea == 'object') {
                                var myPopup = DiviArea.getArea(elemId);
                                if (myPopup) {
                                    DiviArea.show(elemId);
                                }
                            } else {
                                var $elem = jQuery(elemId);
                                if($elem.length > 0) {
                                    $elem.show();
                                }
                            }
                        }
                    }

                }

                wizzaroEvents = resp;
            }

            observeChanges();
        },
        error: function() {
            observeChanges();
        }
    });
}

jQuery(document).ready(function() {
    wizzaroEventsStart();
});