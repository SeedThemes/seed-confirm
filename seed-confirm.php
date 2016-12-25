<?php
/*
Plugin Name: Seed Confirm
Plugin URI: https://www.seedthemes.com/plugin/seed-confirm
Description: WooCommerce extension that creates confirmation form for bank transfer payment.
Version: 0.8.0
Author: SeedThemes
Author URI: https://www.seedthemes.com
License: GPL2
Text Domain: seed-confirm
*/

/*
Copyright 2016 SeedThemes  (email : info@seedthemes.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Load text domain.
 */
load_plugin_textdomain('seed-confirm', false, basename( dirname( __FILE__ ) ) . '/languages' );

if(!class_exists('Seed_Confirm'))
{
	class Seed_Confirm
	{
        /**
         * Construct the plugin object
         */
        public function __construct()
        {
            // register actions
        } // END public function __construct

        /**
         * Activate the plugin
         */
        public static function activate()
        {
            // Add Default payment-confirm page.
            $page = get_page_by_path('confirm-payment');

            if (!is_object($page)) {
                global $user_ID;

                $page = array(
                    'post_type'      => 'page',
                    'post_name'      => 'confirm-payment',
                    'post_parent'    => 0,
                    'post_author'    => $user_ID,
                    'post_status'    => 'publish',
                    'post_title'     => __('Confirm Payment', 'seed-confirm'),
                    'post_content'   => '[seed_confirm]',
                    'ping_status'    => 'closed',
                    'comment_status' => 'closed',
                );

                $page_id = wp_insert_post($page);
            }else{
                $page_id = $page->ID;
            }

            // Add default plugin's settings.
            add_option( 'seed_confirm_page', $page_id);
            add_option( 'seed_confirm_notification_text', __( 'Thank you for your payment. We will process your order shortly.', 'seed-confirm' ) );
            add_option( 'seed_confirm_notification_bg_color', '#57AD68' );
            add_option( 'seed_confirm_required', json_encode( array(
                'seed_confirm_name' => 'true',
                'seed_confirm_contact' => 'true',
                'seed_confirm_amount' => 'true',
            ) ) );
        } // END public static function activate

        /**
         * Deactivate the plugin
         */     
        public static function deactivate()
        {
            // Do nothing
        } // END public static function deactivate
    } // END class Seed_Confirm
} // END if(!class_exists('Seed_Confirm'))

if(class_exists('Seed_Confirm'))
{
    // Installation and uninstallation hooks
	register_activation_hook(__FILE__, array('Seed_Confirm', 'activate'));
	register_deactivation_hook(__FILE__, array('Seed_Confirm', 'deactivate'));

    // instantiate the plugin class
	$Seed_Confirm = new Seed_Confirm();
}

/**
 * Check woo-commerce plugin is installed and activated or not.
 * @return bool
 */
if ( ! function_exists( 'is_woocommerce_activated' ) ) {
	function is_woocommerce_activated() {
		if ( class_exists( 'woocommerce' ) ) { return true; } else { return false; }
	}
}

/**
 * Remove all woocommerce_thankyou_bacs hooks.
 * Cause we don't want to display all bacs from woocommerce.
 * Web show new one that is better.
 */
add_action( 'template_redirect', 'seed_confirm_remove_hook_thankyou_bacs' );

function seed_confirm_remove_hook_thankyou_bacs() {
	if(is_woocommerce_activated()){
		$gateways = WC()->payment_gateways()->payment_gateways();
		remove_action( 'woocommerce_thankyou_bacs', array( $gateways[ 'bacs' ], 'thankyou_page' ) );
	}
}

/**
 * Remove the original bank details
 * @link http://www.vanbodevelops.com/tutorials/remove-bank-details-from-woocommerce-order-emails
 */
add_action('init', 'seed_confirm_remove_bank_details', 100);

function seed_confirm_remove_bank_details()
{
	if (!is_woocommerce_activated()) {
		return;
	}

	// Get the gateways instance
	$gateways = WC_Payment_Gateways::instance();

	// Get all available gateways, [id] => Object
	$available_gateways = $gateways->get_available_payment_gateways();

	if (isset($available_gateways['bacs'])) {
		// If the gateway is available, remove the action hook
		remove_action('woocommerce_email_before_order_table', array($available_gateways['bacs'], 'email_instructions'), 10, 3);
	}
}

/**
 * Add bank lists to these pages.
 * Confirm page
 * Thankyou page
 * Thankyou email
 */
