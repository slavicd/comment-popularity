<?php

/**
 * Class HMN_Comment_Popularity_Admin
 */
class HMN_Comment_Popularity_Admin {

	private static $instance;

	private function __construct() {

		add_action( 'show_user_profile', array( $this, 'render_user_karma_field' ) );
		add_action( 'edit_user_profile', array( $this, 'render_user_karma_field' ) );

		add_action( 'personal_options_update', array( $this, 'save_user_meta' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_user_meta' ) );

		add_filter( 'manage_edit-comments_columns', array( $this, 'add_comment_columns' ) );
		add_filter( 'manage_comments_custom_column', array( $this, 'populate_comment_column' ), 10, 2 );

		add_filter( 'manage_edit-comments_sortable_columns', array( $this, 'make_weight_column_sortable' ) );

		add_action( 'admin_init', array( $this, 'register_plugin_settings' ) );

	}

	public static function get_instance() {

		if ( ! self::$instance instanceof HMN_Comment_Popularity ) {
			self::$instance = new HMN_Comment_Popularity_Admin();

		}

		return self::$instance;

	}

	/**
	 * Adds a setting field on the Discussion admin page.
	 */
	public function register_plugin_settings() {

		register_setting( 'discussion', 'comment_popularity_prefs', array( $this, 'validate_settings' ) );

		add_settings_field( 'hmn_cp_expert_karma_field', __( 'Default karma value for expert users', 'comment-popularity' ), array( $this, 'render_expert_karma_input' ), 'discussion', 'default', array( 'label_for' => 'hmn_cp_expert_karma_field' ) );
	}

	/**
	 * Callback to render the option HTML input on the settings page.
	 */
	public function render_expert_karma_input() {

		$prefs = get_option( 'comment_popularity_prefs', array( 'default_expert_karma' => 0 ) );

		$default_expert_karma = array_key_exists( 'default_expert_karma', $prefs ) ? $prefs['default_expert_karma'] : 0;

		echo '<input class="small-text" id="default_expert_karma" name="comment_popularity_prefs[default_expert_karma]" placeholder="' . esc_attr_e( 'Enter value', 'comment-popularity' ) . '" type="number" min="0" max="" step="1" value="' . esc_attr( $default_expert_karma ) . '" />';

	}

	/**
	 * Sanitize the user input.
	 *
	 * @param $input
	 *
	 * @return mixed
	 */
	public function validate_settings( $input ) {

		$valid['default_expert_karma'] = absint( $input['default_expert_karma'] );

		return $valid;
	}


	/**
	 * Renders the HTML form element for setting the user karma value.
	 *
	 * @param $user
	 */
	public function render_user_karma_field( $user ) {

		if ( ! current_user_can( 'manage_user_karma_settings' ) ) {
			return;
		}

		$prefs = get_option( 'comment_popularity_prefs', array( 'default_expert_karma' => 0 ) );

		$default_karma = $prefs['default_expert_karma'];

		$current_karma = get_the_author_meta( 'hmn_user_karma', $user->ID );

		$user_karma = ( empty( $current_karma ) ) ? $default_karma : $current_karma;

		$user_expert_status = get_the_author_meta( 'hmn_user_expert_status', $user->ID );

		?>

		<h3><?php esc_html_e( 'Comment popularity settings', 'comment-popularity' ); ?></h3>

		<table class="form-table">

			<tr>

				<th>

					<label for="hmn_user_expert_status"><?php esc_html_e( 'Expert Commenter', 'comment-popularity' ); ?></label>

				</th>

				<td>

					<input id="hmn_user_expert_status" name="hmn_user_expert_status" type="hidden" value="0" />
					<input id="hmn_user_expert_status" name="hmn_user_expert_status" type="checkbox" value="1" <?php checked( $user_expert_status ); ?> />

				</td>

			</tr>

			<tr>

				<th>

					<label for="hmn_user_karma"><?php esc_html_e( 'Karma', 'comment-popularity' ); ?></label>

				</th>

				<td>

					<input name="hmn_user_karma" type="number" step="1" min="0" id="hmn_user_karma" value="<?php echo esc_attr( $user_karma ); ?>" class="small-text">

				</td>

			</tr>

		</table>

	<?php
	}

	/**
	 * Add comment karma column to the admin view.
	 *
	 * @param $columns
	 *
	 * @return array
	 */
	public function add_comment_columns( $columns )
	{
		return array_merge( $columns, array(
			'comment_karma' => __( 'Weight', 'comment-popularity' ),
		) );
	}

	/**
	 * Populate the custom comment list table view with karma.
	 *
	 * @param $column
	 * @param $comment_ID
	 */
	public function populate_comment_column( $column, $comment_ID )
	{
		$comment = get_comment( $comment_ID );

		echo intval( $comment->comment_karma );
	}

	/**
	 * Add ability to sort by comment karma on the edit comments admin view.
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function make_weight_column_sortable( $columns ) {

		$columns['comment_karma'] = 'comment_karma';

		return $columns;

	}
	/**
	 * Saves the custom user meta data.
	 *
	 * @param $user_id
	 */
	public function save_user_meta( $user_id ) {

		if ( ! current_user_can( 'manage_user_karma_settings' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		$user_karma = absint( $_POST['hmn_user_karma'] );

		$user_expert_status = (bool)$_POST['hmn_user_expert_status'];

		update_user_meta( $user_id, 'hmn_user_karma', $user_karma );

		update_user_meta( $user_id, 'hmn_user_expert_status', $user_expert_status );

	}


}