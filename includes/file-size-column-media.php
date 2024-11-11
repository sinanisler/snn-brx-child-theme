<?php 
// Add a new 'File Size' column to the media library.

function add_file_size_column( $columns ) {
    $columns['file_size'] = __( 'File Size', 'textdomain' ); 
    return $columns;
}
add_filter( 'manage_upload_columns', 'add_file_size_column' );

// Populate the 'File Size' column with the actual file size.
function populate_file_size_column( $column_name, $post_id ) {
    if ( 'file_size' == $column_name ) {
        $file_path = get_attached_file( $post_id ); // Get the file path.

        if ( file_exists( $file_path ) ) {
            // Retrieve the file size.
            $file_size = filesize( $file_path );

            // Format the file size.
            echo size_format( $file_size, 2 );
        } else {
            echo '—';
        }
    }
}
add_action( 'manage_media_custom_column', 'populate_file_size_column', 10, 2 );




