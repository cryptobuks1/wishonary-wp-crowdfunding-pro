<?php
/**
 * BuddyBoss Zoom Meeting
 *
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database interaction class for the BuddyBoss zoom meeting.
 * Instance methods are available for creating/editing an meeting,
 * static methods for querying meeting.
 *
 * @since 1.0.0
 */
class BP_Zoom_Meeting {

	/** Properties ************************************************************/

	/**
	 * ID of the media item.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	var $id;

	/**
	 * Group ID of the meeting item.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	var $group_id;

	/**
	 * Activity ID of the meeting item.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	var $activity_id;

	/**
	 * Site User ID of the meeting item.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	var $user_id;

	/**
	 * Title of the meeting item.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	var $title;

	/**
	 * Description of the meeting item.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	var $description;

	/**
	 * Host ID of the meeting item.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	var $host_id;

	/**
	 * Start date of the meeting item.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	var $start_date;

	/**
	 * timezone of the meeting item.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	var $timezone;

	/**
	 * password of the meeting item.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	var $password;

	/**
	 * duration of the meeting item.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	var $duration;

	/**
	 * join before host of the meeting item.
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	var $join_before_host;

	/**
	 * host video of the media item.
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	var $host_video;

	/**
	 * participants video of the media item.
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	var $participants_video;

	/**
	 * mute participants of the media item.
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	var $mute_participants;

	/**
	 * Meeting authetication.
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	var $meeting_authentication;

	/**
	 * Waiting room.
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	var $waiting_room;

	/**
	 * Recurring meeting.
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	var $recurring;

	/**
	 * Auto recording of the media item.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	var $auto_recording;

	/**
	 * Alternative host ids of the media item.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	var $alternative_host_ids;

	/**
	 * zoom meeting id of the media item.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	var $meeting_id;

	/**
	 * zoom meeting start date in utc of the media item.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	var $start_date_utc;

	/**
	 * Whether the meeting should be hidden in sitewide.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	var $hide_sitewide = 0;

	/**
	 * Error holder.
	 *
	 * @since 1.0.0
	 *
	 * @var WP_Error
	 */
	public $errors;

	/**
	 * Whether the meeting is past or not.
	 *
	 * @since 1.0.0
	 *
	 * @var boolean
	 */
	public $is_past = false;

	/**
	 * Error type to return. Either 'bool' or 'wp_error'.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $error_type = 'bool';

	/**
	 * Constructor method.
	 *
	 * @since 1.0.0
	 *
	 * @param int|bool $id Optional. The ID of a specific meeting item.
	 */
	function __construct( $id = false ) {
		// Instantiate errors object.
		$this->errors = new WP_Error();

		if ( ! empty( $id ) ) {
			$this->id = (int) $id;
			$this->populate();
		}
	}

	/**
	 * Populate the object with data about the specific meeting item.
	 *
	 * @since 1.0.0
	 */
	public function populate() {

		global $wpdb;

		$row = wp_cache_get( $this->id, 'bp_meeting' );

		if ( false === $row ) {
			$bp  = buddypress();
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->table_prefix}bp_zoom_meetings WHERE id = %d", $this->id ) );

			wp_cache_set( $this->id, $row, 'bp_meeting' );
		}

		if ( empty( $row ) ) {
			$this->id = 0;
			return;
		}

		if ( strtotime( wp_date( 'Y-m-d H:i:s', time(), new DateTimeZone( 'UTC' ) ) ) > strtotime( $row->start_date_utc ) ) {
			$this->is_past = true;
		}

