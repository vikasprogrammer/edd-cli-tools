<?php
/**
 * Easy Digital Downloads WP-CLI EDD Extended
 *
 * This class provides an integration point with the WP-CLI plugin allowing
 * access to EDD from the command line.
 *
 * @package     EDD
 * @subpackage  Classes/CLI
 * @copyright   Copyright (c) 2015, Pippin Williamson
 * @license     http://opensource.org/license/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

WP_CLI::add_command( 'edd', 'EDD_CLI_Toolbox' );

/**
 * Work with EDD through WP-CLI
 *
 * EDD_CLI Class
 *
 * Adds CLI support to EDD through WP-CL
 *
 * @since   1.0
 */
class EDD_CLI_Toolbox extends EDD_CLI {

	//wp edd update_download 57 --version=1.0.1 --file_path=/tmp/file.zip

	public function new_release ( $args, $assoc_args ) {

		// Check validity of username or ID, retrieve the user object.
		if ( empty( $args[0] ) ) {

			\WP_CLI::error( __( 'A valid license ID must be specified as the first argument.' ) );

		} else {
			$download_id = $args[0];
			$download = get_post( $args[0] );
			// echo $license->post_type;

			if ( ! $download || 'download' !== $download->post_type ) {
				\WP_CLI::error( sprintf( __( 'No product was found with ID %d.' ), $download_id ) );
			} else {
				WP_CLI::success( 'Found the product with ID ' . $download_id );
			}

		}


		$version    = isset( $assoc_args['version'] ) ? $assoc_args['version'] : false;
		$file_path = isset( $assoc_args['file_path'] ) ? $assoc_args['file_path'] : false;

		if($version == false || $file_path == false) {
			WP_CLI::error( 'Version or file path missing (--version= --file_path=)');
			exit;
		}

	

		if(!file_exists($file_path)) {
			WP_CLI::error( 'File doesnt exist' );
			exit;
		}

		$files = get_post_meta( $download_id , 'edd_download_files');
		$files = $files[0];
// var_dump($files);exit;
		if(count($files) > 0) {
			$latest_file = $files[count($files)];
			if($latest_file['name'] == basename($file_path)) {
				WP_CLI::error( 'Looks like you have already uploaded this file ' . basename($file_path));
				exit;
			}
		}

		$current_version = get_post_meta( $download_id, '_edd_sl_version', (string) $version );

		if($current_version == $version) {
			WP_CLI::error( 'Looks like you have already set this version ' .$version);
				exit;
		}

		$file_array = array(
		    'name' => basename( $file_path ),
		    'tmp_name' => $file_path
		);

		$wp_upload_dir = wp_upload_dir();
		$wp_upload_dir = edd_set_upload_dir($wp_upload_dir);
		 
		// Prepare an array of post data for the attachment.
		$attachment = array(
		    'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ), 
		    'post_mime_type' => $filetype['type'],
		    'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
		    'post_content'   => '',
		    'post_status'    => 'inherit'
		);

		// var_dump($attachment);exit;
		add_filter( 'upload_dir', 'edd_set_upload_dir' );

		WP_CLI::line( 'Uploading media');

		$attachment_id = media_handle_sideload( $file_array, $download_id, null, $attachment );

		if ( is_wp_error( $result ) ) {
		    WP_CLI::error("File upload error");
		    exit;
		}

		$file_url = wp_get_attachment_url( $attachment_id );

		


		$new_file = ["thumbnail_size" => false, "name" => basename($file_path), "file" => $file_url, "condition" => "all"];
		array_push($files, $new_file);

		update_post_meta( $download_id , 'edd_download_files', $files);

		update_post_meta( $download_id, '_edd_sl_version', (string) $version );

		update_post_meta( $download_id, '_edd_sl_upgrade_file_key', 4 );

		WP_CLI::success( 'All done!');

		
	}

}
