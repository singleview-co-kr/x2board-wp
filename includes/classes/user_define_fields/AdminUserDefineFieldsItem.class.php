<?php
/* Copyright (C) singleview.co.kr <https://singleview.co.kr> */

/**
 * A class to handle extra variables used in posts
 */
namespace X2board\Includes\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Each value of the extra vars
 *
 * @author XEHub (developers@xpressengine.com)
 */
if ( ! class_exists( '\\X2board\\Includes\\Classes\\AdminUserDefineFieldsItem' ) ) {

	class AdminUserDefineFieldsItem extends UserDefineFields {

		protected $_a_all_fields = null;

		// default fields
		protected $_s_field_type    = null;
		protected $_s_field_name    = null;
		protected $_s_meta_key      = null;
		protected $_s_search        = null;
		protected $_s_default_value = null;
		protected $_s_description   = null;
		protected $_s_required      = null;

		// optional
		protected $_s_hidden                   = null;
		protected $_s_email_permission         = null;
		protected $_a_email                    = array();
		protected $_s_notice_permission        = null;
		protected $_a_notice                   = array();
		protected $_s_allow_comment_permission = null;
		protected $_a_allow_comment            = array();

		protected $_s_permission    = null;
		protected $_s_placeholder   = null;
		protected $_s_option_field  = null;
		protected $_a_roles         = array();
		protected $_a_row           = array();
		protected $_s_custom_class  = null;
		protected $_s_show_document = null;

		protected $_s_term = null;

		/**
		 * Constructor
		 *
		 * @param string $a_single_field field information array
		 * @return void
		 */
		public function __construct( $a_single_field ) {
			parent::__construct();
			$this->_a_all_fields = array_merge( $this->_a_default_fields, $this->_a_extends_fields );
			// default fields
			$this->_s_field_type  = $a_single_field['field_type'];
			$this->_s_field_name  = $a_single_field['field_name'];
			$this->_s_meta_key    = $a_single_field['meta_key'];
			$this->_s_search      = $a_single_field['search'];
			$this->_s_description = $a_single_field['description'];

			// optional fields
			if ( isset( $a_single_field['default_value'] ) ) {
				$this->_s_default_value = $a_single_field['default_value'];
			}
			if ( isset( $a_single_field['required'] ) ) {
				$this->_s_required = $a_single_field['required'];
			}

			if ( isset( $a_single_field['hidden'] ) ) {
				$this->_s_hidden = $a_single_field['hidden'];
			}

			if ( isset( $a_single_field['email_permission'] ) ) {
				$this->_s_email_permission = $a_single_field['email_permission'];
			}
			if ( isset( $a_single_field['email'] ) && is_array( $a_single_field['email'] ) ) {
				$this->_a_email = $a_single_field['email'];
			}

			if ( isset( $a_single_field['notice_permission'] ) ) {
				$this->_s_notice_permission = $a_single_field['notice_permission'];
			}
			if ( isset( $a_single_field['notice'] ) && is_array( $a_single_field['notice'] ) ) {
				$this->_a_notice = $a_single_field['notice'];
			}
			if ( isset( $a_single_field['allow_comment_permission'] ) ) {
				$this->_s_allow_comment_permission = $a_single_field['allow_comment_permission'];
			}
			if ( isset( $a_single_field['allow_comment'] ) && is_array( $a_single_field['allow_comment'] ) ) {
				$this->_a_allow_comment = $a_single_field['allow_comment'];
			}

			if ( isset( $a_single_field['permission'] ) ) {
				$this->_s_permission = $a_single_field['permission'];
			}
			if ( isset( $a_single_field['placeholder'] ) ) {
				$this->_s_placeholder = $a_single_field['placeholder'];
			}
			if ( isset( $a_single_field['option_field'] ) ) {
				$this->_s_option_field = $a_single_field['option_field'];
			}
			if ( isset( $a_single_field['roles'] ) && is_array( $a_single_field['roles'] ) ) {
				$this->_a_roles = $a_single_field['roles'];
			}
			if ( isset( $a_single_field['row'] ) && is_array( $a_single_field['row'] ) ) {
				$this->_a_row = $a_single_field['row'];
			}
			if ( isset( $a_single_field['custom_class'] ) ) {
				$this->_s_custom_class = $a_single_field['custom_class'];
			}
			if ( isset( $a_single_field['show_document'] ) ) {
				$this->_s_show_document = $a_single_field['show_document'];
			}

			if ( isset( $a_single_field['term'] ) ) {
				$this->_s_term = $a_single_field['term'];
			}
		}

		/**
		 * Returns a form based on its type
		 *
		 * @return string Returns a widget html.
		 */
		public function get_widget_html() {
			$s_field_label = $this->_get_field_label();

			$s_html  = null;
			$s_html .= '<li class="' . $this->_get_field_type() . ' ' . esc_attr( $this->_s_meta_key ) . ' ' . esc_attr( $this->_s_field_type ) . '">';
			$s_html .= '<input type="hidden" class="parent_id" value="' . esc_attr( $this->_s_meta_key ) . '">';
			$s_html .= '<div class="x2board-saved-fields-header">';
			$s_html .= '<div class="x2board-fields-title toggle x2board-field-handle">
											<button type="button">';
			$s_html .= esc_html( $s_field_label );
			if ( $this->_s_field_name ) {
				$s_html .= ' : ' . esc_html( $this->_s_field_name );
			}
			$s_html .= '<span class="fields-up">▲</span>
												<span class="fields-down">▼</span>
											</button>
										</div>';
			if ( $this->_a_all_fields[ $this->_s_field_type ]['close_button'] == 'yes' ) {
				$s_html .= '<div class="x2board-fields-toggle">
											<button type="button" class="fields-remove" title="' . __( 'cmd_remove', X2B_DOMAIN ) . '">X</button>
										</div>';
			}
			$s_html .= '</div>
									<div class="x2board-fields-content">
										<input type="hidden" name="fields[' . esc_attr( $this->_s_meta_key ) . '][field_type]" class="field_data field_type" value="' . esc_attr( $this->_s_field_type ) . '">';
			// 입력란의 [필드 레이블]에 해당하는 변수가 field_name 변수, field_name이 공란일 때 [필드 레이블]의 기본값
			$s_html .= '<input type="hidden" name="fields[' . esc_attr( $this->_s_meta_key ) . '][field_label]" class="field_data field_label" value="' . esc_attr( $s_field_label ) . '">';
			if ( $this->_s_option_field ) {
				$s_html .= '<input type="hidden" name="fields[' . esc_attr( $this->_s_meta_key ) . '][option_field]" class="field_data option_field" value="1">';
			}
			if ( ! is_null( $this->_s_hidden ) ) {
				$s_html .= '<input type="text" name="fields[' . esc_attr( $this->_s_meta_key ) . '][hidden]" class="field_data hidden" value="1">';
			}
			if ( $this->_s_field_type == 'title' ) {
				$s_html .= '<div class="attr-row">
											<div class="description">※ ' . __( 'msg_title_is_mandatory', X2B_DOMAIN ) . '</div>
											<input type="hidden" name="fields[title][permission]" value="all">
										</div>';
			} elseif ( $this->_s_field_type == 'attach' ) {
				$s_html .= '<div class="attr-row">
											<p class="description">※ ' . __( 'about_user_defined_file_appending_field', X2B_DOMAIN ) . '</p>
										</div>';
				/*
				$s_html .=                '<div class="attr-row">
											<label class="attr-name" for="max_each_file_size_mb">첨부파일 당 최대 용량(Mb)</label>
											<div class="attr-value">
												<input type="text" id="max_each_file_size_mb" name="max_each_file_size_mb" value="'.esc_attr($meta->max_each_file_size_mb ).'" placeholder="Mb 단위 입력">
											</div>
										</div>
										<div class="attr-row">
											<label class="attr-name" for="max_attached_count">게시글당 최대 첨부파일 개수</label>
											<div class="attr-value">
												<select name="max_attached_count" id="max_attached_count">
													<option value="">없음</option>
													<option value="1" selected>1개</option>
												</select>
												<!-- <p class="description">일부 스킨에서는 적용되지 않습니다.</p> -->
											</div>
										</div>';*/
			} elseif ( $this->_s_field_type == 'kr_zip' ) {
				$s_html .= '<div class="attr-row">
											<div class="description">※ ' . __( 'about_user_defined_address_field', X2B_DOMAIN ) . '</div>
										</div>';
			}

			if ( $this->_s_field_name ) {
				$s_html .= '<div class="attr-row">
											<label class="attr-name" for="' . esc_attr( $this->_s_meta_key ) . '-field-label">' . __( 'lbl_field_label', X2B_DOMAIN ) . '</label>
											<div class="attr-value">
												<input type="text" id="' . esc_attr( $this->_s_meta_key ) . '-field-label" name="fields[' . esc_attr( $this->_s_meta_key ) . '][field_name]" class="field_data field_name" value="' . esc_attr( $this->_s_field_name ) . '" placeholder="' . esc_attr( $s_field_label ) . '">
											</div>
										</div>';
			}
			$s_readonly = ! is_null( $this->_s_meta_key ) ? 'readonly' : '';
			$s_html    .= '<div class="attr-row">
											<label class="attr-name" for="' . esc_attr( $this->_s_meta_key ) . '">' . __( 'lbl_meta_key', X2B_DOMAIN ) . '</label>
											<div class="attr-value">
												<input type="text" name="fields[' . esc_attr( $this->_s_meta_key ) . '][meta_key]" id="' . esc_attr( $this->_s_meta_key ) . '" class="field_data meta_key" value="' . $this->_s_meta_key . '" ' . $s_readonly . ' placeholder="meta_key">
											</div>
											<div class="description">※ ' . __( 'about_meta_key', X2B_DOMAIN ) . '</div>
										</div>';
			if ( ! empty( $this->_a_row ) ) {
				if ( $this->_is_value_exists() ) {
					$already_echo = false;
					$s_html      .= '<div class="x2board-radio-reset">';
					foreach ( $this->_a_row as $option_key => $option_value ) {
						if ( isset( $option_value['label'] ) && $option_value['label'] ) {
							$s_html .= '<div class="attr-row option-wrap">
												<div class="attr-name option">
													<label for="' . esc_attr( $option_key ) . '_label">' . __( 'lbl_label', X2B_DOMAIN ) . '</label>
												</div>
												<div class="attr-value">
													<input type="text" id="' . esc_attr( $option_key ) . '_label" name="fields[' . esc_attr( $this->_s_meta_key ) . '][row][' . esc_attr( $option_key ) . '][label]" id="' . esc_attr( $this->_s_meta_key ) . '" class="field_data option_label" value="' . esc_attr( $option_value['label'] ) . '">
													<button type="button" class="' . esc_attr( $this->_s_field_type ) . '" onclick="add_option(this)">+</button>
													<button type="button" class="' . esc_attr( $this->_s_field_type ) . '" onclick="remove_option(this)">-</button>
													<label>';
							if ( $this->_s_field_type == 'checkbox' ) {
								$s_default_value = ( isset( $option_value['default_value'] ) && $option_value['default_value'] == '1' ) ? 'checked' : '';
								$s_html         .= '<input type="checkbox" name="fields[' . esc_attr( $this->_s_meta_key ) . '][row][' . esc_attr( $option_key ) . '][default_value]" class="field_data default_value" ' . $s_default_value . ' value="1">';
							} else {
								$s_checked = $this->_s_default_value == $option_key ? 'checked' : '';
								$s_html   .= '<input type="radio" name="fields[' . esc_attr( $this->_s_meta_key ) . '][default_value]" class="field_data default_value" value="' . esc_attr( $option_key ) . '">';
							}
							$s_html .= __( 'lbl_default_value', X2B_DOMAIN ) . '
													</label>';
							if ( $this->_s_field_type == 'radio' || $this->_s_field_type == 'select' ) {
								if ( ! $already_echo ) {
									$s_html      .= '<span style="vertical-align:middle;cursor:pointer;" onclick="x2board_radio_reset(this)">· ' . __( 'cmd_reset', X2B_DOMAIN ) . '</span>';
									$already_echo = true;
								}
							}
							$s_html .= '</div>
											</div>';
						}
					}
					$s_html .= '</div>';
				} else {
					$uniq_id = 'php_' . uniqid();
					$s_html .= '<div class="attr-row option-wrap">
								<div class="attr-name option">
									<label for="' . esc_attr( $this->_s_meta_key ) . '_label">' . __( 'lbl_label', X2B_DOMAIN ) . '</label>
								</div>
								<div class="attr-value">
									<input type="text" id="' . esc_attr( $this->_s_meta_key ) . '_label" name="fields[' . esc_attr( $this->_s_meta_key ) . '][row][' . $uniq_id . '][label]" class="field_data option_label" value="">
									<button type="button" class="' . esc_attr( $this->_s_field_type ) . '" onclick="add_option(this)">+</button>
									<button type="button" class="' . esc_attr( $this->_s_field_type ) . '" onclick="remove_option(this)">-</button>
									<label>';
					if ( $this->_s_field_type == 'checkbox' ) {
						$s_html .= '<input type="checkbox" name="fields[' . esc_attr( $this->_s_meta_key ) . '][row][' . $uniq_id . '][default_value]" class="field_data default_value" value="">';
					} else {
						$s_html .= '<input type="radio" name="fields[' . esc_attr( $this->_s_meta_key ) . '][default_value]" class="field_data default_value" value="">';
					}
					$s_html .= __( 'lbl_default_value', X2B_DOMAIN ) . '
									</label>
								</div>
							</div>';
				}
			}
			if ( ! is_null( $this->_s_permission ) && $this->_s_field_type != 'title' ) {
				$s_html .= '<div class="attr-row">
								<label class="attr-name" for="' . esc_attr( $this->_s_meta_key ) . '_permission">' . __( 'msg_whom_to_show', X2B_DOMAIN ) . '</label>
								<div class="attr-value">';

				$s_html    .= '<select id="' . esc_attr( $this->_s_meta_key ) . '_permission" name="fields[' . esc_attr( $this->_s_meta_key ) . '][permission]" class="field_data roles" onchange="x2board_fields_permission_roles_view(this)">';
				$s_selected = $this->_s_permission == 'all' ? 'selected' : '';
				$s_html    .= '<option value="all" ' . $s_selected . ' >' . __( 'opt_role_all_users', X2B_DOMAIN ) . '</option>';
				$s_selected = $this->_s_permission == 'author' ? 'selected' : '';
				$s_html    .= '<option value="author" ' . $s_selected . '>' . __( 'opt_role_loggedin_users', X2B_DOMAIN ) . '</option>';
				$s_selected = $this->_s_permission == 'roles' ? 'selected' : '';
				$s_html    .= '<option value="roles" ' . $s_selected . '>' . __( 'opt_role_customize', X2B_DOMAIN ) . '</option>
									</select>';
				$s_hide     = $this->_s_permission != 'roles' ? 'x2board-hide' : '';
				$s_html    .= '<div class="x2board-permission-read-roles-view ' . $s_hide . '">';
				foreach ( get_editable_roles() as $roles_key => $roles_value ) {
					$s_mandatory = $roles_key == 'administrator' ? 'onclick="return false"' : '';
					if ( ! is_null( $this->_a_roles ) ) {
						$s_checked = ( $roles_key == 'administrator' || in_array( $roles_key, $this->_a_roles ) ) ? 'checked' : '';
					} else {
						$s_checked = '';
					}
					$s_html .= '<label><input type="checkbox" name="fields[' . esc_attr( $this->_s_meta_key ) . '][roles][]" class="field_data" value="' . $roles_key . '" ' . $s_mandatory . ' ' . $s_checked . '> ' . _x( $roles_value['name'], 'User role' ) . '</label>';
				}
				$s_html .= '</div>';
				$s_html .= '</div>
							</div>';
			}
			if ( $this->_s_field_type == 'option' ) {
				$s_html    .= '<div class="attr-row">
										<label class="attr-name" for="' . esc_attr( $this->_s_meta_key ) . '_email">' . __( 'msg_allow_email_input_for_guest', X2B_DOMAIN ) . '</label>
											<div class="attr-value">
												<select id="' . esc_attr( $this->_s_meta_key ) . '_email" name="fields[option][email_permission]" class="field_data roles" onchange="x2board_fields_permission_roles_view(this)">';
				$s_selected = $this->_s_email_permission == 'allow' ? 'selected' : '';
				$s_html    .= '<option value="allow" ' . $s_selected . '>' . __( 'opt_allow', X2B_DOMAIN ) . '</option>';
				$s_selected = ! $this->_s_email_permission || $this->_s_email_permission == 'disallow' ? 'selected' : '';
				$s_html    .= '<option value="disallow" ' . $s_selected . '>' . __( 'opt_disallow', X2B_DOMAIN ) . '</option>';
				$s_html    .= '</select>';
				$s_html    .= '</div>
										</div>';

				$s_html    .= '<div class="attr-row">
											<label class="attr-name" for="' . esc_attr( $this->_s_meta_key ) . '_notice">' . __( 'lbl_allow_notice', X2B_DOMAIN ) . '</label>
											<div class="attr-value">
												<select id="' . esc_attr( $this->_s_meta_key ) . '_notice" name="fields[option][notice_permission]" class="field_data roles" onchange="x2board_fields_permission_roles_view(this)">';
				$s_selected = $this->_s_notice_permission == 'all' ? 'selected' : '';
				$s_html    .= '<option value="all" ' . $s_selected . '>' . __( 'opt_role_all_users', X2B_DOMAIN ) . '</option>';
				$s_selected = $this->_s_notice_permission == 'author' ? 'selected' : '';
				$s_html    .= '<option value="author" ' . $s_selected . '>' . __( 'opt_role_loggedin_users', X2B_DOMAIN ) . '</option>';
				$s_selected = $this->_s_notice_permission == 'roles' ? 'selected' : '';
				$s_html    .= '<option value="roles" ' . $s_selected . '>' . __( 'opt_role_customize', X2B_DOMAIN ) . '</option>
												</select>';
				$s_hide     = $this->_s_notice_permission != 'roles' ? 'x2board-hide' : '';
				$s_html    .= '<div class="x2board-permission-read-roles-view ' . $s_hide . ' ">';
				foreach ( get_editable_roles() as $roles_key => $roles_value ) {
					$s_mandatory = $roles_key == 'administrator' ? 'onclick="return false"' : '';
					$s_checked   = ( $roles_key == 'administrator' || in_array( $roles_key, $this->_a_notice ) ) ? 'checked' : '';
					$s_html     .= '<label><input type="checkbox" name="fields[option][notice][]" class="field_data" value="' . $roles_key . '" ' . $s_mandatory . ' ' . $s_checked . '> ' . _x( $roles_value['name'], 'User role' ) . '</label>';
				}
				$s_html .= '</div>
											</div>
										</div>';

				$s_html    .= '<div class="attr-row">
											<label class="attr-name" for="' . esc_attr( $this->_s_meta_key ) . '_allow_comment">' . __( 'lbl_allow_comment', X2B_DOMAIN ) . '</label>
											<div class="attr-value">
												<select id="' . esc_attr( $this->_s_meta_key ) . '_allow_comment" name="fields[option][allow_comment_permission]" class="field_data roles" onchange="x2board_fields_permission_roles_view(this)">';
				$s_selected = $this->_s_allow_comment_permission == 'all' ? 'selected' : '';
				$s_html    .= '<option value="all" ' . $s_selected . ' >' . __( 'opt_role_all_users', X2B_DOMAIN ) . '</option>';
				$s_selected = $this->_s_allow_comment_permission == 'author' ? 'selected' : '';
				$s_html    .= '<option value="author" ' . $s_selected . '>' . __( 'opt_role_loggedin_users', X2B_DOMAIN ) . '</option>';
				$s_selected = $this->_s_allow_comment_permission == 'roles' ? 'selected' : '';
				$s_html    .= '<option value="roles" ' . $s_selected . '>' . __( 'opt_role_customize', X2B_DOMAIN ) . '</option>';
				$s_html    .= '</select>';
				$s_hide     = $this->_s_allow_comment_permission != 'roles' ? 'x2board-hide' : '';
				$s_html    .= '<div class="x2board-permission-read-roles-view ' . $s_hide . ' ">';
				foreach ( get_editable_roles() as $roles_key => $roles_value ) {
					$s_mandatory = $roles_key == 'administrator' ? 'onclick="return false"' : '';
					$s_checked   = ( $roles_key == 'administrator' || in_array( $roles_key, $this->_a_allow_comment ) ) ? 'checked' : '';
					$s_html     .= '<label><input type="checkbox" name="fields[option][allow_comment][]" class="field_data" value="' . $roles_key . '" ' . $s_mandatory . '  ' . $s_checked . '> ' . _x( $roles_value['name'], 'User role' ) . '</label>';
				}
				$s_html .= '</div>
											</div>
										</div>';
			}
			if ( isset( $this->_s_default_value ) && $this->_s_field_type != 'checkbox' && $this->_s_field_type != 'radio' && $this->_s_field_type != 'select' ) {
				$s_html .= '<div class="attr-row">
											<label class="attr-name" for="' . esc_attr( $this->_s_meta_key ) . '_default_value">' . __( 'lbl_default_value', X2B_DOMAIN ) . '</label>
											<div class="attr-value">';
				/*
				if($this->_s_field_type == 'search') {
					$s_html .=                  '<select id="'.esc_attr($this->_s_meta_key).'_default_value" name="fields[search][default_value]" class="field_data default_value">';
					$s_selected = $this->_s_default_value == '1' ? 'selected' : '';
					$s_html .=                      '<option value="1" '.$s_selected.'>제목과 내용 검색허용</option>';
					$s_selected = $this->_s_default_value == '2' ? 'selected' : '';
					$s_html .=                      '<option value="2" '.$s_selected.'>제목만 검색허용 (비밀글)</option>';
					$s_selected = $this->_s_default_value == '3' ? 'selected' : '';
					$s_html .=                      '<option value="3" '.$s_selected.'>통합검색 제외</option>
												</select>';
				}
				else {*/
					$s_html .= '<input type="text" id="' . esc_attr( $this->_s_meta_key ) . '_default_value" name="fields[' . esc_attr( $this->_s_meta_key ) . '][default_value]" class="field_data default_value" value="' . $this->_s_default_value . '">';
				// }
				$s_html .= '</div>
										</div>';
			}
			if ( $this->_is_multiline_fields() ) {
				$s_html .= '<div class="attr-row">';
				if ( $this->_s_field_type == 'html' ) {
					$s_html .= '<label class="attr-name" for="' . esc_attr( $this->_s_meta_key ) . '_html">' . $this->_s_field_name . '</label>
											<div class="attr-value">
												<textarea id="' . esc_attr( $this->_s_meta_key ) . '_html" name="fields[' . esc_attr( $this->_s_meta_key ) . '][html]" class="field_data html" rows="5">' . $item['html'] . '</textarea>
											</div>';
				} elseif ( $this->_s_field_type == 'shortcode' ) {
					$s_html .= '<label class="attr-name" for="' . esc_attr( $this->_s_meta_key ) . '_shortcode">' . $this->_s_field_name . '</label>
											<div class="attr-value">
												<textarea id="' . esc_attr( $this->_s_meta_key ) . '_shortcode" name="fields[' . esc_attr( $this->_s_meta_key ) . '][shortcode]" class="field_data shortcode" rows="5">' . $item['shortcode'] . '</textarea>
											</div>';
				}
				$s_html .= '</div>';
			}
			if ( $this->_s_field_type == 'term_agree' ) {
				$s_html .= '<div class="attr-row">';
				$s_html .= 		'<label class="attr-name" for="' . $this->_s_meta_key . '">' . __( 'lbl_term_condition', X2B_DOMAIN ) . '</label>';
				$s_html .= 		'<div class="attr-value">';
				$s_html .= 			'	<textarea id="' . esc_attr( $this->_s_meta_key ) . '_term" name="fields[' . esc_attr( $this->_s_meta_key ) . '][term]" class="field_data term" rows="5">' . $this->_s_term . '</textarea>';
				$s_html .= 		'</div>';
				$s_html .= 		'<div class="description">※ ' . __( 'desc_user_define_field_term_agree', X2B_DOMAIN ) . '</div>';
				$s_html .= 	'</div>';
			}
			if ( isset( $this->_s_placeholder ) ) {
				$s_html .= '<div class="attr-row">
											<label class="attr-name" for="' . esc_attr( $this->_s_meta_key ) . '_placeholder">' . __( 'lbl_placeholder', X2B_DOMAIN ) . '</label>
											<div class="attr-value"><input type="text" id="' . esc_attr( $this->_s_meta_key ) . '_placeholder" name="fields[' . esc_attr( $this->_s_meta_key ) . '][placeholder]" class="field_data placeholder" value="' . esc_attr( $this->_s_placeholder ) . '"></div>
										</div>';
			}
			if ( isset( $this->_s_description ) ) {
				$s_html .= '<div class="attr-row">
											<label class="attr-name" for="' . esc_attr( $this->_s_meta_key ) . '_description">' . __( 'lbl_description', X2B_DOMAIN ) . '</label>
											<div class="attr-value">
												<input type="text" id="' . esc_attr( $this->_s_meta_key ) . '_description" name="fields[' . esc_attr( $this->_s_meta_key ) . '][description]" class="field_data field_description" value="' . esc_attr( $this->_s_description ) . '">
											</div>
										</div>';
			}
			if ( isset( $this->_s_custom_class ) ) {
				$s_html .= '<div class="attr-row">
											<label class="attr-name" for="' . esc_attr( $this->_s_meta_key ) . '_custom_class">' . __( 'lbl_css_class', X2B_DOMAIN ) . '</label>
											<div class="attr-value"><input type="text" id="' . esc_attr( $this->_s_meta_key ) . '_custom_class" name="fields[' . esc_attr( $this->_s_meta_key ) . '][custom_class]" class="field_data custom_class" value="' . esc_attr( $this->_s_custom_class ) . '"></div>
										</div>';
			}
			if ( isset( $this->_s_show_document ) && ! $this->_is_multiline_fields() ) {
				$s_html    .= '<div class="attr-row">
											<label class="attr-name">' . __( 'lbl_printing_code', X2B_DOMAIN ) . '</label>
											<div class="attr-value">
												<div class="example">';
				$print_code = null;
				if ( $this->_get_field_type() == 'extends' || $this->_s_option_field ) {
					if ( $this->_s_field_type == 'file' ) {
						$print_code = '<?php echo $content->attach->{\'' . $this->_s_meta_key . '\'}[1]?>';
					} elseif ( $this->_s_field_type == 'checkbox' ) {
						$print_code = '<?php echo implode(\', \', $content->option->{\'' . $this->_s_meta_key . '\'})?>';
					} else {
						$print_code = '<?php echo $content->option->{\'' . $this->_s_meta_key . '\'}?>';
					}
				}
				$s_html .= esc_html( $print_code );
				$s_html .= '</div>
											</div>
										</div>';
			}
			if ( ! is_null( $this->_s_search ) || ! is_null( $this->_s_required ) || ! is_null( $this->_s_show_document ) || ! is_null( $this->_s_hidden ) ) {
				$s_html .= '<div class="attr-row">';
				if ( isset( $this->_s_search ) ) {
					$s_checked = $this->_s_search == 'Y' ? 'checked' : '';
					$s_html   .= '<label>
												<input type="hidden" name="fields[' . esc_attr( $this->_s_meta_key ) . '][search]" class="field_data search" value="">
												<input type="checkbox" name="fields[' . esc_attr( $this->_s_meta_key ) . '][search]" class="field_data search" value="Y" ' . $s_checked . ' >' . __( 'lbl_search', X2B_DOMAIN ) . '
											</label>';
				}
				if ( isset( $this->_s_required ) ) {
					$s_checked = $this->_s_required == '1' ? 'checked' : '';
					$s_html   .= '<label>
												<input type="hidden" name="fields[' . esc_attr( $this->_s_meta_key ) . '][required]" class="field_data required" value="">
												<input type="checkbox" name="fields[' . esc_attr( $this->_s_meta_key ) . '][required]" class="field_data required" value="1" ' . $s_checked . ' >' . __( 'lbl_required', X2B_DOMAIN ) . '
											</label>';
				}
				if ( $this->_s_show_document ) {
					$s_checked = $this->_s_show_document == '1' ? 'checked' : '';
					$s_html   .= '<label>
												<input type="hidden" name="fields[' . esc_attr( $this->_s_meta_key ) . '][show_document]" class="field_data show_document" value="">
												<input type="checkbox" name="fields[' . esc_attr( $this->_s_meta_key ) . '][show_document]" class="field_data show_document" value="1" ' . $s_checked . ' >' . __( 'lbl_display_on_content', X2B_DOMAIN ) . '
											</label>';
				}
				if ( ! is_null( $this->_s_hidden ) ) {
					$s_checked = $this->_s_hidden == '1' ? 'checked' : '';
					$s_hidden  = $this->_s_field_type == 'text' ? '(hidden)' : '';
					$s_html   .= '<label>
												<input type="hidden" name="fields[' . esc_attr( $this->_s_meta_key ) . '][hidden]" class="field_data hidden" value="">
												<input type="checkbox" name="fields[' . esc_attr( $this->_s_meta_key ) . '][hidden]" class="field_data hidden" value="1" ' . $s_checked . ' >' . __( 'lbl_hiding', X2B_DOMAIN ) . $s_hidden . '
											</label>';
				}
				$s_html .= '</div>';
			}
			$s_html .= '</div>
								</li>';
			return $s_html;
		}
	}
}
/* End of file AdminUserDefineFieldsItem.class.php */
