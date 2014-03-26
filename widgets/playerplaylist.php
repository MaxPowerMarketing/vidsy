<?php
//No permitimos el acceder directamente a este archivo
if (!defined('ABSPATH')) exit;

class VidsyWidgetPlayerWithPlaylist extends WP_Widget
{
				function VidsyWidgetPlayerWithPlaylist()
				{
								$widget_ops = array(
												'classname' => 'VidsyWidgetPlayerWithPlaylist',
												'description' => 'Displays a complete player with all videos collected in a single playlist'
								);
								$this->WP_Widget('VidsyWidgetPlayerWithPlaylist', 'VidsyTV: player with videos from a playlist', $widget_ops);
				}

				function form($instance)
				{
								$title = isset($instance['title']) ? esc_attr($instance['title']) : '';
								$theme = isset($instance['theme']) ? esc_attr($instance['theme']) : 'light';
								$playlistid = isset($instance['playlistid']) ? esc_attr($instance['playlistid']) : 'light';
								$height = isset($instance['height']) ? absint($instance['height']) : '430';
?>
						<p>
							<label for="<?php
								echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php
								echo $this->get_field_id('title'); ?>" name="<?php
								echo $this->get_field_name('title'); ?>" type="text" value="<?php
								echo $title; ?>" /></label>
						</p>
						<p>
							<label for="<?php
								echo $this->get_field_id('domain'); ?>">Vidsy's Subdomain:
								<?php
								$vidsy_options = get_option('vidsy_options', '');
								$vidsy_subdomain = $vidsy_options['subdomain'];

								if (empty($vidsy_subdomain) OR stripos($vidsy_subdomain, 'invalid') !== false) {
?>
										Please, <a href="<?php
												echo admin_url('admin.php?page=vidsy-config'); ?>">configure</a> your Vidsy subdomain first.
								<?php
								} else {
?>
								<strong><?php
												echo $vidsy_subdomain; ?></strong>
								<?php
								}
?>
							</label>
						</p>
						<?php
								$error = true;
								/*
												Traemos los resultados desde un trasient (cache 60 minutos), si no, y solo si no existen, vamos a la API para volver a consultar.
								*/
								if (false === ($playlists = get_transient('vidsy_playlists_' . $vidsy_subdomain))) {
												$apiresponse = wp_remote_get(VIDSY_URL . '/api/playlists/for/' . $vidsy_subdomain, array(
																'timeout' => 15
												));
												if (is_wp_error($apiresponse) || !isset($apiresponse['body'])) {
																$error = true;
												} else {
																$apiresults = json_decode(wp_remote_retrieve_body($apiresponse));
																if ($apiresults->status == 'error') {
																				$error = true;
																} else {
																				$error = false;
																				$playlists = $apiresults->playlists;
																}
												}

												if ($error === false) {
																set_transient('vidsy_playlists_' . $vidsy_subdomain, $playlists, 60 * 60);
												}
								} else {
												$error = false;
								}
?>
						<p>
							<label for="<?php
								echo $this->get_field_id('playlistid'); ?>">Select Playlist:</label>
								<?php
								if ($error === true) {
?>
										Error while connecting with Vidsy's servers. Please reload this page and try again.
									<?php
								} else {
?>
							<select id="<?php
												echo $this->get_field_id('playlistid'); ?>" name="<?php
												echo $this->get_field_name('playlistid'); ?>">
								<?php
												foreach ($playlists as $pl) {
																$pl->name = wp_kses($pl->name, array());
																$pl->id = (int)$pl->id;
?>
									<option value="<?php
																echo $pl->id; ?>" <?php
																selected($playlistid, $pl->id); ?>><?php
																echo $pl->name; ?></option>

									<?php
												} ?>
							</select>

									<?php
								}
?>
						</p>
						<p>
							<label for="<?php
								echo $this->get_field_id('theme'); ?>">Select Theme:</label>
							<select id="<?php
								echo $this->get_field_id('theme'); ?>" name="<?php
								echo $this->get_field_name('theme'); ?>">
								<option value="light" <?php
								selected($theme, 'light'); ?>>Light</option>
								<option value="dark" <?php
								selected($theme, 'dark'); ?>>Dark</option>
							</select>
						</p>
						<p>
							<label for="<?php
								echo $this->get_field_id('height'); ?>">Widget height:</label>
							<input id="<?php
								echo $this->get_field_id('height'); ?>" name="<?php
								echo $this->get_field_name('height'); ?>" type="text" value="<?php
								echo $height; ?>" size="3"> pixels.
						</p>
<?php
				}

				function update($new_instance, $old_instance)
				{
								$instance = $old_instance;

								$instance['title'] = strip_tags($new_instance['title']);
								$instance['theme'] = strip_tags($new_instance['theme']);
								$instance['playlistid'] = (int)$new_instance['playlistid'];
								$instance['height'] = (int)$new_instance['height'];
								return $instance;
				}

				function widget($args, $instance)
				{
								extract($args, EXTR_SKIP);

								echo $before_widget;
								$title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
								$theme = empty($instance['theme']) ? ' ' : apply_filters('widget_title', $instance['theme']);
								$playlistid = empty($instance['playlistid']) ? ' ' : apply_filters('widget_title', $instance['playlistid']);
								$height = empty($instance['height']) ? ' ' : apply_filters('widget_title', $instance['height']);

								if (!empty($title)) {
												echo $before_title . $title . $after_title;
								}

								if ($height < 100) {
												$height = 300;
								}

								$playlistid = (int)$playlistid;
								//Vidsy's user data
								$userdata = get_option('vidsy_options');
								$vidsy_subdomain = $userdata['subdomain'];
								$userdata = $userdata['userdata'];

								if (empty($userdata->userid) OR empty($vidsy_subdomain) OR empty($playlistid) OR !is_numeric($playlistid)) {
?>
									<div class="textwidget">Please configure your widget.</div>
									<?php
								} else {
?>
									<script type='text/javascript' src='//<?php
												echo VIDSY_DOMAIN; ?>/public/widgets/playerplaylist.js?userid=<?php
												echo $userdata->userid; ?>&amp;username=<?php
												echo $vidsy_subdomain; ?>&amp;width=100%25&amp;height=<?php
												echo $height; ?>&amp;playlistid=<?php
												echo $playlistid; ?>&amp;theme=<?php
												echo $theme; ?>'></script>
								<?php
								}
								echo $after_widget;
				}
}
add_action('widgets_init', create_function('', 'return register_widget("VidsyWidgetPlayerWithPlaylist");'));
