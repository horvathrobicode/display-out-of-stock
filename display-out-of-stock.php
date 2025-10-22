add_action( 'woocommerce_product_query', function( $q ) {
    // 1) Remove product_visibility tax queries that exclude outofstock
    $tax_query = $q->get( 'tax_query' );
    if ( ! empty( $tax_query ) && is_array( $tax_query ) ) {
        foreach ( $tax_query as $idx => $tax ) {
            if ( isset( $tax['taxonomy'] ) && $tax['taxonomy'] === 'product_visibility' ) {
                unset( $tax_query[ $idx ] );
            }
        }
        $q->set( 'tax_query', $tax_query );
    }

    $post_in = $q->get( 'post__in' );
    $term = null;

    
    $term_slug = $q->get( 'term' );
    if ( empty( $term_slug ) ) {
        $queried = get_queried_object();
        if ( $queried && isset( $queried->taxonomy ) && $queried->taxonomy === 'product_cat' ) {
            $term = $queried;
        }
    } else {
        $term = get_term_by( 'slug', $term_slug, 'product_cat' );
    }

    if ( ! empty( $post_in ) && is_array( $post_in ) && $term ) {
        // get out-of-stock product IDs in this term
        $args = [
            'limit'        => -1,
            'status'       => 'publish',
            'stock_status' => 'outofstock',
            'return'       => 'ids',
            'category'     => [$term->slug],
        ];
        $out_ids = wc_get_products( $args ); // returns array of IDs

        if ( ! empty( $out_ids ) ) {
            $new_post_in = array_values( array_unique( array_merge( $post_in, $out_ids ) ) );
            $q->set( 'post__in', $new_post_in );
        }
    }

    // Ensure publish
    $q->set( 'post_status', 'publish' );

}, 999 );