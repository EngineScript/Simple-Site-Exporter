<?php
/*
Plugin Name: EngineScript: Simple Site Exporter
Description: Exports the site files and database as a zip archive.
Version: 1.5.9
Author: EngineScript
License: GPL v3 or later
Text Domain: simple-site-exporter-enginescript
*/

// Prevent direct access. Note: Using return here instead of exit.
if ( ! defined( 'ABSPATH' ) ) {
    return; // Prevent direct access
}

// Define plugin version
if (!defined('ES_SITE_EXPORTER_VERSION')) {
    define('ES_SITE_EXPORTER_VERSION', '1.5.9');
}

// --- Admin Menu ---
function sse_admin_menu() {
    add_management_page(
        esc_html__( 'Simple Site Exporter', 'simple-site-exporter-enginescript' ), // Escaped title
        esc_html__( 'Site Exporter', 'simple-site-exporter-enginescript' ),       // Escaped menu title
        'manage_options', // Capability required
        'simple-site-exporter',
        'sse_exporter_page_html'
    );
}
add_action( 'admin_menu', 'sse_admin_menu' );

// --- Exporter Page HTML ---
function sse_exporter_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have permission to view this page.', 'simple-site-exporter-enginescript' ), 403 );
    }

    $upload_dir = wp_upload_dir();
    if ( empty( $upload_dir['basedir'] ) ) {
         wp_die( esc_html__( 'Could not determine the WordPress upload directory.', 'simple-site-exporter-enginescript' ) );
    }
    $export_dir_name = 'enginescript-sse-site-exports';
    $export_dir_path = $upload_dir['basedir'] . '/' . $export_dir_name;
    $display_path = str_replace( ABSPATH, '', $export_dir_path );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( get_admin_page_title(), 'simple-site-exporter-enginescript' ); // Use esc_html_e for translatable titles ?></h1>
        <p><?php esc_html_e( 'Click the button below to generate a zip archive containing your WordPress files and a database dump (.sql file).', 'simple-site-exporter-enginescript' ); ?></p>
        <p><strong><?php esc_html_e( 'Warning:', 'simple-site-exporter-enginescript' ); ?></strong> <?php esc_html_e( 'This can take a long time and consume significant server resources, especially on large sites. Ensure your server has sufficient disk space and execution time.', 'simple-site-exporter-enginescript' ); ?></p>
        <p style="margin-top: 15px;">
            <?php
            // printf is standard in WordPress for translatable strings with placeholders. All variables are escaped.
            printf(
                /* translators: %s: directory path */
                esc_html__( 'Exported .zip files will be saved in the following directory on the server: %s', 'simple-site-exporter-enginescript' ),
                '<code>' . esc_html( $display_path ) . '</code>'
            );
            ?>
        </p>
        <form method="post" action="" style="margin-top: 15px;">
            <?php wp_nonce_field( 'sse_export_action', 'sse_export_nonce' ); ?>
            <input type="hidden" name="action" value="sse_export_site">
            <?php submit_button( esc_html__( 'Export Site', 'simple-site-exporter-enginescript' ) ); ?>
        </form>
        <hr>
        <p>
            <?php esc_html_e( 'This plugin is part of the EngineScript project.', 'simple-site-exporter-enginescript' ); ?>
            <a href="https://github.com/EngineScript/EngineScript" target="_blank" rel="noopener noreferrer">
                <?php esc_html_e( 'Visit the EngineScript GitHub page', 'simple-site-exporter-enginescript' ); ?>
            </a>
        </p>
        <p style="color: #b94a48; font-weight: bold;">
            <?php esc_html_e( 'Important:', 'simple-site-exporter-enginescript' ); ?>
            <?php esc_html_e( 'The exported zip file is publicly accessible while it remains in the above directory. For security, you should remove the exported file from the server once you are finished downloading it.', 'simple-site-exporter-enginescript' ); ?>
        </p>
        <p style="color: #b94a48; font-weight: bold;">
            <?php esc_html_e( 'Security Notice:', 'simple-site-exporter-enginescript' ); ?>
            <?php esc_html_e( 'For your protection, the exported zip file will be automatically deleted from the server 5 minutes after it is created.', 'simple-site-exporter-enginescript' ); ?>
        </p>
    </div>
    <?php
}