add_shortcode( 'seed_confirm_banks', 'seed_confirm_banks' );
add_action( 'woocommerce_thankyou_bacs', 'seed_confirm_banks', 10);
add_action( 'woocommerce_email_before_order_table', 'seed_confirm_banks');

function seed_confirm_banks( $orderid ) {
	$thai_accounts = array();

	$bacs = WC()->payment_gateways->get_available_payment_gateways();

	$bacs_settings = reset( $bacs );

	$thai_accounts = $bacs_settings->account_details;
?>

<?php do_action('seed_confirm_before_banks', $orderid); ?>

<div id="seed-confirm-banks" class="seed-confirm-banks">
	<h2><?php esc_html_e( 'Our Bank Details', 'seed-confirm' ); ?></h2>
	<p><?php esc_html_e( 'Make your payment directly into our bank account.', 'seed-confirm' ); ?></p>
	<div class="table-responsive _heading">
		<table class="table">
			<thead>
				<tr>
					<th class="seed-confirm-bank-name"><?php esc_html_e( 'Bank Name', 'seed-confirm' ); ?></th>
					<th class="seed-confirm-bank-sort-code"><?php esc_html_e( 'Sort Code', 'seed-confirm' ); ?></th>
					<th class="seed-confirm-bank-account-number"><?php esc_html_e( 'Account Number', 'seed-confirm' ); ?></th>
					<th class="seed-confirm-bank-account-name"><?php esc_html_e( 'Account Name', 'seed-confirm' ); ?></th>	
				</tr>
			</thead>
			<tbody>
				<?php foreach( $thai_accounts as $_account ): ?>
				<tr>
					<td class="seed-confirm-bank-name"><?php echo $_account['bank_name']; ?></td>
					<td class="seed-confirm-bank-sort-code"><?php echo $_account['sort_code']; ?></td>
					<td class="seed-confirm-bank-account-number"><?php echo $_account['account_number'];?></td>
					<td class="seed-confirm-bank-account-name"><?php echo $_account['account_name'];?></td>		
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
<?php do_action('seed_confirm_after_banks', $orderid); ?>
<?php
}

/**
 * Get page url used to show Seed Confirm Form
 * return false if seed_confirm_page in wp_option table doesn't exist
 */
function seed_confirm_get_permalink() {
	$seed_confirm_page_id = get_option( 'seed_confirm_page', false );
	if($seed_confirm_page_id) {
		return get_permalink($seed_confirm_page_id);
	}
	return false;
}

/**
 * Check if current page is Seed Confirm page
 * This funciton should be executed in the loop or in Woocommerce template file
 */
function is_seed_confirm() {
	global $post;
	$seed_confirm_page_id = get_option( 'seed_confirm_page', false );
	$current_page_id = $post->ID;
	if($current_page_id == $seed_confirm_page_id) {
		return true;
	}
	return false;
}

/**
 * Show a Seed Confirm Page link under banks table
 */
add_action('seed_confirm_after_banks', 'seed_confirm_link_button');
function seed_confirm_link_button() {
	$seed_confirm_link = seed_confirm_get_permalink();
	if($seed_confirm_link) {
		?>
			<div class="sedd-confirm-payment-page-link">
				<a href="<?php echo $seed_confirm_link; ?>" class="btn btn-primary"><?php _e('Confirm Payment', 'seed-confirm') ?></a>
			</div>
		<?php
	}
}

/**
 * Enqueue css and javascript for confirmation payment page.
 * CSS for feel good.
 * javascript for validate data.
 */
add_action( 'wp_enqueue_scripts', 'seed_confirm_scripts' );

function seed_confirm_scripts() {
	if(!is_admin()) {
		wp_enqueue_style( 'seed-confirm', plugin_dir_url( __FILE__ ) . 'seed-confirm.css' , array() );
		wp_enqueue_script( 'seed-confirm', plugin_dir_url( __FILE__ ) . 'seed-confirm.js' , array('jquery'), '2016-1', true );
	}
}

/**
 * Register seed_confirm shortcode.
 * This shortcode display form for  payment confirmation.
 * [seed_confirm]
 */
add_shortcode( 'seed_confirm', 'seed_confirm_shortcode' );

