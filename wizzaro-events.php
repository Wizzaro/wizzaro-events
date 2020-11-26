<?php
   /*
   Plugin Name: Wizzaro Events
   Description: This is plugin realy MVP version of this plugin
   Version: 0.0.1
   Author: Przemysław Dziadek
   Author URI: http://www.wizzaro.com
   License: GPL-2.0+
   */

if ( !function_exists( 'add_action' ) ) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

//ADD ACTIONS
add_action( 'wp_ajax_wizzaro_events', 'wizzaro_events_ajax' );
add_action( 'wp_ajax_nopriv_wizzaro_events', 'wizzaro_events_ajax' );

add_action( 'wp_ajax_wizzaro_events_get', 'wizzaro_events_get_ajax' );
add_action( 'wp_ajax_nopriv_wizzaro_events_get', 'wizzaro_events_get_ajax' );

if ( is_admin() ) {
    add_action( 'admin_enqueue_scripts', 'wizzaro_events_register_admin_scripts' );
    add_action( 'wp_ajax_wizzaro_events_toggle', 'wizzaro_events_toggle_ajax' );
} else {
    add_action( 'wp_enqueue_scripts', 'wizzaro_events_register_scripts' );
}

function wizzaro_events_register_scripts() {
    wp_register_script('wizzaro_events_js', plugin_dir_url( __FILE__ ) . 'assets/events.js?v=1.0.0', [ 'jquery' ]);
}

function wizzaro_events_register_admin_scripts() {
    wp_register_script('wizzaro_events_js_admin_events', plugin_dir_url( __FILE__ ) . 'assets/admin-events.js?v=1.0.0', [ 'jquery' ]);
    wp_register_script('wizzaro_events_js_admin_edit_events', plugin_dir_url( __FILE__ ) . 'assets/admin-edit-events.js?v=1.0.0', [ 'jquery' ]);
}

//ADD SHORDCODE
add_shortcode( 'wizzaro_events_js', 'wizzaro_events_js_shordcode' );

function wizzaro_events_js_shordcode() {
    wp_enqueue_script('wizzaro_events_js');
    wp_localize_script( 'wizzaro_events_js', 'WizzaroEventsConfig', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));
}

//AJAX

function wizzaro_events_get_ajax() {
    echo json_encode(WizzaroEvents::getEvents());
    wp_die();
}

function wizzaro_events_ajax() {
    header("Cache-Control: no-cache, must-revalidate");
    set_time_limit(60);

    $maxExecutionTime = ini_get('max_execution_time');

    $events = WizzaroEvents::getEvents();
    $currentLoop = 0;

    while (true) {
        wp_cache_flush();

        $newEvents = WizzaroEvents::getEvents();

        if ( count(array_diff_assoc($events, $newEvents)) > 0 || count(array_diff_assoc($newEvents, $events)) > 0) { //need two check with both sides becouse we can add/edit and delete events
            echo json_encode($newEvents);
            break;
        } elseif ($currentLoop >= $maxExecutionTime) {
            break;
        }

        flush();
        ob_flush();
        echo ' ';

        if ( connection_aborted() || connection_status() != 0 ) { break; };

        $currentLoop++;

        sleep(1);
    }

    wp_die();
}

//ADD ADMIN PAGE

add_action( 'admin_menu', 'wizzaro_events_admin_page_register' );

function wizzaro_events_admin_page_register() {
    add_menu_page(
        'Zdarzenia',
        'Zdarzenia',
        'manage_options',
        'wizzaro-events',
        'wizzaro_events_admin_page_content',
        'dashicons-bell',
        99
    );

    add_submenu_page(
        'wizzaro-events',
        'Edytuj Zdarzenia',
        'Edytuj Zdarzenia',
        'manage_options',
        'wizzaro-events-edit',
        'wizzaro_events_edit_admin_page_content'
    );
}

function wizzaro_events_admin_page_style() {
    ?>
    <style>
        .wizzaro-events-table {
            table-layout: fixed;
            border-collapse: collapse;
        }

        .wizzaro-events-table td {
            padding: 7px;
        }

        .wizzaro-events-table tr:nth-child(2n) {
            background: #f1f1f1;
        }

        .wizzaro-events-table td:first-child {
            width: 100%;
        }

        .wizzaro-events-table input {
            width: 100%;
        }

        .wizzaro-events-loader {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #fff;
            background: rgba(255,255,255, 0.7);
            z-index: 99999;
        }

        .wizzaro-events-loader .spinner {
            position: absolute;
            top: 50%;
            left: 50%;
            margin: -10px 0 0 -10px;
            visibility: visible;
        }

        .wizzaro-events-msg {
            text-align: center;
        }
    </style>
    <?php
}

