<?php

namespace TheDramatist\WooComAW\AccountWidgetCore;

/**
 * Class AccountWidgetCore
 *
 * @package TheDramatist\WooComAW\AccountWidgetCore
 */
class AccountWidgetCore extends \WP_Widget {

	/**
	 * AccountWidgetCore constructor.
	 */
	public function __construct() {

		$widget_ops = [
			'classname'   => 'WooComAW',
			'description' => __( 'WooCom Account Widget shows order & account data', 'woocom-aw' ),
		];
		parent::__construct( 'WooComAW',
		                     __( 'WooCom Account Widget', 'woocom-aw' ), $widget_ops );
	}

	/**
	 * This method is responsible for showing the bbackend form for the Widget.
	 *
	 * @param array $instance
	 */
	public function form( $instance ) {

		$instance         = wp_parse_args( (array) $instance, ['title' => ''] );
		$show_cartlink    = isset( $instance['show_cartlink'] ) ? (bool) $instance['show_cartlink'] : false;
		$show_items       = isset( $instance['show_items'] ) ? (bool) $instance['show_items'] : false;
		$show_upload      = isset( $instance['show_upload'] ) ? (bool) $instance['show_upload'] : false;
		$show_upload_new  = isset( $instance['show_upload_new'] ) ? (bool) $instance['show_upload_new'] : false;
		$show_unpaid      = isset( $instance['show_unpaid'] ) ? (bool) $instance['show_unpaid'] : false;
		$show_pending     = isset( $instance['show_pending'] ) ? (bool) $instance['show_pending'] : false;
		$show_logout_link = isset( $instance['show_logout_link'] ) ? (bool) $instance['show_logout_link'] : false;
		$login_with_email = isset( $instance['login_with_email'] ) ? (bool) $instance['login_with_email'] : false;

		include 'Views/HtmlFormView.php';
	}