function seed_confirm_shortcode( $atts ) {
	global $post;

	$seed_confirm_name = '';
	$seed_confirm_contact = '';
	$seed_confirm_order = '';
	$seed_confirm_account_number = '';
	$seed_confirm_amount = '';
	$seed_confirm_date = '';
	$seed_confirm_hour = '';
	$seed_confirm_minute = '';

	$current_user = wp_get_current_user();

	$user_id = $current_user->ID;

	$seed_confirm_name = get_user_meta( $user_id, 'billing_first_name', true ) . ' ' . get_user_meta( $user_id, 'billing_last_name', true );
	$seed_confirm_contact = get_user_meta( $user_id, 'billing_phone', true );

	$seed_confirm_date = current_time('d-m-Y');
	$seed_confirm_hour = current_time('H');
	$seed_confirm_minute = current_time('i');

	ob_start();
	?>
	<?php do_action('seed_confirm_before_confirm_content'); ?>
	<?php if( $_SERVER['REQUEST_METHOD'] === 'POST' ): ?>
	<div class="seed-confirm-message" style="background-color: <?php echo get_option( 'seed_confirm_notification_bg_color' ); ?>">
		<?php do_action('seed_confirm_before_message'); ?>
		<?php echo get_option( 'seed_confirm_notification_text' ); ?>
	</div>
	<?php endif; ?>
	<?php do_action('seed_confirm_before_form'); ?>
	<form method="post" id="seed-confirm-form" class="seed-confirm-form _heading" enctype="multipart/form-data">
		<?php wp_nonce_field( 'seed-confirm-form-'.$post->ID ) ?>
        <?php $seed_confirm_required = json_decode( get_option( 'seed_confirm_required' ), true );  ?>
		<div class="form-group row">
			<div class="col-sm-6">
				<label for="seed-confirm-name"><?php esc_html_e( 'Name', 'seed-confirm' ); ?></label>
				<input class="form-control <?php if( isset( $seed_confirm_required['seed_confirm_name'] )){ echo 'required';} ?>" type="text" id="seed-confirm-name" name="seed-confirm-name" value="<?php echo esc_html( $seed_confirm_name ); ?>" />
			</div>	
			<div class="col-sm-6">
				<label for="seed-confirm-contact"><?php esc_html_e( 'Contact', 'seed-confirm' ); ?></label>
				<input class="form-control <?php if( isset( $seed_confirm_required['seed_confirm_contact'] )){ echo 'required';} ?>" type="text" id="seed-confirm-contact" name="seed-confirm-contact" value="<?php echo esc_html( $seed_confirm_contact ); ?>" />
			</div>
		</div>
		<div class="form-group row">
			<div class="col-sm-6">
				<label for="seed-confirm-order"><?php esc_html_e( 'Order', 'seed-confirm' ); ?></label>

				<?php
				$user_id = $current_user->ID;

				$customer_orders = array();

				if( $user_id !== 0 && is_woocommerce_activated()) {
					$customer_orders = get_posts( array(
						'numberposts' => -1,
						'meta_key'    => '_customer_user',
						'meta_value'  => $user_id,
						'post_type'   => wc_get_order_types(),
						'post_status' => array( 'wc-on-hold', 'wc-processing' ),
						)
					);
				}

				if( count( $customer_orders ) > 0 ) {
					?>

					<select id="seed-confirm-order" name="seed-confirm-order" class="form-control <?php if( isset( $seed_confirm_required['seed_confirm_order'] )){ echo 'required';} ?>">
						<?php
						foreach( $customer_orders as $_order ):
							$order = new WC_Order( $_order->ID );
						?>
						<option value="<?php echo $_order->ID ?>"<?php if($seed_confirm_order == $_order->ID): ?> selected="selected"<?php endif ?>>
							<?php 
								if( $_order->post_status == 'wc-processing' ) {esc_html_e( '[Noted] ', 'seed-confirm' ); };
								echo __('No. ', 'seed-confirm') . $_order->ID .__(' - Amount: ', 'seed-confirm') . $order->get_total() . ' '. get_woocommerce_currency_symbol(); 
							?>
						</option>
						<?php
							endforeach;
						?>
					</select>
					<?php } else { ?>
						<input type="text" class="form-control <?php if( isset( $seed_confirm_required['seed_confirm_order'] )){ echo 'required';} ?>" id="seed-confirm-order" name="seed-confirm-order" value="<?php echo esc_html( $seed_confirm_order ); ?>" />
						<?php } ?>
					</div>
					<div class="col-sm-6">
						<label for="seed-confirm-amount"><?php esc_html_e( 'Amount', 'seed-confirm' ); ?></label>
						<input type="text" class="form-control <?php if( isset( $seed_confirm_required['seed_confirm_amount'] )){ echo 'required';} ?>" name="seed-confirm-amount" id="seed-confirm-amount" value="<?php echo esc_html( $seed_confirm_amount ); ?>" />
					</div>
				</div>
				<?php
				$account_details = get_option('woocommerce_bacs_accounts', true);

				$thai_accounts = $account_details;
			?>

			<div class="form-group seed-confirm-bank-info">
				<label><?php esc_html_e( 'Bank Account', 'seed-confirm' ); ?></label>
				<?php if( count( $thai_accounts ) > 0 ): ?>
				<?php foreach( $thai_accounts as $_account ): ?>

				<div class="form-check">
					<label class="form-check-label">
						<input class="form-check-input <?php if( isset( $seed_confirm_required['seed_confirm_account_number'] )){ echo 'required';} ?>" type="radio" id="bank-<?php echo $_account['account_number']; ?>" name="seed-confirm-account-number" value="<?php echo $_account['account_number']; ?>"<?php if( $seed_confirm_account_number == $_account['account_number'] ): ?> selected="selected"<?php endif; ?>>
						<span class="seed-confirm-bank-info-bank"><?php echo $_account['bank_name']; ?></span>
						<span class="seed-confirm-bank-info-account-number"><?php echo $_account['account_number']; ?></span>
						<span class="seed-confirm-bank-info-account-name"><?php echo $_account['account_name']; ?></span>
					</label>
				</div>

			<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<?php wp_enqueue_script('jquery-ui-datepicker'); ?>
		<div class="form-group row">
			<div class="col-sm-6 seed-confirm-date">
				<label for="seed-confirm-date"><?php esc_html_e( 'Transfer Date', 'seed-confirm' ); ?></label>
				<input type="text" id="seed-confirm-date" name="seed-confirm-date" class="form-control <?php if( isset( $seed_confirm_required['seed_confirm_date'] )){ echo 'required';} ?>" value="<?php echo $seed_confirm_date ?>"/>
			</div>
			<div class="col-sm-6 seed-confirm-time">
				<label><?php esc_html_e( 'Time', 'seed-confirm' ); ?></label>
				<div class="form-inline">
					
					<select name="seed-confirm-hour" id="seed-confirm-hour" class="form-control">
						<option value="00"<?php if( $seed_confirm_hour == '00'): ?> selected='selected'<?php endif; ?>>00</option>
						<option value="01"<?php if( $seed_confirm_hour == '01'): ?> selected='selected'<?php endif; ?>>01</option>
						<option value="02"<?php if( $seed_confirm_hour == '02'): ?> selected='selected'<?php endif; ?>>02</option>
						<option value="03"<?php if( $seed_confirm_hour == '03'): ?> selected='selected'<?php endif; ?>>03</option>
						<option value="04"<?php if( $seed_confirm_hour == '04'): ?> selected='selected'<?php endif; ?>>04</option>
						<option value="05"<?php if( $seed_confirm_hour == '05'): ?> selected='selected'<?php endif; ?>>05</option>
						<option value="06"<?php if( $seed_confirm_hour == '06'): ?> selected='selected'<?php endif; ?>>06</option>
						<option value="07"<?php if( $seed_confirm_hour == '07'): ?> selected='selected'<?php endif; ?>>07</option>
						<option value="08"<?php if( $seed_confirm_hour == '08'): ?> selected='selected'<?php endif; ?>>08</option>
						<option value="09"<?php if( $seed_confirm_hour == '09'): ?> selected='selected'<?php endif; ?>>09</option>
						<option value="10"<?php if( $seed_confirm_hour == '10'): ?> selected='selected'<?php endif; ?>>10</option>
						<option value="11"<?php if( $seed_confirm_hour == '11'): ?> selected='selected'<?php endif; ?>>11</option>
						<option value="12"<?php if( $seed_confirm_hour == '12'): ?> selected='selected'<?php endif; ?>>12</option>
						<option value="13"<?php if( $seed_confirm_hour == '13'): ?> selected='selected'<?php endif; ?>>13</option>
						<option value="14"<?php if( $seed_confirm_hour == '14'): ?> selected='selected'<?php endif; ?>>14</option>
						<option value="15"<?php if( $seed_confirm_hour == '15'): ?> selected='selected'<?php endif; ?>>15</option>
						<option value="16"<?php if( $seed_confirm_hour == '16'): ?> selected='selected'<?php endif; ?>>16</option>
						<option value="17"<?php if( $seed_confirm_hour == '17'): ?> selected='selected'<?php endif; ?>>17</option>
						<option value="18"<?php if( $seed_confirm_hour == '18'): ?> selected='selected'<?php endif; ?>>18</option>
						<option value="19"<?php if( $seed_confirm_hour == '19'): ?> selected='selected'<?php endif; ?>>19</option>
						<option value="20"<?php if( $seed_confirm_hour == '20'): ?> selected='selected'<?php endif; ?>>20</option>
						<option value="21"<?php if( $seed_confirm_hour == '21'): ?> selected='selected'<?php endif; ?>>21</option>
						<option value="22"<?php if( $seed_confirm_hour == '22'): ?> selected='selected'<?php endif; ?>>22</option>
						<option value="23"<?php if( $seed_confirm_hour == '23'): ?> selected='selected'<?php endif; ?>>23</option>
					</select>
					
					
					<select name="seed-confirm-minute" id="seed-confirm-minute" class="form-control">
						<option value="00"<?php if( $seed_confirm_minute == '00'): ?> selected='selected'<?php endif; ?>>00</option>
						<option value="01"<?php if( $seed_confirm_minute == '01'): ?> selected='selected'<?php endif; ?>>01</option>
						<option value="02"<?php if( $seed_confirm_minute == '02'): ?> selected='selected'<?php endif; ?>>02</option>
						<option value="03"<?php if( $seed_confirm_minute == '03'): ?> selected='selected'<?php endif; ?>>03</option>
						<option value="04"<?php if( $seed_confirm_minute == '04'): ?> selected='selected'<?php endif; ?>>04</option>
						<option value="05"<?php if( $seed_confirm_minute == '05'): ?> selected='selected'<?php endif; ?>>05</option>
						<option value="06"<?php if( $seed_confirm_minute == '06'): ?> selected='selected'<?php endif; ?>>06</option>
						<option value="07"<?php if( $seed_confirm_minute == '07'): ?> selected='selected'<?php endif; ?>>07</option>
						<option value="08"<?php if( $seed_confirm_minute == '08'): ?> selected='selected'<?php endif; ?>>08</option>
						<option value="09"<?php if( $seed_confirm_minute == '09'): ?> selected='selected'<?php endif; ?>>09</option>
						<option value="10"<?php if( $seed_confirm_minute == '10'): ?> selected='selected'<?php endif; ?>>10</option>
						<option value="11"<?php if( $seed_confirm_minute == '11'): ?> selected='selected'<?php endif; ?>>11</option>
						<option value="12"<?php if( $seed_confirm_minute == '12'): ?> selected='selected'<?php endif; ?>>12</option>
						<option value="13"<?php if( $seed_confirm_minute == '13'): ?> selected='selected'<?php endif; ?>>13</option>
						<option value="14"<?php if( $seed_confirm_minute == '14'): ?> selected='selected'<?php endif; ?>>14</option>
						<option value="15"<?php if( $seed_confirm_minute == '15'): ?> selected='selected'<?php endif; ?>>15</option>
						<option value="16"<?php if( $seed_confirm_minute == '16'): ?> selected='selected'<?php endif; ?>>16</option>
						<option value="17"<?php if( $seed_confirm_minute == '17'): ?> selected='selected'<?php endif; ?>>17</option>
						<option value="18"<?php if( $seed_confirm_minute == '18'): ?> selected='selected'<?php endif; ?>>18</option>
						<option value="19"<?php if( $seed_confirm_minute == '19'): ?> selected='selected'<?php endif; ?>>19</option>
						<option value="20"<?php if( $seed_confirm_minute == '20'): ?> selected='selected'<?php endif; ?>>20</option>
						<option value="21"<?php if( $seed_confirm_minute == '21'): ?> selected='selected'<?php endif; ?>>21</option>
						<option value="22"<?php if( $seed_confirm_minute == '22'): ?> selected='selected'<?php endif; ?>>22</option>
						<option value="23"<?php if( $seed_confirm_minute == '23'): ?> selected='selected'<?php endif; ?>>23</option>
						<option value="24"<?php if( $seed_confirm_minute == '24'): ?> selected='selected'<?php endif; ?>>24</option>
						<option value="25"<?php if( $seed_confirm_minute == '25'): ?> selected='selected'<?php endif; ?>>25</option>
						<option value="26"<?php if( $seed_confirm_minute == '26'): ?> selected='selected'<?php endif; ?>>26</option>
						<option value="27"<?php if( $seed_confirm_minute == '27'): ?> selected='selected'<?php endif; ?>>27</option>
						<option value="28"<?php if( $seed_confirm_minute == '28'): ?> selected='selected'<?php endif; ?>>28</option>
						<option value="29"<?php if( $seed_confirm_minute == '29'): ?> selected='selected'<?php endif; ?>>29</option>
						<option value="30"<?php if( $seed_confirm_minute == '30'): ?> selected='selected'<?php endif; ?>>30</option>
						<option value="31"<?php if( $seed_confirm_minute == '31'): ?> selected='selected'<?php endif; ?>>31</option>
						<option value="32"<?php if( $seed_confirm_minute == '32'): ?> selected='selected'<?php endif; ?>>32</option>
						<option value="33"<?php if( $seed_confirm_minute == '33'): ?> selected='selected'<?php endif; ?>>33</option>
						<option value="34"<?php if( $seed_confirm_minute == '34'): ?> selected='selected'<?php endif; ?>>34</option>
						<option value="35"<?php if( $seed_confirm_minute == '35'): ?> selected='selected'<?php endif; ?>>35</option>
						<option value="36"<?php if( $seed_confirm_minute == '36'): ?> selected='selected'<?php endif; ?>>36</option>
						<option value="37"<?php if( $seed_confirm_minute == '37'): ?> selected='selected'<?php endif; ?>>37</option>
						<option value="38"<?php if( $seed_confirm_minute == '38'): ?> selected='selected'<?php endif; ?>>38</option>
						<option value="39"<?php if( $seed_confirm_minute == '39'): ?> selected='selected'<?php endif; ?>>39</option>
						<option value="40"<?php if( $seed_confirm_minute == '40'): ?> selected='selected'<?php endif; ?>>40</option>
						<option value="41"<?php if( $seed_confirm_minute == '41'): ?> selected='selected'<?php endif; ?>>41</option>
						<option value="42"<?php if( $seed_confirm_minute == '42'): ?> selected='selected'<?php endif; ?>>42</option>
						<option value="43"<?php if( $seed_confirm_minute == '43'): ?> selected='selected'<?php endif; ?>>43</option>
						<option value="44"<?php if( $seed_confirm_minute == '44'): ?> selected='selected'<?php endif; ?>>44</option>
						<option value="45"<?php if( $seed_confirm_minute == '45'): ?> selected='selected'<?php endif; ?>>45</option>
						<option value="46"<?php if( $seed_confirm_minute == '46'): ?> selected='selected'<?php endif; ?>>46</option>
						<option value="47"<?php if( $seed_confirm_minute == '47'): ?> selected='selected'<?php endif; ?>>47</option>
						<option value="48"<?php if( $seed_confirm_minute == '48'): ?> selected='selected'<?php endif; ?>>48</option>
						<option value="49"<?php if( $seed_confirm_minute == '49'): ?> selected='selected'<?php endif; ?>>49</option>
						<option value="50"<?php if( $seed_confirm_minute == '50'): ?> selected='selected'<?php endif; ?>>50</option>
						<option value="51"<?php if( $seed_confirm_minute == '51'): ?> selected='selected'<?php endif; ?>>51</option>
						<option value="52"<?php if( $seed_confirm_minute == '52'): ?> selected='selected'<?php endif; ?>>52</option>
						<option value="53"<?php if( $seed_confirm_minute == '53'): ?> selected='selected'<?php endif; ?>>53</option>
						<option value="54"<?php if( $seed_confirm_minute == '54'): ?> selected='selected'<?php endif; ?>>54</option>
						<option value="55"<?php if( $seed_confirm_minute == '55'): ?> selected='selected'<?php endif; ?>>55</option>
						<option value="56"<?php if( $seed_confirm_minute == '56'): ?> selected='selected'<?php endif; ?>>56</option>
						<option value="57"<?php if( $seed_confirm_minute == '57'): ?> selected='selected'<?php endif; ?>>57</option>
						<option value="58"<?php if( $seed_confirm_minute == '58'): ?> selected='selected'<?php endif; ?>>58</option>
						<option value="59"<?php if( $seed_confirm_minute == '59'): ?> selected='selected'<?php endif; ?>>59</option>
					</select>
					
					<script type="text/javascript">
						jQuery(document).ready(function() {
							jQuery('#seed-confirm-date').datepicker({
								dateFormat : 'dd-mm-yy',
								maxDate: new Date
							});
						});

					</script>
				</div>
			</div>
		</div>
		<div class="form-group">
			<div class="seed-confirm-slip">
				<label><?php esc_html_e( 'Payment Slip', 'seed-confirm' ); ?></label>
				<input type="file" id="seed-confirm-slip" name="seed-confirm-slip" class="<?php if( isset( $seed_confirm_required['seed_confirm_slip'] )){ echo 'required';} ?>" />
			</div>
		</div>
		<input type="hidden" name="postid" value="<?php echo $post->ID ?>" />
		<input type="submit" class="btn btn-primary" value="<?php esc_html_e( 'Submit Payment Detail', 'seed-confirm' ); ?>" />
	</form>
	<?php do_action('seed_confirm_after_form'); ?>
<?php
	return ob_get_clean();
}

