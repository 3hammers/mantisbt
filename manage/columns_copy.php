<?php
# MantisBT - A PHP based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package MantisBT
 * @copyright Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
 * @copyright Copyright (C) 2002 - 2012  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 *
 * @uses core.php
 * @uses access_api.php
 * @uses authentication_api.php
 * @uses columns_api.php
 * @uses config_api.php
 * @uses constant_inc.php
 * @uses current_user_api.php
 * @uses form_api.php
 * @uses gpc_api.php
 * @uses print_api.php
 */

/**
 * MantisBT Core API's
 */
require_once( '../core.php' );
require_api( 'access_api.php' );
require_api( 'authentication_api.php' );
require_api( 'columns_api.php' );
require_api( 'config_api.php' );
require_api( 'constant_inc.php' );
require_api( 'current_user_api.php' );
require_api( 'form_api.php' );
require_api( 'gpc_api.php' );
require_api( 'print_api.php' );

form_security_validate( 'manage_columns_copy' );

auth_reauthenticate();

$f_project_id		= gpc_get_int( 'project_id' );
$f_other_project_id	= gpc_get_int( 'other_project_id' );
$f_copy_from		= gpc_get_bool( 'copy_from' );
$f_copy_to			= gpc_get_bool( 'copy_to' );
$f_manage_page		= gpc_get_bool( 'manage_page' );

if ( $f_copy_from ) {
	$t_src_project_id = $f_other_project_id;
	$t_dst_project_id = $f_project_id;
} else if ( $f_copy_to ) {
	$t_src_project_id = $f_project_id;
	$t_dst_project_id = $f_other_project_id;
} else {
	throw new MantisBT\Exception\Generic();
}

# only admins can set global defaults.for ALL_PROJECT
if ( $f_manage_page && $t_dst_project_id == ALL_PROJECTS && !current_user_is_administrator() ) {
	throw new MantisBT\Exception\Access_Denied();
}

# only MANAGERS can set global defaults.for a project
if ( $f_manage_page && $t_dst_project_id != ALL_PROJECTS ) {
	access_ensure_project_level( MANAGER, $t_dst_project_id );
}

# user should only be able to set columns for a project that is accessible and exists
if ( $t_dst_project_id != ALL_PROJECTS ) {
	// ensure the project we are copying from exists, if not all projects
	if( $f_project_id !== ALL_PROJECTS ) {
		project_ensure_exists( $f_project_id );
	}
	// ensure the user has access to the destination project
	access_ensure_project_level( VIEWER, $t_dst_project_id );
}

# Calculate the user id to set the configuration for.
if ( $f_manage_page ) {
	$t_user_id = NO_USER;
} else {
	$t_user_id = auth_get_current_user_id();
}

$t_all_columns = columns_get_all();
$t_default = null;

$t_view_issues_page_columns = config_get( 'view_issues_page_columns', $t_default, $t_user_id, $t_src_project_id );
$t_view_issues_page_columns = columns_remove_invalid( $t_view_issues_page_columns, $t_all_columns );

$t_print_issues_page_columns = config_get( 'print_issues_page_columns', $t_default, $t_user_id, $t_src_project_id );
$t_print_issues_page_columns = columns_remove_invalid( $t_print_issues_page_columns, $t_all_columns );

$t_export_columns = config_get( 'export_columns', $t_default, $t_user_id, $t_src_project_id );
$t_export_columns = columns_remove_invalid( $t_export_columns, $t_all_columns );

config_set( 'view_issues_page_columns', $t_view_issues_page_columns, $t_user_id, $t_dst_project_id );
config_set( 'print_issues_page_columns', $t_print_issues_page_columns, $t_user_id, $t_dst_project_id );
config_set( 'export_columns', $t_export_columns, $t_user_id, $t_dst_project_id );

form_security_purge( 'manage_columns_copy' );

$t_redirect_url = $f_manage_page ? 'manage_config_columns_page.php' : 'account_manage_columns_page.php';
print_header_redirect( $t_redirect_url );