	/**
	 * This method is reponsible for updating the backend form of the widget.
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {

		$instance                     = $old_instance;
		$instance['logged_out_title'] = strip_tags( stripslashes( $new_instance['logged_out_title'] ) );
		$instance['logged_in_title']  = strip_tags( stripslashes( $new_instance['logged_in_title'] ) );
		$instance['show_cartlink']    = ! empty( $new_instance['show_cartlink'] ) ? 1 : 0;
		$instance['show_items']       = ! empty( $new_instance['show_items'] ) ? 1 : 0;
		$instance['show_upload']      = ! empty( $new_instance['show_upload'] ) ? 1 : 0;
		$instance['show_upload_new']  = ! empty( $new_instance['show_upload_new'] ) ? 1 : 0;
		$instance['show_unpaid']      = ! empty( $new_instance['show_unpaid'] ) ? 1 : 0;
		$instance['show_pending']     = ! empty( $new_instance['show_pending'] ) ? 1 : 0;
		$instance['show_logout_link'] = ! empty( $new_instance['show_logout_link'] ) ? 1 : 0;
		$instance['login_with_email'] = ! empty( $new_instance['login_with_email'] ) ? 1 : 0;
		$instance['woocom_aw_redirect']     = esc_attr( $new_instance['woocom_aw_redirect'] );

		if ( (int) $instance['login_with_email'] === 1 ) {
			add_option( 'woocom_aw_login_with_email', $new_instance['login_with_email'] );
		} else {
			delete_option( 'woocom_aw_login_with_email' );
		}

		return $instance;
	}

	/**
	 * This method is the fornt end of the Widget.
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {

		extract( $args, EXTR_SKIP );
		global $woocommerce;

		$logged_out_title = apply_filters( 'widget_title',
		                                   empty( $instance['logged_out_title'] ) ? __( 'Customer Login', 'woocom-aw' )
			                                   : $instance['logged_out_title'], $instance );
		$logged_in_title = apply_filters(
			'widget_title',
			/* translators: %s: The Widget title when user is logged in. */
			empty( $instance['logged_in_title'] ) ? __( 'Welcome %s', 'woocom-aw' )
				: $instance['logged_in_title'], $instance );

		echo $before_widget;

		$c            = ( isset( $instance['show_cartlink'] ) && $instance['show_cartlink'] ) ? '1' : '0';
		$cart_page_id = get_option( 'woocommerce_cart_page_id' );

		//check if user is logged in
		if ( is_user_logged_in() ) {

			$it   = ( isset( $instance['show_items'] ) && $instance['show_items'] ) ? '1' : '0';
			$u    = ( isset( $instance['show_upload'] ) && $instance['show_upload'] ) ? '1' : '0';
			$unew = ( isset( $instance['show_upload_new'] ) && $instance['show_upload_new'] ) ? '1' : '0';
			$up   = ( isset( $instance['show_unpaid'] ) && $instance['show_unpaid'] ) ? '1' : '0';
			$p    = ( isset( $instance['show_pending'] ) && $instance['show_pending'] ) ? '1' : '0';
			$lo   = ( isset( $instance['show_logout_link'] ) && $instance['show_logout_link'] ) ? '1' : '0';

			// redirect url after login / logout
			if ( is_multisite() ) {
				$woo_aw_home = network_home_url();
			} else {
				$woo_aw_home = home_url();
			}

			$user = get_user_by( 'id', get_current_user_id() );
			echo '<div class=login>';
			if ( $user->first_name !== '' ) {
				$uname = $user->first_name;
			} else {
				$uname = $user->display_name;
			}
			if ( $logged_in_title ) {
				echo $args['before_title'] . sprintf( $logged_in_title, ucwords( $uname ) ) . $args['after_title'];
			}

			if ( $c ) {
				echo '<p><a class="woocom-aw-button cart-link woocom-aw-cart-link" href="'
				     . get_permalink( $this->lang_id( $cart_page_id ) ) .
				     '" title="' . __( 'View your shopping cart', 'woocom-aw' ) .
				     '">' . __( 'View your shopping cart', 'woocom-aw' ) . '</a></p>';
			}

			$notcompleted   = 0;
			$uploadfile     = 0;
			$uploadfile_new = 0;
			$notpaid        = 0;
			$customer_id    = get_current_user_id();
			if ( version_compare( WOOCOMMERCE_VERSION, '2.2' ) < 0 ) {

				$posts_args = [
					'numberposts' => - 1,
					'meta_key'    => '_customer_user',
					'meta_value'  => get_current_user_id(),
					'post_type'   => 'shop_order',
					'post_status' => 'publish',
				];
				$customer_orders = get_posts( $posts_args );

			} else {

				$posts_args = [
					'numberposts' => - 1,
					'meta_key'    => '_customer_user',
					'meta_value'  => get_current_user_id(),
					'post_type'   => wc_get_order_types( 'view-orders' ),
					'post_status' => array_keys( wc_get_order_statuses() ),
				];
				$customer_orders = get_posts( apply_filters( 'woocom_aw_my_orders_query', $posts_args ) );

			}
			if ( $customer_orders ) {
				foreach ( $customer_orders as $customer_order ) :
					$woocommerce1 = 0;
					if ( version_compare( WOOCOMMERCE_VERSION, "2.2" ) < 0 ) {
						$order = new \WC_Order();
						$order->populate( $customer_order );
					} else {
						$order = wc_get_order( $customer_order->ID );
					}

					if ( $this->get_order_data( $order, 'status' ) !== 'completed'
					     && $this->get_order_data( $order, 'status' ) !== 'cancelled' ) {
						$notcompleted ++;
					}

					/* upload files */
					if ( function_exists( 'woocommerce_umf_admin_menu' ) ) {
						if ( get_max_upload_count( $order ) > 0 ) {
							$j = 1;
							foreach ( $order->get_items() as $order_item ) {
								$max_upload_count = get_max_upload_count( $order, $order_item['product_id'] );
								$i                = 1;
								$upload_count     = 0;
								while ( $i <= $max_upload_count ) {
									if (
										get_post_meta(
											$this->get_order_data( $order, 'id' ),
											'_woo_umf_uploaded_file_name_' . $j,
											true
										) !== ''
									) {
										$upload_count ++;
									}
									$i ++;
									$j ++;
								}
								/* toon aantal nog aan te leveren bestanden */
								$upload_count = $max_upload_count - $upload_count;
								$uploadfile   += $upload_count;
							}
						}
					}

					if ( class_exists( 'WPF_Uploads' ) ) {

						// Uploads needed
						$uploads_needed     = \WPF_Uploads::order_needs_upload( $order, true );
						$uploaded_count_new = \WPF_Uploads::order_get_upload_count(
							$this->get_order_data( $order, 'id' )
						);

						$uploads_needed_left = $uploads_needed - $uploaded_count_new;

						$uploadfile_new = $uploadfile_new + $uploads_needed_left;
					}

					if (
						in_array(
							$this->get_order_data( $order, 'status' ),
							[ 'on-hold', 'pending', 'failed' ],
							true
						)
					) {
						$notpaid ++;
					}
				endforeach;
			}

			$my_account_id = $this->lang_id( get_option( 'woocommerce_myaccount_page_id' ) );

			echo '<ul class="clearfix woocom-aw-list">';
			if ( $it ) {
				echo '<li class="woocom-aw-link item">
						<a class="cart-contents-new" href="'
				     . get_permalink( $this->lang_id( $cart_page_id ) ) . '" title="'
				     . __( 'View your shopping cart', 'woocom-aw' ) . '"><span>'
				     . $woocommerce->cart->cart_contents_count . '</span> '
				     . _n( 'product in your shopping cart', 'products in your shopping cart',
				           $woocommerce->cart->cart_contents_count, 'woocom-aw' ) . '
						</a>
					</li>';
			}
			if ( $u && function_exists( 'woocommerce_umf_admin_menu' ) ) {

				echo '<li class="woocom-aw-link upload">
						<a href="' . get_permalink( $my_account_id ) . '" title="'
				     . __( 'Upload files', 'woocom-aw' ) . '"><span>' . $uploadfile . '</span> '
				     . _n( 'file to upload', 'files to upload', $uploadfile, 'woocom-aw' ) . '
						</a>
					</li>';
			}
			if ( $unew && class_exists( 'WPF_Uploads' ) ) {

				echo '<li class="woocom-aw-link upload">
						<a href="' . get_permalink( $my_account_id ) . '" title="'
				     . __( 'Upload files', 'woocom-aw' ) . '"><span>' . $uploadfile_new . '</span> '
				     . _n( 'file to upload', 'files to upload', $uploadfile_new, 'woocom-aw' ) . '
						</a>
					</li>';
			}
			if ( $up ) {
				echo '<li class="woocom-aw-link paid">
						<a href="' . get_permalink( $my_account_id ) . '" title="'
				     . __( 'Pay orders', 'woocom-aw' ) . '"><span>' . $notpaid . '</span> '
				     . _n( 'payment required', 'payments required', $notpaid, 'woocom-aw' ) . '
						</a>
					</li>';
			}
			if ( $p ) {
				echo '<li class="woocom-aw-link pending">
						<a href="' . get_permalink( $my_account_id ) . '" title="'
				     . __( 'View uncompleted orders', 'woocom-aw' ) . '"><span>' . $notcompleted . '</span> '
				     . _n( 'order pending', 'orders pending', $notcompleted, 'woocom-aw' ) . '
						</a>
					</li>';
			}
			echo '</ul>';
			echo '<p><a class="woocom-aw-button woocom-aw-myaccount-link myaccount-link" href="'
			     . get_permalink( $my_account_id ) . '" title="' .
			     __( 'My Account', 'woocom-aw' ) . '">'
			     . __( 'My Account', 'woocom-aw' ) . '</a></p>';
			if ( $lo === 1 ) {
				echo '<p><a class="woocom-aw-button woocom-aw-logout-link logout-link" href="'
				     . wp_logout_url( $woo_aw_home ) .
				     '" title="' . __( 'Log out', 'woocom-aw' ) .
				     '">' . __( 'Log out', 'woocom-aw' ) . '</a></p>';
			}
		} else {
			echo '<div class=logout>';
			// user is not logged in
			if ( $logged_out_title ) {
				echo $args['before_title'] . $logged_out_title . $args['after_title'];
			}
			if ( isset( $_GET['login'] ) && $_GET['login'] === 'failed' ) {
				echo '<p class="woocom-aw-login-failed woocom-aw-error">';
				_e( 'Login failed, please try again', 'woocom-aw' );
				echo '</p>';
			}
			// login form
			$args = [
				'echo'           => true,
				'form_id'        => 'woocom_aw_login_form',
				'label_username' => __( 'Username', 'woocom-aw' ),
				'label_password' => __( 'Password', 'woocom-aw' ),
				'label_remember' => __( 'Remember Me', 'woocom-aw' ),
				'label_log_in'   => __( 'Log In', 'woocom-aw' ),
				'id_username'    => 'user_login',
				'id_password'    => 'user_pass',
				'id_remember'    => 'rememberme',
				'id_submit'      => 'wp-submit',
				'remember'       => true,
				'value_username' => null,
				'value_remember' => false,
			];

			if ( isset( $instance['woocom_aw_redirect'] ) && $instance['woocom_aw_redirect'] !== '' ) {
				$args['redirect'] = get_permalink( $this->lang_id( $instance['woocom_aw_redirect'] ) );
			}

			wp_login_form( $args );
			echo '<a class="woocom-aw-link woocom-aw-lost-pass" href="'
			     . wp_lostpassword_url() . '">' .
			     __( 'Lost password?', 'woocom-aw' ) . '</a>';

			if ( get_option( 'users_can_register' ) ) {
				echo '<a class="woocom-aw-button woocom-aw-register-link register-link" href="'
				     . get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) .
				     '" title="'
				     . __( 'Register', 'woocom-aw' ) . '">' . __( 'Register', 'woocom-aw' ) . '</a>';
			}
			if ( $c ) {
				echo '<p><a class="woocom-aw-button woocom-aw-cart-link cart-link" href="'
				     . get_permalink( $this->lang_id( $cart_page_id ) ) .
				     '" title="'
				     . __( 'View your shopping cart', 'woocom-aw' ) . '">' .
				     __( 'View your shopping cart', 'woocom-aw' ) . '</a></p>';
			}
		}
		echo '</div>';
		echo $after_widget;
	}

	/**
	 * Get language ID for WPML
	 *
	 * @param $id
	 *
	 * @return mixed
	 */
	public function lang_id( $id ) {
		if(function_exists('icl_object_id')) {
			return icl_object_id($id,'page',true);
		} else {
			return $id;
		}
	}

	/**
	 * Get order data by Order object
	 *
	 * Used for backward compatibility for WC < 3.0
	 * @param \WC_Order $order
	 * @param string $data Data to retreive
	 *
	 * @return mixed
	 */
	public function get_order_data( $order, $data ) {
		if( version_compare( WC_VERSION, '3.0', '<' ) ) {

			switch ($data) {

				case 'user_id':
					return $order->user_id;
				case 'id':
					return $order->ID;
				case 'status':
					return $order->status;

			}

		} else {

			switch ($data) {

				case 'user_id':
					return $order->get_user_id();
				case 'id':
					return $order->get_id();
				case 'status':
					return $order->get_status();

			}

		}

	}
}