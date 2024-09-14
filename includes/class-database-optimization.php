<?php
namespace AAC;

defined( 'ABSPATH' ) || exit;

class Database_Optimization {

    public function init() {
        add_action( 'aac_daily_database_optimization', [ $this, 'optimize_database' ] );

        if ( ! wp_next_scheduled( 'aac_daily_database_optimization' ) ) {
            wp_schedule_event( time(), 'daily', 'aac_daily_database_optimization' );
        }
    }

    public function optimize_database() {
        global $wpdb;
        $tables = $wpdb->get_col( 'SHOW TABLES' );
        foreach ( $tables as $table ) {
            $wpdb->query( "OPTIMIZE TABLE $table" );
        }
    }
}