		$this->id                     = (int) $row->id;
		$this->group_id               = (int) $row->group_id;
		$this->activity_id            = (int) $row->activity_id;
		$this->user_id                = (int) $row->user_id;
		$this->title                  = $row->title;
		$this->description            = $row->description;
		$this->host_id                = $row->host_id;
		$this->start_date             = $row->start_date;
		$this->timezone               = $row->timezone;
		$this->password               = $row->password;
		$this->duration               = $row->duration;
		$this->join_before_host       = (bool) $row->join_before_host;
		$this->host_video             = (bool) $row->host_video;
		$this->participants_video     = (bool) $row->participants_video;
		$this->mute_participants      = (bool) $row->mute_participants;
		$this->meeting_authentication = (bool) $row->meeting_authentication;
		$this->waiting_room           = (bool) $row->waiting_room;
		$this->recurring              = (bool) $row->recurring;
		$this->auto_recording         = $row->auto_recording;
		$this->alternative_host_ids   = $row->alternative_host_ids;
		$this->meeting_id             = $row->meeting_id;
		$this->start_date_utc         = $row->start_date_utc;
		$this->hide_sitewide          = $row->hide_sitewide;
	}

	/**
	 * Save the meeting item to the database.
	 *
	 * @since 1.0.0
	 *
	 * @return WP_Error|bool True on success.
	 */
	public function save() {

		global $wpdb;

		$bp = buddypress();

		$this->id                     = apply_filters_ref_array(
			'bp_zoom_meeting_id_before_save',
			array(
				$this->id,
				&$this,
			)
		);
		$this->group_id               = apply_filters_ref_array(
			'bp_zoom_meeting_group_id_before_save',
			array(
				$this->group_id,
				&$this,
			)
		);
		$this->activity_id            = apply_filters_ref_array(
			'bp_zoom_meeting_activity_id_before_save',
			array(
				$this->activity_id,
				&$this,
			)
		);
		$this->user_id                   = apply_filters_ref_array(
			'bp_zoom_meeting_user_id_before_save',
			array(
				$this->user_id,
				&$this,
			)
		);
		$this->title                  = apply_filters_ref_array(
			'bp_zoom_meeting_title_before_save',
			array(
				$this->title,
				&$this,
			)
		);
		$this->description                  = apply_filters_ref_array(
			'bp_zoom_meeting_description_before_save',
			array(
				$this->description,
				&$this,
			)
		);
		$this->host_id                = apply_filters_ref_array(
			'bp_zoom_meeting_host_id_before_save',
			array(
				$this->host_id,
				&$this,
			)
		);
		$this->start_date             = apply_filters_ref_array(
			'bp_zoom_meeting_start_date_before_save',
			array(
				$this->start_date,
				&$this,
			)
		);
		$this->timezone               = apply_filters_ref_array(
			'bp_zoom_meeting_timezone_before_save',
			array(
				$this->timezone,
				&$this,
			)
		);
		$this->password               = apply_filters_ref_array(
			'bp_zoom_meeting_password_before_save',
			array(
				$this->password,
				&$this,
			)
		);
		$this->duration               = apply_filters_ref_array(
			'bp_zoom_meeting_duration_before_save',
			array(
				$this->duration,
				&$this,
			)
		);
		$this->join_before_host       = apply_filters_ref_array(
			'bp_zoom_meeting_join_before_host_before_save',
			array(
				$this->join_before_host,
				&$this,
			)
		);
		$this->host_video             = apply_filters_ref_array(
			'bp_zoom_meeting_host_video_before_save',
			array(
				$this->host_video,
				&$this,
			)
		);
		$this->participants_video     = apply_filters_ref_array(
			'bp_zoom_meeting_participants_video_before_save',
			array(
				$this->participants_video,
				&$this,
			)
		);
		$this->mute_participants      = apply_filters_ref_array(
			'bp_zoom_meeting_mute_participants_before_save',
			array(
				$this->mute_participants,
				&$this,
			)
		);
		$this->meeting_authentication = apply_filters_ref_array(
			'bp_zoom_meeting_meeting_authentication_before_save',
			array(
				$this->meeting_authentication,
				&$this,
			)
		);
		$this->waiting_room           = apply_filters_ref_array(
			'bp_zoom_meeting_waiting_room_before_save',
			array(
				$this->waiting_room,
				&$this,
			)
		);
		$this->recurring         = apply_filters_ref_array(
			'bp_zoom_meeting_recurring_before_save',
			array(
				$this->recurring,
				&$this,
			)
		);
		$this->auto_recording         = apply_filters_ref_array(
			'bp_zoom_meeting_auto_recording_before_save',
			array(
				$this->auto_recording,
				&$this,
			)
		);
		$this->alternative_host_ids   = apply_filters_ref_array(
			'bp_zoom_meeting_alternative_host_ids_before_save',
			array(
				$this->alternative_host_ids,
				&$this,
			)
		);
		$this->meeting_id        = apply_filters_ref_array(
			'bp_zoom_meeting_meeting_id_before_save',
			array(
				$this->meeting_id,
				&$this,
			)
		);
		$this->start_date_utc = apply_filters_ref_array(
			'bp_zoom_meeting_start_date_utc_before_save',
			array(
				$this->start_date_utc,
				&$this,
			)
		);
		$this->hide_sitewide = apply_filters_ref_array(
			'bp_zoom_meeting_hide_sitewidec_before_save',
			array(
				$this->hide_sitewide,
				&$this,
			)
		);

		$this->start_date = mysql_to_rfc3339( $this->start_date );
		$this->start_date_utc = mysql_to_rfc3339( $this->start_date_utc );

		/**
		 * Fires before the current meeting item gets saved.
		 *
		 * Please use this hook to filter the properties above. Each part will be passed in.
		 *
		 * @since 1.0.0
		 *
		 * @param BP_Zoom_Meeting $this Current instance of the meeting item being saved. Passed by reference.
		 */
		do_action_ref_array( 'bp_zoom_meeting_before_save', array( &$this ) );

		if ( 'wp_error' === $this->error_type && $this->errors->get_error_code() ) {
			return $this->errors;
		}

		if ( empty( $this->host_id ) ) {
			if ( 'bool' === $this->error_type ) {
				return false;
			} else {
				$this->errors->add( 'bp_zoom_meeting_missing_host_id' );

				return $this->errors;
			}
		}

		// If we have an existing ID, update the meeting item, otherwise insert it.
		if ( ! empty( $this->id ) ) {
			$q = $wpdb->prepare( "UPDATE {$bp->table_prefix}bp_zoom_meetings SET group_id = %d, activity_id = %d, user_id = %d, host_id = %s, title = %s, description = %s, start_date = %s, timezone = %s, password = %s, duration = %d, join_before_host = %d, host_video = %d, participants_video = %d, mute_participants = %d, waiting_room = %d, meeting_authentication = %d, recurring = %d, auto_recording = %s, alternative_host_ids = %s, meeting_id = %s, start_date_utc = %s, hide_sitewide = %d WHERE id = %d", $this->group_id, $this->activity_id, $this->user_id, $this->host_id, $this->title, $this->description, $this->start_date, $this->timezone, $this->password, $this->duration, $this->join_before_host, $this->host_video, $this->participants_video, $this->mute_participants, $this->waiting_room, $this->meeting_authentication, $this->recurring, $this->auto_recording, $this->alternative_host_ids, $this->meeting_id, $this->start_date_utc, $this->hide_sitewide, $this->id );
		} else {
			$q = $wpdb->prepare( "INSERT INTO {$bp->table_prefix}bp_zoom_meetings (group_id, activity_id, user_id, host_id, title, description, start_date, timezone, password, duration, join_before_host, host_video, participants_video, mute_participants, waiting_room, meeting_authentication, recurring, auto_recording, alternative_host_ids, meeting_id, start_date_utc, hide_sitewide ) VALUES (%d, %d, %d, %s, %s, %s, %s, %s, %s, %d, %d, %d, %d, %d, %d, %d, %d, %s, %s, %s, %s, %d )", $this->group_id, $this->activity_id, $this->user_id, $this->host_id, $this->title, $this->description, $this->start_date, $this->timezone, $this->password, $this->duration, $this->join_before_host, $this->host_video, $this->participants_video, $this->mute_participants, $this->waiting_room, $this->meeting_authentication, $this->recurring, $this->auto_recording, $this->alternative_host_ids, $this->meeting_id, $this->start_date_utc, $this->hide_sitewide );
		}

		if ( false === $wpdb->query( $q ) ) {
			return false;
		}

		// If this is a new meeting item, set the $id property.
		if ( empty( $this->id ) ) {
			$this->id = $wpdb->insert_id;
		}

		/**
		 * Fires after an meeting item has been saved to the database.
		 *
		 * @since 1.0.0
		 *
		 * @param BP_Zoom_Meeting $this Current instance of meeting item being saved. Passed by reference.
		 */
		do_action_ref_array( 'bp_zoom_meeting_after_save', array( &$this ) );

		return true;
	}

	/** Static Methods ***************************************************/

	/**
	 * Get meeting items, as specified by parameters.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args {
	 *     An array of arguments. All items are optional.
	 *     @type int          $page              Which page of results to fetch. Using page=1 without per_page will result
	 *                                           in no pagination. Default: 1.
	 *     @type int|bool     $per_page          Number of results per page. Default: 20.
	 *     @type int|bool     $max               Maximum number of results to return. Default: false (unlimited).
	 *     @type string       $fields            Media fields to return. Pass 'ids' to get only the media IDs.
	 *                                           'all' returns full media objects.
	 *     @type string       $sort              ASC or DESC. Default: 'DESC'.
	 *     @type string       $order_by          Column to order results by.
	 *     @type array        $exclude           Array of media IDs to exclude. Default: false.
	 *     @type string       $search_terms      Limit results by a search term. Default: false.
	 *     @type string|bool  $count_total       If true, an additional DB query is run to count the total media items
	 *                                           for the query. Default: false.
	 * }
	 * @return array The array returned has two keys:
	 *               - 'total' is the count of located medias
	 *               - 'meetings' is an array of the located medias
	 */
	public static function get( $args = array() ) {

		global $wpdb;

		$bp = buddypress();
		$r  = wp_parse_args(
			$args,
			array(
				'page'          => 1,               // The current page.
				'per_page'      => 20,              // Media items per page.
				'max'           => false,           // Max number of items to return.
				'fields'        => 'all',           // Fields to include.
				'sort'          => 'DESC',          // ASC or DESC.
				'order_by'      => 'start_date_utc',    // Column to order by.
				'exclude'       => false,           // Array of ids to exclude.
				'in'            => false,           // Array of ids to limit query by (IN).
				'meta_query'    => false,           // Filter by meetingmeta.
				'search_terms'  => false,           // Terms to search by.
				'count_total'   => false,           // Whether or not to use count_total.
				'group_id'      => false,           // filter results by group id.
				'meeting_id'    => false,           // filter results by zoom meeting id.
				'activity_id'   => false,           // filter results by zoom activity id.
				'user'          => false,           // filter results by site user id.
				'since'         => false,           // return items since date.
				'from'          => false,           // return items from date.
				'recorded'      => false,           // return items which have recordings.
				'recurring'     => false,           // return items which is recurring.
				'hide_sitewide' => false,               // return items which is not hidden.
			)
		);

		// Select conditions.
		$select_sql = 'SELECT DISTINCT m.id';

		$from_sql = " FROM {$bp->table_prefix}bp_zoom_meetings m";

		$join_sql = '';

		// Where conditions.
		$where_conditions = array();

		// Searching.
		if ( $r['search_terms'] ) {
			$search_terms_like              = '%' . bp_esc_like( $r['search_terms'] ) . '%';
			$where_conditions['search_sql'] = $wpdb->prepare( '( m.title LIKE %s OR m.meeting_id LIKE %s OR m.description LIKE %s )', $search_terms_like, $search_terms_like, $search_terms_like );
		}

		// Sorting.
		$sort = $r['sort'];
		if ( 'ASC' !== $sort && 'DESC' !== $sort ) {
			$sort = 'DESC';
		}

		if ( empty( $r['order_by'] ) ) {
			$r['order_by'] = 'start_date_utc';
		}

		$order_by = 'm.' . $r['order_by'];

		if ( ! empty( $r['group_id'] ) ) {
			$where_conditions['group'] = "m.group_id = {$r['group_id']}";
		}

		if ( ! empty( $r['meeting_id'] ) ) {
			$where_conditions['meeting'] = "m.meeting_id = {$r['meeting_id']}";
		}

		if ( ! empty( $r['activity_id'] ) ) {
			$where_conditions['activity'] = "m.activity_id = {$r['activity_id']}";
		}

		if ( ! empty( $r['user_id'] ) ) {
			$where_conditions['user'] = "m.user_id = {$r['user_id']}";
		}

		if ( ! empty( $r['host_id'] ) ) {
			$where_conditions['host'] = "m.host_id = {$r['host_id']}";
		}

		// Hidden sitewide.
		if ( isset( $r['hide_sitewide'] ) ) {
			$hide_sitewide                     = (int) $r['hide_sitewide'];
			$where_conditions['hide_sitewide'] = "m.hide_sitewide = {$hide_sitewide}";
		}

		// Exclude specified items.
		if ( ! empty( $r['exclude'] ) ) {
			$exclude                     = implode( ',', wp_parse_id_list( $r['exclude'] ) );
			$where_conditions['exclude'] = "m.id NOT IN ({$exclude})";
		}

		// The specific ids to which you want to limit the query.
		if ( ! empty( $r['in'] ) ) {
			$in                     = implode( ',', wp_parse_id_list( $r['in'] ) );
			$where_conditions['in'] = "m.id IN ({$in})";

			// we want to disable limit query when include media ids
			$r['page']     = false;
			$r['per_page'] = false;
		}

		if ( ! empty( $r['since'] ) && empty( $r['in'] ) && empty( $r['meeting_id'] ) ) {
			// Validate that this is a proper Y-m-d H:i:s date.
			// Trick: parse to UNIX date then translate back.
			$translated_date = wp_date( 'Y-m-d H:i:s', strtotime( $r['since'] ), new DateTimeZone( 'UTC' ) );
			if ( $translated_date === $r['since'] ) {
				$where_conditions['date_filter'] = "m.start_date_utc > '{$translated_date}'";
			}
		}

		if ( ! empty( $r['from'] ) ) {
			// Validate that this is a proper Y-m-d H:i:s date.
			// Trick: parse to UNIX date then translate back.
			$translated_date = wp_date( 'Y-m-d H:i:s', strtotime( $r['from'] ), new DateTimeZone( 'UTC' ) );
			if ( $translated_date === $r['from'] ) {
				$where_conditions['date_filter'] = "m.start_date_utc < '{$translated_date}'";
			}
		}

		if ( $r['recurring'] ) {
			$where_conditions['recurring'] = "m.recurring = 1";
		}

		if ( $r['recorded'] ) {
			$meta_recorded_query = array(
				'relation' => 'AND',
				array(
					'key'     => 'zoom_recording_count',
					'value'   => '0',
					'compare' => '>',
				)
			);
			if ( empty( $r['meta_query'] ) ) {
				$r['meta_query'] = $meta_recorded_query;
			} else {
				$r['meta_query'] = array( $r['meta_query'], $meta_recorded_query );
			}
		}

		// Process meta_query into SQL.
		$meta_query_sql = self::get_meta_query_sql( $r['meta_query'] );

		if ( ! empty( $meta_query_sql['join'] ) ) {
			$join_sql .= $meta_query_sql['join'];
		}

		if ( ! empty( $meta_query_sql['where'] ) ) {
			$where_conditions[] = $meta_query_sql['where'];
		}

		/**
		 * Filters the MySQL WHERE conditions for the Meeting items get method.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $where_conditions Current conditions for MySQL WHERE statement.
		 * @param array  $r                Parsed arguments passed into method.
		 * @param string $select_sql       Current SELECT MySQL statement at point of execution.
		 * @param string $from_sql         Current FROM MySQL statement at point of execution.
		 * @param string $join_sql         Current INNER JOIN MySQL statement at point of execution.
		 */
		$where_conditions = apply_filters( 'bp_zoom_meeting_get_where_conditions', $where_conditions, $r, $select_sql, $from_sql, $join_sql );

		if ( empty( $where_conditions ) ) {
			$where_conditions['2'] = '2';
		}

		// Join the where conditions together.
		if ( ! empty( $scope_query['sql'] ) ) {
			$where_sql = 'WHERE ( ' . join( ' AND ', $where_conditions ) . ' ) OR ( ' . $scope_query['sql'] . ' )';
		} else {
			$where_sql = 'WHERE ' . join( ' AND ', $where_conditions );
		}

		/**
		 * Filter the MySQL JOIN clause for the main meeting query.
		 *
		 * @since 1.0.0
		 *
		 * @param string $join_sql   JOIN clause.
		 * @param array  $r          Method parameters.
		 * @param string $select_sql Current SELECT MySQL statement.
		 * @param string $from_sql   Current FROM MySQL statement.
		 * @param string $where_sql  Current WHERE MySQL statement.
		 */
		$join_sql = apply_filters( 'bp_zoom_meeting_get_join_sql', $join_sql, $r, $select_sql, $from_sql, $where_sql );

		// Sanitize page and per_page parameters.
		$page     = absint( $r['page'] );
		$per_page = absint( $r['per_page'] );

		$retval = array(
			'meetings'       => null,
			'total'          => null,
			'has_more_items' => null,
		);

		// Query first for media IDs.
		$meeting_ids_sql = "{$select_sql} {$from_sql} {$join_sql} {$where_sql} ORDER BY {$order_by} {$sort}";

		if ( ! empty( $per_page ) && ! empty( $page ) ) {
			// We query for $per_page + 1 items in order to
			// populate the has_more_items flag.
			$meeting_ids_sql .= $wpdb->prepare( ' LIMIT %d, %d', absint( ( $page - 1 ) * $per_page ), $per_page + 1 );
		}

		/**
		 * Filters the paged meeting MySQL statement.
		 *
		 * @since 1.0.0
		 *
		 * @param string $meeting_ids_sql    MySQL statement used to query for Meeting IDs.
		 * @param array  $r                Array of arguments passed into method.
		 */
		$meeting_ids_sql = apply_filters( 'bp_zoom_meeting_paged_meetings_sql', $meeting_ids_sql, $r );

		$cache_group = 'bp_meeting';

		$cached = bp_core_get_incremented_cache( $meeting_ids_sql, $cache_group );
		if ( false === $cached ) {
			$meeting_ids = $wpdb->get_col( $meeting_ids_sql );
			bp_core_set_incremented_cache( $meeting_ids_sql, $cache_group, $meeting_ids );
		} else {
			$meeting_ids = $cached;
		}

		$retval['has_more_items'] = ! empty( $per_page ) && count( $meeting_ids ) > $per_page;

		// If we've fetched more than the $per_page value, we
		// can discard the extra now.
		if ( ! empty( $per_page ) && count( $meeting_ids ) === $per_page + 1 ) {
			array_pop( $meeting_ids );
		}

		if ( 'ids' === $r['fields'] ) {
			$meetings = array_map( 'intval', $meeting_ids );
		} else {
			$meetings = self::get_meeting_data( $meeting_ids );
		}

		$retval['meetings'] = $meetings;

		// If $max is set, only return up to the max results.
		if ( ! empty( $r['count_total'] ) ) {

			/**
			 * Filters the total meeting MySQL statement.
			 *
			 * @since 1.0.0
			 *
			 * @param string $value     MySQL statement used to query for total meetings.
			 * @param string $where_sql MySQL WHERE statement portion.
			 * @param string $sort      Sort direction for query.
			 */
			$total_meetings_sql = apply_filters( 'bp_zoom_meeting_total_medias_sql', "SELECT count(DISTINCT m.id) FROM {$bp->table_prefix}bp_zoom_meetings m {$join_sql} {$where_sql}", $where_sql, $sort );
			$cached             = bp_core_get_incremented_cache( $total_meetings_sql, $cache_group );
			if ( false === $cached ) {
				$total_meetings = $wpdb->get_var( $total_meetings_sql );
				bp_core_set_incremented_cache( $total_meetings_sql, $cache_group, $total_meetings );
			} else {
				$total_meetings = $cached;
			}

			if ( ! empty( $r['max'] ) ) {
				if ( (int) $total_meetings > (int) $r['max'] ) {
					$total_meetings = $r['max'];
				}
			}

			$retval['total'] = $total_meetings;
		}

		return $retval;
	}

	/**
	 * Convert media IDs to meeting objects, as expected in template loop.
	 *
	 * @since 1.0.0
	 *
	 * @param array $meeting_ids Array of meeting IDs.
	 * @return array
	 */
	protected static function get_meeting_data( $meeting_ids = array() ) {
		global $wpdb;

		// Bail if no meeting ID's passed.
		if ( empty( $meeting_ids ) ) {
			return array();
		}

		// Get BuddyPress.
		$bp = buddypress();

		$meetings     = array();
		$uncached_ids = bp_get_non_cached_ids( $meeting_ids, 'bp_meeting' );

		// Prime caches as necessary.
		if ( ! empty( $uncached_ids ) ) {
			// Format the meeting ID's for use in the query below.
			$uncached_ids_sql = implode( ',', wp_parse_id_list( $uncached_ids ) );

			// Fetch data from meeting table, preserving order.
			$queried_adata = $wpdb->get_results( "SELECT * FROM {$bp->table_prefix}bp_zoom_meetings WHERE id IN ({$uncached_ids_sql})" );

			// Put that data into the placeholders created earlier,
			// and add it to the cache.
			foreach ( (array) $queried_adata as $adata ) {
				wp_cache_set( $adata->id, $adata, 'bp_meeting' );
			}
		}

		// Now fetch data from the cache.
		foreach ( $meeting_ids as $meeting_id ) {
			// Integer casting.
			$meeting = wp_cache_get( $meeting_id, 'bp_meeting' );
			if ( ! empty( $meeting ) ) {

				$meeting_is_past = false;
				if ( strtotime( wp_date( 'Y-m-d H:i:s', time(), new DateTimeZone( 'UTC' ) ) ) > strtotime( $meeting->start_date_utc ) ) {
					$meeting_is_past = true;
				}

				$meeting->id                     = (int) $meeting->id;
				$meeting->group_id               = (int) $meeting->group_id;
				$meeting->activity_id            = (int) $meeting->activity_id;
				$meeting->user_id                = (int) $meeting->user_id;
				$meeting->duration               = (int) $meeting->duration;
				$meeting->join_before_host       = (bool) $meeting->join_before_host;
				$meeting->host_video             = (bool) $meeting->host_video;
				$meeting->participants_video     = (bool) $meeting->participants_video;
				$meeting->mute_participants      = (bool) $meeting->mute_participants;
				$meeting->meeting_authentication = (bool) $meeting->meeting_authentication;
				$meeting->waiting_room           = (bool) $meeting->waiting_room;
				$meeting->recurring              = (bool) $meeting->recurring;
				$meeting->is_past                = (bool) $meeting_is_past;

				// Update meeting recording data.
				// bp_zoom_meeting_update_recordings_data( $meeting_id, $meeting );

				$meeting->join_url        = bp_zoom_meeting_get_meta( $meeting_id, 'zoom_join_url', true );
				$meeting->start_url       = bp_zoom_meeting_get_meta( $meeting_id, 'zoom_start_url', true );
				$meeting->zoom_details    = bp_zoom_meeting_get_meta( $meeting_id, 'zoom_details', true );
//				$meeting->recording_count = bp_zoom_meeting_get_meta( $meeting_id, 'zoom_recording_count', true );
//				$meeting->recording_files = bp_zoom_meeting_get_meta( $meeting_id, 'zoom_recording_files', true );
				$meeting->invitation      = bp_zoom_meeting_get_meta( $meeting_id, 'zoom_meeting_invitation', true );
			}

			$meetings[] = $meeting;
		}

		return $meetings;
	}

	/**
	 * Get the SQL for the 'meta_query' param in BP_Zoom_Meeting::get().
	 *
	 * We use WP_Meta_Query to do the heavy lifting of parsing the
	 * meta_query array and creating the necessary SQL clauses. However,
	 * since BP_Zoom_Meeting::get() builds its SQL differently than
	 * WP_Query, we have to alter the return value (stripping the leading
	 * AND keyword from the 'where' clause).
	 *
	 * @since 1.0.0
	 *
	 * @param array $meta_query An array of meta_query filters. See the
	 *                          documentation for WP_Meta_Query for details.
	 * @return array $sql_array 'join' and 'where' clauses.
	 */
	public static function get_meta_query_sql( $meta_query = array() ) {
		global $wpdb;

		$sql_array = array(
			'join'  => '',
			'where' => '',
		);

		if ( ! empty( $meta_query ) ) {
			$meeting_meta_query = new WP_Meta_Query( $meta_query );

			// WP_Meta_Query expects the table name at
			// $wpdb->meetingmeta.
			$wpdb->meetingmeta = bp_core_get_table_prefix() . 'bp_zoom_meeting_meta';

			$meta_sql = $meeting_meta_query->get_sql( 'meeting', 'm', 'id' );

			// Strip the leading AND - BP handles it in get().
			$sql_array['where'] = preg_replace( '/^\sAND/', '', $meta_sql['where'] );
			$sql_array['join']  = $meta_sql['join'];
		}

		return $sql_array;
	}

	/**
	 * Delete meeting items from the database.
	 *
	 * To delete a specific meeting item, pass an 'id' parameter.
	 * Otherwise use the filters.
	 *
	 * @param array $args {
	 * @int    $id                Optional. The ID of a specific item to delete.
	 * @int    $meeting_id           Optional. The meeting ID to filter by.
	 * @int    $group_id           Optional. The group ID to filter by.
	 * }
	 *
	 * @return array|bool An array of deleted meeting IDs on success, false on failure.
	 * @since 1.0.0
	 */
	public static function delete( $args = array(), $from = false ) {
		global $wpdb;

		$bp = buddypress();
		$r  = wp_parse_args(
			$args,
			array(
				'id'          => false,
				'meeting_id'  => false,
				'group_id'    => false,
				'activity_id' => false,
				'user_id'     => false,
			)
		);

		// Setup empty array from where query arguments.
		$where_args = array();

		// ID.
		if ( ! empty( $r['id'] ) ) {
			$where_args[] = $wpdb->prepare( 'id = %d', $r['id'] );
		}

		// meeting ID.
		if ( ! empty( $r['meeting_id'] ) ) {
			$where_args[] = $wpdb->prepare( 'meeting_id = %d', $r['meeting_id'] );
		}

		// group ID.
		if ( ! empty( $r['group_id'] ) ) {
			$where_args[] = $wpdb->prepare( 'group_id = %d', $r['group_id'] );
		}

		// activity ID.
		if ( ! empty( $r['activity_id'] ) ) {
			$where_args[] = $wpdb->prepare( 'activity_id = %d', $r['activity_id'] );
		}

		// site user ID.
		if ( ! empty( $r['user_id'] ) ) {
			$where_args[] = $wpdb->prepare( 'user_id = %d', $r['user_id'] );
		}

		// Bail if no where arguments.
		if ( empty( $where_args ) ) {
			return false;
		}

		// Join the where arguments for querying.
		$where_sql = 'WHERE ' . join( ' AND ', $where_args );

		// Fetch all meeting being deleted so we can perform more actions.
		$meetings = $wpdb->get_results( "SELECT * FROM {$bp->table_prefix}bp_zoom_meetings {$where_sql}" );

		/**
		 * Action to allow intercepting meeting items to be deleted.
		 *
		 * @param array $meetings Array of meeting.
		 * @param array $r Array of parsed arguments.
		 *
		 * @since 1.0.0
		 */
		do_action_ref_array( 'bp_zoom_meeting_before_delete', array( $meetings, $r ) );

		// Attempt to delete meeting from the database.
		$deleted = $wpdb->query( "DELETE FROM {$bp->table_prefix}bp_zoom_meetings {$where_sql}" );

		// Bail if nothing was deleted.
		if ( empty( $deleted ) ) {
			return false;
		}

		/**
		 * Action to allow intercepting meeting items just deleted.
		 *
		 * @param array $meetings Array of meeting.
		 * @param array $r Array of parsed arguments.
		 *
		 * @since 1.0.0
		 */
		do_action_ref_array( 'bp_zoom_meeting_after_delete', array( $meetings, $r ) );

		// Pluck the meeting IDs out of the $meetings array.
		$meeting_ids = wp_parse_id_list( wp_list_pluck( $meetings, 'id' ) );

		return $meeting_ids;
	}

	/**
	 * Update Meeting count.
	 *
	 * @param $id Meeting ID.
	 * @param int $count Recording counts.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public function update_recording_count( $id = 0, $count = 0 ) {
		global $wpdb, $bp;

		if ( empty( $id ) ) {
			return false;
		}

		$q = $wpdb->prepare( "UPDATE {$bp->table_prefix}bp_zoom_meetings SET  recordings = %d WHERE id = %d", $count, $id );

		if ( false === $wpdb->query( $q ) ) {
			return false;
		}

		return true;
	}
}