// --- Handle Export Action ---
/**
 * Handles the site export process when the form is submitted.
 *
 * @todo This function is too long (>100 lines) and complex (Cyclomatic > 10, NPath > 200). Consider refactoring into smaller functions.
 */
function sse_handle_export() {
    // Sanitize and retrieve action from POST data
    // Note: Accessing $_POST directly is necessary for form handling.
    // Values are immediately sanitized and assigned to local variables.
    $post_action = isset( $_POST['action'] ) ? sanitize_key( $_POST['action'] ) : '';
    if ( 'sse_export_site' !== $post_action ) {
        return;
    }

    // Sanitize, unslash, and verify nonce from POST data
    $post_nonce = isset( $_POST['sse_export_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['sse_export_nonce'] ) ) : '';
    if ( ! $post_nonce || ! wp_verify_nonce( $post_nonce, 'sse_export_action' ) ) {
        wp_die( esc_html__( 'Nonce verification failed! Please try again.', 'simple-site-exporter-enginescript' ), 403 );
    }

    // Check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have permission to perform this action.', 'simple-site-exporter-enginescript' ), 403 );
    }

    // Increase execution time limit
    // Note: set_time_limit is discouraged but often necessary for potentially long-running exports.
    // Alternatives like background processing add significant complexity.
    if (function_exists('set_time_limit') && !ini_get('safe_mode')) {
        set_time_limit( 0 );
    }

    $upload_dir = wp_upload_dir();
    if ( empty( $upload_dir['basedir'] ) || empty( $upload_dir['baseurl'] ) ) {
         wp_die( esc_html__( 'Could not determine the WordPress upload directory or URL.', 'simple-site-exporter-enginescript' ) );
    }
    $export_dir_name = 'enginescript-sse-site-exports';
    $export_dir = $upload_dir['basedir'] . '/' . $export_dir_name;
    $export_url = $upload_dir['baseurl'] . '/' . $export_dir_name;
    wp_mkdir_p( $export_dir ); // Ensure the directory exists

    // Add an index.php file to prevent directory listing
    // Note: file_exists and file_put_contents are discouraged but used here for a simple,
    // non-critical check/write within the known writable uploads directory.
    // WP_Filesystem API adds overhead for this minor task.
    $index_file_path = $export_dir . '/index.php';
    if ( ! file_exists( $index_file_path ) ) {
        // Use WordPress Filesystem API instead of direct file operations
        global $wp_filesystem;
        if ( ! $wp_filesystem ) {
            // Standard WordPress pattern for including core files
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_require_once
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }
        
        if ( $wp_filesystem ) {
            $wp_filesystem->put_contents(
                $index_file_path,
                '<?php // Silence is golden.',
                FS_CHMOD_FILE
            );
        } else {
            error_log('Simple Site Exporter: Failed to initialize WordPress filesystem API');
            // Fallback to direct method only if WP_Filesystem fails
            @file_put_contents( $index_file_path, '<?php // Silence is golden.' );
        }
    }

    $site_name = sanitize_file_name( get_bloginfo( 'name' ) );
    $timestamp = date( 'Y-m-d_H-i-s' );
    $random_str = substr( bin2hex( random_bytes(4) ), 0, 7 );
    $db_filename = "db_dump_{$site_name}_{$timestamp}.sql";
    $zip_filename = "site_export_sse_{$random_str}_{$site_name}_{$timestamp}.zip";
    $db_filepath = $export_dir . '/' . $db_filename;
    $zip_filepath = $export_dir . '/' . $zip_filename;
    // Removed unused variable $zip_fileurl

    // --- 1. Database Export (WP-CLI recommended) ---
    $db_exported = false;
    $db_error = '';
    // Note: shell_exec is required for WP-CLI integration. Ensure server security and that the command is properly escaped.
    if ( function_exists('shell_exec') ) {
        $wp_cli_path = trim(shell_exec('which wp'));
        
        // Validate the wp-cli path before using it
        // Using file_exists() for validating WP-CLI path is a security measure before executing shell commands
        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.filesystem_file_exists
        if (!empty($wp_cli_path) && file_exists($wp_cli_path) && (strpos($wp_cli_path, '/') === 0 || strpos($wp_cli_path, '\\') === 0)) {
            // Note: escapeshellarg is used to sanitize arguments passed to shell_exec.
            $command = sprintf(
                '%s db export %s --path=%s --allow-root',
                escapeshellarg($wp_cli_path),
                escapeshellarg($db_filepath),
                escapeshellarg(ABSPATH)
            );
            $output = shell_exec($command . ' 2>&1');
            // Note: file_exists and filesize are standard for checking command output files.
            if ( file_exists( $db_filepath ) && filesize( $db_filepath ) > 0 ) {
                $db_exported = true;
            } else {
                 $db_error = !empty($output) ? trim($output) : 'WP-CLI command failed silently.';
            }
        } else {
             $db_error = esc_html__( 'Invalid WP-CLI path detected.', 'simple-site-exporter-enginescript' );
        }
    } else {
         $db_error = esc_html__( 'shell_exec function is disabled on this server.', 'simple-site-exporter-enginescript' );
    }

    // Handle DB Export Failure - Show notice and stop
    // Note: Refactored to avoid unnecessary else clause.
    if ( ! $db_exported ) {
        add_action( 'admin_notices', function() use ($db_error) {
             ?>
             <div class="notice notice-error is-dismissible">
                 <p><?php
                    printf(
                        /* translators: %s: error message */
                        esc_html__( 'Database export failed: %s. Export process halted.', 'simple-site-exporter-enginescript' ),
                        '<strong>' . esc_html( $db_error ) . '</strong>'
                    );
                 ?></p>
             </div>
             <?php
        });
        error_log("Simple Site Exporter: DB export failed - " . $db_error);
        return; // Stop the export process
    }

    // --- 2. File Export (ZipArchive) ---
    if ( ! class_exists( 'ZipArchive' ) ) {
         add_action( 'admin_notices', function() {
             ?>
             <div class="notice notice-error is-dismissible">
                 <p><?php esc_html_e( 'ZipArchive class is not available on your server. Cannot create zip file.', 'simple-site-exporter-enginescript' ); ?></p>
             </div>
             <?php
         });
         // Note: file_exists and @unlink are used for cleanup of self-created temp files.
         // WP_Filesystem->delete() adds complexity here.
         if ( file_exists( $db_filepath ) ) {
            @unlink( $db_filepath );
         }
         return; // Stop
    }

    $zip = new ZipArchive();
    if ( $zip->open( $zip_filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== TRUE ) {
        add_action( 'admin_notices', function() use ($zip_filepath) {
             $display_zip_path = str_replace( ABSPATH, '', $zip_filepath );
             ?>
             <div class="notice notice-error is-dismissible">
                 <p><?php
                    printf(
                        /* translators: %s: file path */
                        esc_html__( 'Could not create zip file at %s', 'simple-site-exporter-enginescript' ),
                        '<code>' . esc_html( $display_zip_path ) . '</code>'
                    );
                 ?></p>
             </div>
             <?php
         });
         // Cleanup DB dump if zip creation failed
         // Note: file_exists and @unlink used for cleanup.
         if ( file_exists( $db_filepath ) ) {
            @unlink( $db_filepath );
         }
         return; // Stop
    }

    // Add Database Dump to Zip
    // Note: file_exists is standard here for checking if the DB dump was created.
    if ( $db_exported && file_exists( $db_filepath ) ) {
        if ( ! $zip->addFile( $db_filepath, $db_filename ) ) {
             error_log( "Simple Site Exporter: Failed to add DB file to zip: " . $db_filepath );
        }
    }

    // Add WordPress Files
    // Note: realpath is discouraged but useful for resolving the absolute path.
    // Fallback to ABSPATH if realpath fails.
    $source_path = realpath( ABSPATH );
    if ( ! $source_path ) {
        error_log( "Simple Site Exporter: Could not resolve real path for ABSPATH. Using ABSPATH directly." );
        $source_path = ABSPATH; // Fallback
    }

    try {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $source_path, RecursiveDirectoryIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS ),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ( $files as $file_info ) {
            if ( ! $file_info->isReadable() ) {
                error_log( "Simple Site Exporter: Skipping unreadable file/dir: " . $file_info->getPathname() );
                continue;
            }

            $file = $file_info->getRealPath();
            $pathname = $file_info->getPathname();
            $relativePath = ltrim( substr( $pathname, strlen( $source_path ) ), '/' );

            if ( empty($relativePath) ) continue;

            // --- Exclusions ---
            if ( strpos( $pathname, $export_dir ) === 0 ) continue;
            if ( preg_match( '#^wp-content/(cache|upgrade|temp)/#', $relativePath ) ) continue;
            if ( preg_match( '#(^|/)\.(git|svn|hg|DS_Store|htaccess|user\.ini)$#i', $relativePath ) ) continue;

            if ( $file_info->isDir() ) {
                if ( ! $zip->addEmptyDir( $relativePath ) ) {
                     error_log( "Simple Site Exporter: Failed to add directory to zip: " . $relativePath );
                }
            } elseif ( $file_info->isFile() ) {
                $file_to_add = ($file !== false) ? $file : $pathname;
                 if ( ! $zip->addFile( $file_to_add, $relativePath ) ) {
                     error_log( "Simple Site Exporter: Failed to add file to zip: " . $relativePath . " (Source: " . $file_to_add . ")" );
                 }
            }
        } // End foreach
    } catch ( Exception $e ) {
        // Cleanup potentially created files
        // Note: file_exists and @unlink used for cleanup.
        if ( file_exists( $zip_filepath ) ) @unlink( $zip_filepath );
        if ( file_exists( $db_filepath ) ) @unlink( $db_filepath );

        add_action( 'admin_notices', function() use ($e) {
             ?>
             <div class="notice notice-error is-dismissible">
                 <p><?php
                    printf(
                        /* translators: %s: error message */
                        esc_html__( 'Error during file processing: %s', 'simple-site-exporter-enginescript' ),
                        '<strong>' . esc_html( $e->getMessage() ) . '</strong>'
                    );
                 ?></p>
             </div>
             <?php
         });
         error_log("Simple Site Exporter: Exception during file iteration - " . $e->getMessage());
         return; // Stop
    }

    $zip_close_status = $zip->close();

    // --- 3. Cleanup temporary DB file ---
    // Note: file_exists and @unlink are acceptable for cleanup of self-created temp files.
    if ( $db_exported && file_exists( $db_filepath ) ) {
        sse_safely_delete_file( $db_filepath );
    }

    // --- 4. Report Success or Failure ---
    // Note: file_exists is standard for checking if the final output file was created.
    if ( $zip_close_status && file_exists( $zip_filepath ) ) {
        // Schedule deletion of the export file after 1 hour
        if ( ! wp_next_scheduled( 'sse_delete_export_file', array( $zip_filepath ) ) ) {
            wp_schedule_single_event( time() + (5 * 60), 'sse_delete_export_file', array( $zip_filepath ) );
        }
        add_action( 'admin_notices', function() use ( $zip_filename, $zip_filepath ) {
            $download_url = add_query_arg(
                array(
                    'sse_secure_download' => $zip_filename,
                    'sse_download_nonce' => wp_create_nonce('sse_secure_download')
                ),
                admin_url()
            );
            
            $delete_url = add_query_arg(
                array(
                    'sse_delete_export' => $zip_filename,
                    'sse_delete_nonce' => wp_create_nonce('sse_delete_export')
                ),
                admin_url()
            );
            
            $display_zip_path = str_replace( ABSPATH, '[wp-root]/', $zip_filepath );
            $display_zip_path = preg_replace('|/+|', '/', $display_zip_path); // Clean up any double slashes
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <?php esc_html_e( 'Site export successfully created!', 'simple-site-exporter-enginescript' ); ?>
                    <?php 
                    // Echo with esc_url() is the WordPress standard approach and is secure
                    // phpcs:ignore WordPress.Security.EchoNotEscaped.OutputNotEscaped
                    ?>
                    <a href="<?php echo esc_url( $download_url ); ?>" class="button" style="margin-left: 10px;">
                        <?php esc_html_e( 'Download Export File', 'simple-site-exporter-enginescript' ); ?>
                    </a>
                    <?php 
                    // Echo with esc_url() is the WordPress standard approach and is secure
                    // phpcs:ignore WordPress.Security.EchoNotEscaped.OutputNotEscaped
                    ?>
                    <a href="<?php echo esc_url( $delete_url ); ?>" class="button button-secondary" style="margin-left: 10px;" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this export file?', 'simple-site-exporter-enginescript' ); ?>');">
                        <?php esc_html_e( 'Delete Export File', 'simple-site-exporter-enginescript' ); ?>
                    </a>
                </p>
                <p><small><?php
                    printf(
                        /* translators: %s: file path */
                        esc_html__('File location: %s', 'simple-site-exporter-enginescript'),
                        '<code title="' . esc_attr__('Path is relative to WordPress root directory', 'simple-site-exporter-enginescript') . '">' . 
                        esc_html($display_zip_path) . '</code>'
                    );
                 ?></small></p>
            </div>
            <?php
        });
        error_log("Simple Site Exporter: Export successful. File saved to " . $zip_filepath);
    } else {
        // Add a generic error notice if the zip failed at the end
         add_action( 'admin_notices', function() {
             ?>
             <div class="notice notice-error is-dismissible">
                 <p><?php esc_html_e( 'Failed to finalize or save the zip archive after processing files.', 'simple-site-exporter-enginescript' ); ?></p>
             </div>
             <?php
         });
         // Note: file_exists is appropriate for logging state.
         error_log("Simple Site Exporter: Export failed. Zip close status: " . ($zip_close_status ? 'OK' : 'FAIL') . ", File exists: " . (file_exists($zip_filepath) ? 'Yes' : 'No'));
         // Attempt cleanup using @unlink, acceptable for self-created files.
         // Note: file_exists and @unlink used for cleanup.
         if ( file_exists( $zip_filepath ) ) {
            @unlink( $zip_filepath );
         }
    }
}
add_action( 'admin_init', 'sse_handle_export' );

