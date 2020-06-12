add_action( 'admin_init' , 'refresh_admin_after_purge' , 9999 );

function refresh_admin_after_purge() {

		global $wp;
		$custom = false;

		$method = filter_input( INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_STRING );

		if ( 'POST' === $method ) {
			$action = filter_input( INPUT_POST, 'nginx_helper_action', FILTER_SANITIZE_STRING );
		} else {
			$action = filter_input( INPUT_GET, 'nginx_helper_action', FILTER_SANITIZE_STRING );
		}

		if( $action == null ) {

			if ( 'POST' === $method ) {
				$action = filter_input( INPUT_POST, 'nginx_custom_action', FILTER_SANITIZE_STRING );
			} else {
				$action = filter_input( INPUT_GET, 'nginx_custom_action', FILTER_SANITIZE_STRING );
			}

			$custom = true;
		}

		if ( empty( $action ) ) {
			return;
		}

		if ( 'done' === $action && is_admin() ) {

			if( $custom ) {

				add_action( 'admin_notices', 'purge_display_notices' );
				add_action( 'network_admin_notices', 'purge_display_notices' );
				return;
			}

			$req= remove_query_arg( array_keys( $_GET ), $wp->request );
			$redirect_url = add_query_arg( 'nginx_custom_action', 'done', $req );
			wp_redirect( esc_url_raw( $redirect_url ) );
			exit;
		}
}

add_filter( 'user_has_cap', 'let_editors_purge', 9999, 3 );
function let_editors_purge( $allcaps, $caps, $args ){

	// Bail out for users who can already purge:
	if ( $allcaps['manage_options'] )
		return $allcaps;

	// Bail out for users who can't publish posts:
	if ( !isset( $allcaps['publish_posts'] ) or !$allcaps['publish_posts'] )
		return $allcaps;

	if( isset( $_GET['nginx_helper_action'] ) && isset( $_GET['nginx_helper_urls'] ) ) {
				$allcaps['manage_options'] = true;
	}

	return $allcaps;
}

	function purge_display_notices() {
		echo '<div class="updated"><p>' . esc_html__( 'Purge initiated', 'nginx-helper' ) . '</p></div>';
	}

add_action( 'admin_bar_menu', 'admin_bar_add_nginx_helper_purge_button_for_editors', 80 );
function admin_bar_add_nginx_helper_purge_button_for_editors() {
		global $wp_admin_bar;

		// https://wordpress.org/plugins/nginx-helper/
		// check if plugin nginx helper is active
		if( ! is_plugin_active('nginx-helper/nginx-helper.php')) {
			return;
		}

		// check if shop_manager or editor
		//if( ! current_user_can('editor') && ! current_user_can('shop_manager')) {
		//	return;
		//}

		if( ! current_user_can('publish_posts') ) {
			return;
		}

		//if ( !isset( $allcaps['publish_posts'] ) or !$allcaps['publish_posts'] )
		//	return $allcaps;

		if ( is_admin() ) {
			$nginx_helper_urls = 'all';
			$link_title        = __( 'Purge Cache', 'kick-off-wp' );
		} else {
			$nginx_helper_urls = 'current-url';
			$link_title        = __( 'Purge Cache Page', 'kick-off-wp' );
		}


		$purge_url = add_query_arg(
			array(
				'nginx_helper_action' => 'purge',
				'nginx_helper_urls'   => $nginx_helper_urls,
			)
		);

		$nonced_url = wp_nonce_url( $purge_url, 'nginx_helper-purge_all' );

		$args = array(
			'id' => 'kowp_purge_nginx_helper_cache',
			'title' => $link_title,
			'href' => $nonced_url,
			'meta' => array(
				'title' => $link_title,
			),
		);

		$wp_admin_bar->add_menu( $args );

	}
