<?php

/**
 * @package WPJobManagerApplicationsCleaner
 */

/*
Plugin Name: WP Job Manager - Applications Cleaner 

Plugin URI: https://www.midmac.net/ 

Description: Used to cleanup the WP Job Manager - Applications by removing <strong>duplicates applications</strong> from the database and remove <strong>unsed attachements</strong> from the file system.

Version: 1.0.0 

Author: Bassel Banbouk

Author URI: https://www.linkedin.com/in/banbouk/

License: GPLv2 or later 

Text Domain: wp-job-manager-applications-cleaner

*/

if ( ! defined( 'ABSPATH' ) ) {
    die;
}

if ( ! class_exists( 'JobApplicationCleanerPlugin' ) ) {
    class JobApplicationCleanerPlugin 
    {
        function initialize() {
            add_action( 'admin_menu', array( $this, 'add_admin_page' ) );
        }

        function add_admin_page() {
            add_submenu_page( 'edit.php?post_type=job_application', 'Job Applications Cleanup', 'DB & Files Cleanup', 'manage_options', 'job_application_cleaner_plugin', array( $this, 'admin_page' ) );
        }

        function admin_page() {
            require_once plugin_dir_path( __FILE__ ) . 'templates/admin.php';
        }

        function activate() {
            flush_rewrite_rules();
        }
        
        function deactivate() {
            flush_rewrite_rules();
        }

        function count_duplicates_in_db() {
            $count_posts_query =
                "SELECT COUNT(*)
                 FROM wp_posts
                 LEFT JOIN (
                     SELECT MAX(e.post_id) ID
                     FROM wp_postmeta AS e
                     INNER JOIN wp_postmeta AS j ON e.post_id = j.post_id
                     WHERE e.meta_key = 'Email Address' AND j.meta_key = '_job_applied_for'
                     GROUP BY j.meta_value, e.meta_value
                 ) keep ON wp_posts.ID = keep.ID
                 WHERE keep.ID IS NULL AND wp_posts.post_type = 'job_application'";

            global $wpdb;
            
            $duplicates = number_format ( $wpdb->get_var( $count_posts_query ) );
            
            return "$duplicates duplicate job applications were found.";
        }

        function remove_duplicates_from_db() {
            $delete_posts_query =
                "DELETE wp_posts.*
                 FROM wp_posts
                 LEFT JOIN (
                     SELECT MAX(e.post_id) ID
                     FROM wp_postmeta AS e
                     INNER JOIN wp_postmeta AS j ON e.post_id = j.post_id
                     WHERE e.meta_key = 'Email Address' AND j.meta_key = '_job_applied_for'
                     GROUP BY j.meta_value, e.meta_value
                 ) keep ON wp_posts.ID = keep.ID
                 WHERE keep.ID IS NULL AND wp_posts.post_type = 'job_application'";

            $delete_postmeta_query =
                "DELETE wp_postmeta.*
                 FROM wp_postmeta
                 LEFT JOIN wp_posts ON wp_postmeta.post_id = wp_posts.ID
                 WHERE wp_posts.ID IS NULL;";

            global $wpdb;

            $deleted_applications = number_format ( $wpdb->query( $delete_posts_query ) );
            $deleted_postmeta = number_format ( $wpdb->query( $delete_postmeta_query ) );

            return "$deleted_applications duplicate job applications, and $deleted_postmeta meta data were deleted from the database.";
        }

        function remove_unused_folders() {
            $processed = 0;
            $delete_success = 0;
            $delete_skipped = 0;
            $delete_failed = 0;
            $db_entries = array();

            $job_applications_dir = wp_upload_dir()['basedir'] . '/job_applications';
            if( is_dir( $job_applications_dir ) ) {
                // exclude . and .. folders
                $entries = array_diff( scandir( $job_applications_dir ), array( '.', '..' ) );
                global $wpdb;
                global $wp_filesystem;

                $metas = $wpdb->get_results(
                                "SELECT meta_value
                                FROM $wpdb->postmeta
                                WHERE meta_key = '_secret_dir'");

                foreach( $metas as $meta ) {
                    array_push( $db_entries, $meta->meta_value );
                }

                foreach( $entries as $entry ) {
                    $entry_full_path = $job_applications_dir . '/' . $entry;
                    // process directories only
                    if( is_dir( $entry_full_path ) ) {
                        $processed = $processed + 1;

                        if ( in_array( $entry, $db_entries ) ) {
                            $delete_skipped = $delete_skipped + 1;
                        }
                        else {
                            // folder does not exist in database => delete it
                            if ( $wp_filesystem->delete($entry_full_path, true) ) {
                                $delete_success = $delete_success + 1;
                            }
                            else {
                                $delete_failed = $delete_failed + 1;
                            }
                        }                        
                    }
                }
                return "Processed: $processed. Deleted: $delete_success. Skipped: $delete_skipped. Failed: $delete_failed.";
            }
            return "$job_applications_dir does not exist";
        }
    }

    $jobApplicationCleanerPlugin = new JobApplicationCleanerPlugin();
    $jobApplicationCleanerPlugin->initialize();

    // activation
    register_activation_hook( __FILE__, array( $jobApplicationCleanerPlugin, 'activate' ) );

    // deactivation
    register_deactivation_hook( __FILE__, array( $jobApplicationCleanerPlugin, 'deactivate' ) );
}