// --- Scheduled Deletion Handler ---
function sse_delete_export_file_handler( $file ) {
    if ( file_exists( $file ) ) {
        sse_safely_delete_file( $file );
        error_log( 'Simple Site Exporter: Scheduled deletion of export file: ' . $file );
    }
}
add_action( 'sse_delete_export_file', 'sse_delete_export_file_handler' );

/**
 * Safely delete a file using WordPress Filesystem API
 *
 * @param string $filepath Path to the file to delete
 * @return bool Whether the file was deleted successfully
 */
function sse_safely_delete_file($filepath) {
    global $wp_filesystem;
    
    // Initialize the WordPress filesystem
    if (empty($wp_filesystem)) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
    }
    
    if (!$wp_filesystem) {
        error_log('Simple Site Exporter: Failed to initialize WordPress filesystem API');
        return false;
    }
    
    // Check if the file exists using WP Filesystem
    if ($wp_filesystem->exists($filepath)) {
        // Delete the file using WordPress Filesystem API
        return $wp_filesystem->delete($filepath, false, 'f');
    }
    
    return false;
}

/**
 * Shared file validation logic used by both download and deletion operations
 * 
 * @param string $filename The filename to validate
 * @param bool $check_filesize Whether to check file size (for downloads)
 * @return array|WP_Error Result array with file data or WP_Error on failure
 */
