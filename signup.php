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
 * Sign Up
 * @package MantisBT
 * @copyright Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
 * @copyright Copyright (C) 2002 - 2012  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 *
 * @uses core.php
 * @uses authentication_api.php
 * @uses config_api.php
 * @uses constant_inc.php
 * @uses crypto_api.php
 * @uses email_api.php
 * @uses form_api.php
 * @uses gpc_api.php
 * @uses helper_api.php
 * @uses lang_api.php
 * @uses print_api.php
 * @uses user_api.php
 * @uses utility_api.php
 */

require_once( 'core.php' );
require_api( 'authentication_api.php' );
require_api( 'config_api.php' );
require_api( 'constant_inc.php' );
require_api( 'crypto_api.php' );
require_api( 'email_api.php' );
require_api( 'form_api.php' );
require_api( 'gpc_api.php' );
require_api( 'helper_api.php' );
require_api( 'lang_api.php' );
require_api( 'print_api.php' );
require_api( 'user_api.php' );
require_api( 'utility_api.php' );

form_security_validate( 'signup' );

$f_username		= strip_tags( gpc_get_string( 'username' ) );
$f_email		= strip_tags( gpc_get_string( 'email' ) );
$f_captcha		= gpc_get_string( 'captcha', '' );

$f_username = trim( $f_username );
$f_captcha = utf8_strtolower( trim( $f_captcha ) );

# force logout on the current user if already authenticated
if( auth_is_user_authenticated() ) {
	auth_logout();
}

# Check to see if signup is allowed
if ( OFF == config_get_global( 'allow_signup' ) ) {
	print_header_redirect( 'login_page.php' );
	exit;
}

# captcha image requires GD library and related option to ON
if( ON == config_get( 'signup_use_captcha' ) && helper_call_custom_function( 'auth_can_change_password', array() ) ) {
	if( !extension_loaded('gd') ) {
		throw new MantisBT\Exception\Missing_GD_Extension();
	}

	require_lib( 'securimage/securimage.php' );

	$securimage = new Securimage();

	if ($securimage->check($f_captcha) == false) {
		throw new MantisBT\Exception\SignUp_Not_Matching_Captcha();
	}
}

email_ensure_valid( $f_email );
email_ensure_not_disposable( $f_email );

# notify the selected group a new user has signed-up
if( user_create( $f_username, /*blank password = auto generate*/ '', $f_email ) ) {
	email_notify_new_account( $f_username, $f_email );
}

form_security_purge( 'signup' );

html_page_top1();
html_page_top2a();
?>

<br />
<div>
<table class="width50" cellspacing="1">
<tr>
	<td class="center">
		<strong><?php echo lang_get( 'signup_done_title' ) ?></strong><br/>
		<?php echo "[$f_username - $f_email] " ?>
	</td>
</tr>
<tr>
	<td>
		<br/>
		<?php echo lang_get( 'password_emailed_msg' ) ?>
		<br /><br/>
		<?php echo lang_get( 'no_reponse_msg') ?>
		<br/><br/>
	</td>
</tr>
</table>
<br />
<?php print_bracket_link( 'login_page.php', lang_get( 'proceed' ) ); ?>
</div>

<?php
html_page_bottom1a( __FILE__ );