function wizzaro_events_admin_page_content() {
    wp_enqueue_script('wizzaro_events_js_admin_events');

    $events = WizzaroEvents::getEvents();
    wizzaro_events_admin_page_style();
    ?>
    <div class="wrap">
        <h1>Zdarzenia</h1>
        <p>
            Wysyłaj zdarzenia na żywo do osób obecnie znajdujących się na danych podstronach by wyświetlić im ukryte treści. Praca ze zdarzeniami jest bardzo prosta. Wystarczy kilka kroków do osiągnięcia oczekiwanego celu:
        </p>
        <ol>
            <li>Dodaj blok treści na stronie oraz nadaj mu unikalny <code>id</code> oraz ustaw <code>display</code> na <code>none</code></li>
            <li>Dodaj shorcode <code>[wizzaro_events_js]</code> na stronie by użycie zdarzeń było możliwe</li>
            <li>Dodaj zdarzenie na stronie "Edytuj Zdarzenia" podając id danego bloku który został dodany na danej stronie</li>
            <li>Przejdź do zakładku "Zdarzenia" i naciśnij "wyślij" by wszystkie osoby na danej stronie zobaczyły ukrytą treść ;)</li>
        </ol>
        <div class="postbox metabox-holder">
            <div class="postbox-header">
                <div class="inside">
                    <strong>Zdarzenia</strong>
                </div>
            </div>
            <div class="inside">
                <?php
                if ( count($events) > 0 ) {
                    ?>
                    <table class="wizzaro-events-table">
                        <?php
                            foreach ( $events as $event => $status ) {
                                ?>
                                <tr data-event="<?php echo $event; ?>">
                                    <td><strong>id: </strong> <?php echo $event; ?></td>
                                    <td>
                                        <button class="button<?php echo $status == true ? ' button-primary' : ''; ?>" data-action="wizzaro-events-toggle">
                                            <?php echo $status == true ? 'resetuj' : 'wyślij'; ?>
                                        </button>
                                    </td>
                                </tr>
                                <?php
                            }
                        ?>
                    </table>
                    <?php
                } else {
                    ?>
                    <p class="wizzaro-events-msg">Brak zdarzeń do wyświetlenia</p>
                    <?php
                }
                ?>
            </div>
        </div>
        <div class="wizzaro-events-loader">
            <span class="spinner"></span>
        </div>
    </div>
    <?php
}

function wizzaro_events_edit_admin_page_content() {
    wp_enqueue_script('wizzaro_events_js_admin_edit_events');

    $events = WizzaroEvents::getEvents();
    wizzaro_events_admin_page_style();

    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['wizzaro_events'] ) && is_array( $_POST['wizzaro_events'] ) && count( $_POST['wizzaro_events'] ) > 0 ) {
        $newEvents = [];

        foreach ( $_POST['wizzaro_events'] as $event ) {
            $eventId = filter_var( $event['id'], FILTER_SANITIZE_STRING );

            if ( mb_strlen( $eventId ) > 0 && !array_key_exists( $eventId, $newEvents ) ) {
                if ( array_key_exists( $eventId, $events ) ) {
                    $newEvents[$eventId] = $events[$eventId];
                } else {
                    $newEvents[$eventId] = false;
                }
            }
        }

        WizzaroEvents::saveEvents($newEvents);

        $events = $newEvents;

        ?>
        <div class="notice notice-success settings-error is-dismissible"> 
            <p><strong>Zdarzenia zostały zapisane.</strong></p>
        </div>
        <?php
    }
    ?>
    <div class="wrap">
        <h1>Edytuj Zdarzenia</h1>
        <br>
        <div class="postbox metabox-holder">
            <div class="inside">
                <form method="post">
                    <table class="wizzaro-events-table">
                        <?php
                        if ( count($events) > 0 ) {
                            foreach ( $events as $event => $status ) {
                                wizzaro_events_edit_admin_page_content_elem($event);
                            }
                        } else {
                            wizzaro_events_edit_admin_page_content_elem();
                        }
                        ?>
                    </table>
                    <br>
                    <button class="button" data-action="wizzaro-events-add-new-elem">Dodaj kolejny</button>
                    <input class="button button-primary" type="submit" value="Zapisz">
                </form>
            </div>
        </div>
    </div>
    <template id="wizzaro-events-new-elem-temp">
        <?php wizzaro_events_edit_admin_page_content_elem(); ?>
    </template>
    <?php
}

function wizzaro_events_edit_admin_page_content_elem($event = '') {
    ?>
    <tr>
        <td>
            <input type="text" name="wizzaro_events[][id]" value="<?php echo $event; ?>" placeholder="id">
        </td>
        <td>
            <button class="button" data-action="wizzaro-events-add-remove-elem">Usuń</button>
        </td>
    </tr>
    <?php
}

//ADMIN AJAX

function wizzaro_events_toggle_ajax() {
    $response = ['status' => false];

    if (isset($_POST['wizzaro_event'])) {
        $events = WizzaroEvents::getEvents();
        
        if ( array_key_exists( $_POST['wizzaro_event'], $events ) ) {
            $events[$_POST['wizzaro_event']] = !$events[$_POST['wizzaro_event']];
            WizzaroEvents::saveEvents($events);

            $response = ['status' => true, 'sended' => $events[$_POST['wizzaro_event']]];        
        }
    }
    
    echo json_encode($response);
    wp_die();
}

//GLOBAL

class WizzaroEvents
{
    const OPTION_KEY = 'wizzaro_events';

    public static function getEvents() {
        return get_option(WizzaroEvents::OPTION_KEY, []);
    }

    public static function saveEvents($events) {
        if( ! update_option( WizzaroEvents::OPTION_KEY, $events ) ) {
            add_option( WizzaroEvents::OPTION_KEY, $events );
        }
    }
}