function sse_validate_export_file($filename, $check_filesize = false) {
    if (empty($filename)) {
        return new WP_Error('invalid_request', esc_html__('No file specified.', 'simple-site-exporter-enginescript'));
    }
    
    // Prevent path traversal attacks
    if (strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
        return new WP_Error('invalid_filename', esc_html__('Invalid filename.', 'simple-site-exporter-enginescript'));
    }
    
    // Validate that it's our export file format
    if (!preg_match('/^site_export_sse_[a-f0-9]{7}_[a-zA-Z0-9_-]+_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.zip$/', $filename)) {
        return new WP_Error('invalid_format', esc_html__('Invalid export file format.', 'simple-site-exporter-enginescript'));
    }
    
    // Get the full path to the file
    $upload_dir = wp_upload_dir();
    $export_dir = $upload_dir['basedir'] . '/enginescript-sse-site-exports';
    $file_path = $export_dir . '/' . $filename;
    
    // Validate the file path is within our export directory
    if (!sse_validate_filepath($file_path, $export_dir)) {
        return new WP_Error('invalid_path', esc_html__('Invalid file path.', 'simple-site-exporter-enginescript'));
    }
    
    // Use WordPress filesystem API
    global $wp_filesystem;
    
    // Initialize the WordPress filesystem
    if (empty($wp_filesystem)) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
    }
    
    // Check if file exists using WP Filesystem
    if (!$wp_filesystem->exists($file_path)) {
        return new WP_Error('file_not_found', esc_html__('Export file not found.', 'simple-site-exporter-enginescript'));
    }
    
    // For download requests, check additional conditions
    if ($check_filesize) {
        // Check if file is readable
        if (!$wp_filesystem->is_readable($file_path)) {
            return new WP_Error('file_not_readable', esc_html__('Export file not readable.', 'simple-site-exporter-enginescript'));
        }
        
        // Get file size using WP Filesystem
        $file_size = $wp_filesystem->size($file_path);
        if (!$file_size) {
            return new WP_Error('file_size_error', esc_html__('Could not determine file size.', 'simple-site-exporter-enginescript'));
        }
    } else {
        $file_size = 0; // Not needed for deletion operations
    }
    
    // Add referer check for request validation
    $referer = wp_get_referer();
    if (!$referer || strpos($referer, admin_url()) !== 0) {
        return new WP_Error('invalid_request_source', esc_html__('Invalid request source.', 'simple-site-exporter-enginescript'));
    }
    
    // Create the result array with common properties
    $result = array(
        'filepath' => $file_path,
        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_basename
        'filename' => basename($file_path)
    );
    
    // Add filesize for download operations
    if ($check_filesize) {
        $result['filesize'] = $file_size;
    }
    
    return $result;
}

/**
 * Validate export download request parameters
 * 
 * @param string $filename The filename to validate
 * @return array|WP_Error Result array with file path and size or WP_Error on failure
 */
function sse_validate_download_request($filename) {
    // Use the shared validation function with filesize checking enabled
    return sse_validate_export_file($filename, true);
}

/**
 * Validate file deletion request
 *
 * @param string $filename The filename to validate
 * @return array|WP_Error Result array with file path or WP_Error on failure
 */
function sse_validate_file_deletion($filename) {
    // Use the shared validation function with filesize checking disabled
    return sse_validate_export_file($filename, false);
}