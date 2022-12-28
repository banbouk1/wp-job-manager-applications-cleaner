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
            $total_deleted = 0;
            $failed_delete = 0;

            $job_applications_dir = wp_upload_dir()['basedir'] . '/job_applications';
            if(is_dir($job_applications_dir)) {
                // exclude . and .. folders
                $entries = array_diff( scandir( $job_applications_dir ), array( '.', '..' ) );
                global $wpdb;
                global $wp_filesystem;

                foreach($entries as $entry) {
                    $entry_full_path = $job_applications_dir . '/' . $entry;
                    if(is_dir($entry_full_path)) {
                        $meta_exists_query =
                        "SELECT *
                         FROM $wpdb->postmeta
                         WHERE meta_key = '_secret_dir' AND meta_value = '$entry'";

                        $used = $wpdb->get_var($meta_exists_query);
                        if ( $used == NULL ) {
                            // folder does not exist in database => delete it
                            if ( $wp_filesystem->delete($entry_full_path, true) ) {
                                $total_deleted = $total_deleted + 1;
                            }
                            else {
                                $failed_delete = $failed_delete + 1;
                            }
                        }                        
                    }
                }
                return "Deleted folders: $total_deleted. Failed: $failed_delete.";
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