/**
 * Grab POST from confirmation payment form and keep it in database.
 */
add_action( 'init', 'seed_confirm_init' , 10 );

function seed_confirm_init() {
	if ( $_SERVER['REQUEST_METHOD'] === 'POST' ):
		if( array_key_exists( 'postid' , $_POST )
			&& array_key_exists( '_wpnonce' , $_POST )
			&& wp_verify_nonce( $_POST['_wpnonce'], 'seed-confirm-form-'.$_POST['postid'] ) ):

			$name = $_POST[ 'seed-confirm-name' ];
			$contact = $_POST[ 'seed-confirm-contact' ];
			$order_id = $_POST[ 'seed-confirm-order' ];
			$account_number = array_key_exists( 'seed-confirm-account-number', $_POST) ? $_POST[ 'seed-confirm-account-number' ] : '';
			$amount = $_POST['seed-confirm-amount' ];
			$date = $_POST[ 'seed-confirm-date' ];
			$hour = $_POST[ 'seed-confirm-hour' ];
			$minute = $_POST[ 'seed-confirm-minute' ];
			$the_content = '<div class="seed_confirm_log">';

			if( trim( $name ) != '' ) {
				$the_content .= '<strong>' . esc_html__( 'Name', 'seed-confirm' ) . ': </strong>';
				$the_content .= '<span>'. $name . '</span><br>';
			}

			if( trim( $contact ) != '' ) {
				$the_content .= '<strong>' . esc_html__( 'Contact', 'seed-confirm' ) . ': </strong>';
				$the_content .= '<span>'. $contact . '</span><br>';
			}

			if( trim( $order_id ) != '' ) {
				$the_content .= '<strong>' . esc_html__( 'Order no', 'seed-confirm' ) . ': </strong>';
				$the_content .= '<span><a href="'. get_admin_url() .'post.php?post=' . $order_id . '&action=edit" target="_blank">'. $order_id . '</a></span><br>';
			}

		if( trim( $account_number ) != '' ) {
			$the_content .= '<strong>' . esc_html__( 'Account no', 'seed-confirm' ) . ': </strong>';
			$the_content .= '<span>'. $account_number . '</span><br>';
		}

		if( trim( $amount )  != '' ) {
			$the_content .= '<strong>' . esc_html__( 'Amount', 'seed-confirm' ) . ': </strong>';
			$the_content .= '<span>'. $amount . '</span><br>';
		}

		if( trim( $date )  != '' ) {
			$the_content .= '<strong>' . esc_html__( 'Date', 'seed-confirm' ) . ': </strong>';
			$the_content .= '<span>'. $date;

			if( trim( $hour )  != '' ) {
				$the_content .= ' ' . $hour;

				if( trim( $minute )  != '' ) {
					$the_content .= ':' . $minute;
				} else {
					$the_content .= ':00';
				}
			}
			$the_content .= '</span><br>';
		}

		$the_content .= '</div>';

		$symbol = get_option('seed_confirm_symbol', (function_exists('get_woocommerce_currency_symbol')?get_woocommerce_currency_symbol():'฿'));

		$transfer_notification_id = wp_insert_post( array	(
			'post_title' => __('Order no. ', 'seed-confirm') .  $order_id . __(' by ', 'seed-confirm') . $name . ' ('. $amount .' '. $symbol . ')',
			'post_content' => $the_content,
			'post_type' => 'seed_confirm_log',
			'post_status' => 'publish'
			)
		);

		require_once( ABSPATH . 'wp-admin/includes/file.php' );

		// Random slip filename. 
        $overrides = array(
            'test_form' => false,
            'unique_filename_callback' => 'seed_unique_filename'
        );
		
		$slip_image = wp_handle_upload( $_FILES['seed-confirm-slip'], $overrides );

		// Append slip image to post content. 

		if( $slip_image && !isset( $slip_image['error'] ) ){

			$the_content .= '<img class="seed-confirm-img" src="'.$slip_image['url'].'" />';

			$attrs = array(
				'ID'           => $transfer_notification_id,
				'post_content' => $the_content,
			);

			wp_update_post( $attrs );
            update_post_meta( $transfer_notification_id, 'seed-confirm-image', $slip_image['url'] );
		}

        // Send email to admin.
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=utf8' . "\r\n";
		$headers .= 'From: Seed Confirm <'.get_option( 'admin_email' ).'>' . "\r\n" .
		$headers .= 'X-Mailer: PHP/' . phpversion();

		$mailsent = wp_mail( get_option( 'admin_email' ) , 'Bank transfer notification', $the_content, $headers );

		if ( ! add_post_meta( $transfer_notification_id, 'seed-confirm-name', $_POST['seed-confirm-name'] , true ) )
			update_post_meta( $transfer_notification_id, 'seed-confirm-name', $_POST['seed-confirm-name'] );

		if ( ! add_post_meta( $transfer_notification_id, 'seed-confirm-contact', $_POST['seed-confirm-contact'] , true ) )
			update_post_meta( $transfer_notification_id, 'seed-confirm-contact', $_POST['seed-confirm-contact'] );

		if ( ! add_post_meta( $transfer_notification_id, 'seed-confirm-order', $_POST['seed-confirm-order'] , true ) )
			update_post_meta( $transfer_notification_id, 'seed-confirm-order', $_POST['seed-confirm-order'] );

		if( array_key_exists( 'seed-confirm-account-number', $_POST) ) {
			if ( ! add_post_meta( $transfer_notification_id, 'seed-confirm-account-number', $_POST['seed-confirm-account-number'] , true ) )
				update_post_meta( $transfer_notification_id, 'seed-confirm-account-number', $_POST['seed-confirm-account-number'] );
		}

		if ( ! add_post_meta( $transfer_notification_id, 'seed-confirm-amount', $_POST['seed-confirm-amount'] , true ) )
			update_post_meta( $transfer_notification_id, 'seed-confirm-amount', $_POST['seed-confirm-amount'] );

		if ( ! add_post_meta( $transfer_notification_id, 'seed-confirm-date', $_POST['seed-confirm-date'] , true ) )
			update_post_meta( $transfer_notification_id, 'seed-confirm-date', $_POST['seed-confirm-date'] );

		if ( ! add_post_meta( $transfer_notification_id, 'seed-confirm-hour', $_POST['seed-confirm-hour'] , true ) )
			update_post_meta( $transfer_notification_id, 'seed-confirm-hour', $_POST['seed-confirm-hour'] );

		if ( ! add_post_meta( $transfer_notification_id, 'seed-confirm-minute', $_POST['seed-confirm-minute'] , true ) )
			update_post_meta( $transfer_notification_id, 'seed-confirm-minute', $_POST['seed-confirm-minute'] );

		// Automatic update woo order status if woocommerce is installed.
		if(is_woocommerce_activated()){
			$order = new WC_Order( $order_id );

			if( ( $order !== null ) ) {

				$order->update_status('processing', 'order_note');
			}
		}

		endif;
		endif;
	}

/**
 * Use for generate unique file name.
 * Difficult to predict.
 * Only slip image that upload through seed-confirm.
 */
function seed_unique_filename( $dir, $name, $ext ) {
	return 'slip-'.md5( $dir.$name.time() ).$ext;
}

/**
 * Register seed_confirm_log PostType.
 * Store confirmation payment.
 */
add_action('init', 'seed_confirm_register_transfer_notifications_logs');

function seed_confirm_register_transfer_notifications_logs() {
	register_post_type('seed_confirm_log', array(
		'labels'	=> array(
			'name'		=> __('Confirm Logs', 'seed-confirm'),
			'singular_name' => __('Log'),
			'menu_name'	=> __('Confirm Logs','seed-confirm')
			),
		'capabilities' => array(
			'create_posts' => 'do_not_allow',
			),
		'map_meta_cap'	=> true,
		'supports' => array( 'title', 'editor', 'custom-fields', 'thumbnail' ),
		'has_archive'	=> false,
		'menu_icon'   => 'dashicons-paperclip',
		'public'	=> true,
		'publicly_queryable'	=> false
		)
	);
}