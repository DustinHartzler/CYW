<?php

if ( !function_exists( 'json_encode' ) ) {
	function json_encode($content, $assoc = false) {
		require_once(dirname( __FILE__ ) . '/json.php');
		if ( $assoc ) {
			$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
		} else {
			$json = new Services_JSON;
		}

		return $json->encode( $content );
	}
}

if ( !function_exists( 'fix_hex_color' ) ) {
	function fix_hex_color($color) {
		if ( strpos( $color, '#', 0 ) === 0 ) {
			return $color;
		}

		return '#' . $color;
	}
}

if ( !class_exists( 'FoobarJSGenerator' ) ) {

	class FoobarJSGenerator {

		function generate($meta, $options, $base_url) {

			if ( !is_array( $meta ) ) return '';

			$backgroundColor = fix_hex_color( FoobarJSGenerator::get_meta( "backgroundColor", $meta ) );
			if ( FoobarJSGenerator::get_meta( "forceTransparent", $meta ) == 'on' ) {
				$backgroundColor = 'transparent';
			}

			$messagesDelay = FoobarJSGenerator::get_meta( "messagesDelay", $meta );
			if ( !empty ($messagesDelay) ) {
				$messagesDelay = intval( $messagesDelay ) * 1000; //convert seconds to milliseconds
			}

			$position = FoobarJSGenerator::get_meta( "positioning", $meta );
			if ( $position == 'fixed' ) {
				$position = 'top';
			}

			$enableShadow = (FoobarJSGenerator::get_meta( "hideShadow", $meta ) == 'on') ? 'false' : 'true';

			$displayDelay = FoobarJSGenerator::get_meta( "displayDelay", $meta ) * 1000;

			$messagesDelay = FoobarJSGenerator::convert_to_milliseconds( FoobarJSGenerator::get_meta( "messagesDelay", $meta ) );

			$messagesScrollDelay = FoobarJSGenerator::convert_to_milliseconds( FoobarJSGenerator::get_meta( "messagesScrollDelay", $meta ) );

			$enableCookie = ((FoobarJSGenerator::get_meta( "enableCookie", $meta ) == 'on') ? 'true' : 'false');

			if ( $enableCookie == 'true' && is_admin() ) {
				$enableCookie = 'false, //forced to be false so it shows in the admin';
			}

			$enableRandomMessage = ((FoobarJSGenerator::get_meta( "enableRandomMessage", $meta ) == 'on') ? 'true' : 'false');

			$disableScrolling = ((FoobarJSGenerator::get_meta( "disableScrolling", $meta ) == 'on') ? 'false' : 'true');

			$ignoreHtmlMarginTop = ((FoobarJSGenerator::get_meta( "ignoreHtmlMarginTop", $meta ) == 'on') ? 'true' : 'false');

			$disablePushDown = ((FoobarJSGenerator::get_meta( "disablePushDown", $meta ) == 'on') ? 'false' : 'true');

			$rtl = ((FoobarJSGenerator::get_meta( "rtl", $meta ) == 'on') ? 'true' : 'false');

			$messages = FoobarJSGenerator::get_meta( "messages", $meta );

			$cookieDuration = FoobarJSGenerator::get_meta( "cookieDuration", $meta );
			if ( empty($cookieDuration) ) {
				$cookieDuration = "20";
			}

			$cookieName = FoobarJSGenerator::get_meta( "cookieName", $meta );
			if ( empty($cookieName) ) {
				$cookieName = "foobar-state";
			}

			$enableNavigation = 'true';

			$navigationTheme = FoobarJSGenerator::get_meta( "navigationTheme", $meta );
			if ( !empty($navigationTheme) && $navigationTheme != 'none' ) {
				$navigationTheme = ',
        "navigation": "' . $navigationTheme . '"';
			} else {
				$enableNavigation = 'false';
				$navigationTheme  = '';
			}

			$button_type = '';
			if ( FoobarJSGenerator::get_meta( "buttonTypeClose", $meta ) == 'on' ) {
				$button_type = ',
        "button": {
          "type": "close"
        }';
			}

			return '
jQuery(function(){
  $foobar({
    "height" : {
      "bar" : ' . FoobarJSGenerator::get_meta2( "height", $meta, "30" ) . ',
      "button" : ' . FoobarJSGenerator::get_meta2( "collapsedButtonHeight", $meta, 30 ) . '
    },
    
    "width": {
      "left": "' . FoobarJSGenerator::get_meta2( "leftWidth", $meta, "*" ) . '",
      "center": "' . FoobarJSGenerator::get_meta2( "centerWidth", $meta, "50%" ) . '",
      "right": "' . FoobarJSGenerator::get_meta2( "rightWidth", $meta, "*" ) . '",
      "button": "' . FoobarJSGenerator::get_meta2( "buttonWidth", $meta, "80px" ) . '"
    },

    "position": {
      "ignoreOffsetMargin": ' . $ignoreHtmlMarginTop . ',
      "bar": "' . $position . '",
      "button": "' . FoobarJSGenerator::get_meta2( "positionClose", $meta, "right" ) . '",
      "social": "' . FoobarJSGenerator::get_meta2( "positionSocial", $meta, "left" ) . '"
    },
    
    "display" : {
      "type" : "' . FoobarJSGenerator::get_meta2( "display", $meta, "expanded" ) . '",
      "delay" : ' . $displayDelay . ',
      "speed": ' . FoobarJSGenerator::get_meta2( "speed", $meta, "200" ) . ',
      "backgroundColor" : "' . $backgroundColor . '",
      "border" : "solid ' . FoobarJSGenerator::get_meta2( "borderSize", $meta, "3" ) . 'px ' . fix_hex_color( FoobarJSGenerator::get_meta2( "borderColor", $meta, "fff" ) ) . '",
      "theme": {
        "bar": "' . FoobarJSGenerator::get_meta2( "buttonTheme", $meta, "triangle-arrow" ) . '"' . $navigationTheme . '
      },
      "easing" : "' . FoobarJSGenerator::get_meta2( "easing", $meta, "swing" ) . '",
      "shadow" : ' . $enableShadow . ',
      "adjustPageHeight" : ' . $disablePushDown . ',
      "rtl" : ' . $rtl . $button_type . '
    },' .

			FoobarJSGenerator::generate_messages( $messages, $meta, $options ) .

			'
			"message": {
			  "delay": ' . $messagesDelay . ',
      "fadeDelay": ' . FoobarJSGenerator::get_meta2( "messagesFadeDelay", $meta, "300" ) . ',
      "random": ' . $enableRandomMessage . ',
      "navigation": ' . $enableNavigation . ',
      "scroll": {
        "enabled": ' . $disableScrolling . ',
        "speed": ' . FoobarJSGenerator::get_meta2( "messagesScrollSpeed", $meta, "50" ) . ',
        "delay": ' . $messagesScrollDelay . ',
        "direction": "left"
      },
      ' . FoobarJSGenerator::generate_styling( $meta ) . '
    }' .

			FoobarJSGenerator::output_if_nd( $meta["leftHtml"], "", ',
    "leftHtml" : "' . FoobarJSGenerator::process_html( $meta["leftHtml"] ) . '"' ) .

			FoobarJSGenerator::output_if_nd( $meta["rightHtml"], "", ',
    "rightHtml" : "' . FoobarJSGenerator::process_html( $meta["rightHtml"] ) . '"' ) .

			FoobarJSGenerator::output_if_nd( $enableCookie, "false", ',
    "cookie": {
      "enabled": ' . $enableCookie . ',
      "name": "' . $cookieName . '",
      "duration": ' . $cookieDuration . '
    }' ) .

			FoobarJSGenerator::generate_social( $meta, $options, $base_url ) .
			FoobarJSGenerator::generate_rss( $meta ) .
			//FoobarJSGenerator::generate_twitter($meta).
			FoobarJSGenerator::generate_ssl() . '
    
  });
  
' . FoobarJSGenerator::get_meta2( "customJS", $meta, "" ) . '
' . FoobarJSGenerator::get_meta2( "custom_js", $options, "" ) . '
});';

		}

		function process_html($input) {
			return addslashes( WPPBUtils::replace_newline( $input ) );
		}

		function generate_styling($meta) {
			$styling = FoobarJSGenerator::get_meta( "styling", $meta );
			if ( $styling == "none" ) {
				return '"cssClass": "__none"';
			} else if ( $styling == "normal" ) {

				if ( $meta["useFontShadow"] == "on" ) {
					$fontShadow = '"shadow" : "0 1px 0 ' . fix_hex_color( $meta["fontShadow"] ) . '"';
				} else {
					$fontShadow = '"shadow" : "none"';
				}

				if ( $meta["useAFontShadow"] == "on" ) {
					$aFontShadow = '"shadow" : "0 1px 0 ' . fix_hex_color( $meta["aFontShadow"] ) . '"';
				} else {
					$aFontShadow = '"shadow" : "none"';
				}

				if ( $meta["useAHoverFontShadow"] == "on" ) {
					$aHoverFontShadow = '"shadow" : "0 1px 0 ' . fix_hex_color( $meta["aHoverFontShadow"] ) . '"';
				} else {
					$aHoverFontShadow = '"shadow" : "none"';
				}

				return '"font": {
        "family": "' . $meta["fontFamily"] . '",
        "size": "' . $meta["fontSize"] . 'pt",
        "color": "' . fix_hex_color( $meta["fontColor"] ) . '",
        ' . $fontShadow . '
      },
      "aFont": {
        "family": "' . $meta["aFontFamily"] . '",
        "size": "' . $meta["aFontSize"] . 'pt",
        "color": "' . fix_hex_color( $meta["aFontColor"] ) . '",
        "decoration": "' . $meta["aFontDecoration"] . '",
        ' . $aFontShadow . ',
        "hover": {
          "color": "' . fix_hex_color( $meta["aHoverFontColor"] ) . '",
          "decoration": "' . $meta["aHoverFontDecoration"] . '",
          ' . $aHoverFontShadow . '
        }
      }';

			} else {
				return '"cssClass": "' . $meta["messageClass"] . '"';
			}
		}

		function generate_messages($messages, $meta, $options) {
			if ( is_array( $messages ) ) {
				$message_js = '
    "messages": [';
				foreach ( $messages as $message ) {
					if ( strlen( $message ) == 0 ) continue;
					$message_js .= ('
      "' . FoobarJSGenerator::process_html( $message ) . '",');
				}

				if ( array_key_exists( "twitterEnabled", $meta ) && $meta["twitterEnabled"] == "on" ) {

					require_once 'OAuth.php';
					require_once 'twitteroauth.php';
					require_once 'FooTweetFetcher.php';

					$tf     = new FooTweetFetcher($options['twitter_consumer_key'], $options['twitter_consumer_secret'], $options['twitter_access_key'], $options['twitter_access_secret']);
					$args   = array(
						'limit'            => $meta["twitterMaxTweets"],
						'include_retweets' => 0,
						'exclude_replies'  => 1
					);
					$tweets = $tf->get_tweets( $meta["twitterUser"], $args );
					if ( $tweets !== false && is_array( $tweets ) && (count( $tweets ) > 0) ) {
						foreach ( $tweets as $tweet ) {
							$text = $tf->make_clickable( $tweet );
							$message_js .= '
		"' . FoobarJSGenerator::process_html( $text ) . '",';
						}
					}
				}

				if ( WPPBUtils::ends_with( $message_js, ',' ) ) {
					//cut off last char
					$message_js = substr( $message_js, 0, -1 );
				}
				$message_js .= '
    ],';

				return $message_js;
			} else {
				return '';
			}
		}

		function generate_social($meta, $options, $base_url) {

			if ( $meta["positionSocial"] == 'hidden' ) {
				return '';
			}

			$img_src_url_base = $base_url . 'images/social/';

			if ( FoobarJSGenerator::get_meta( "socialUseFontShadow", $meta ) == "on" ) {
				$shadow = '"shadow" : "0 1px 0 ' . fix_hex_color( FoobarJSGenerator::get_meta( "socialFontShadow", $meta ) ) . '"';
			} else {
				$shadow = '"shadow" : "none"';
			}

			$socialFontFamily = FoobarJSGenerator::get_meta( "socialFontFamily", $meta );
			if ( empty($socialFontFamily) ) {
				$socialFontFamily = 'Verdana';
			}

			$socialFontSize = FoobarJSGenerator::get_meta( "socialFontSize", $meta );
			if ( empty($socialFontSize) ) {
				$socialFontSize = '10';
			}

			$socialFontColor = FoobarJSGenerator::get_meta( "socialFontColor", $meta );
			if ( empty($socialFontColor) ) {
				$socialFontColor = '#fff';
			}

			$social_js = ',
    "social" : {
      "text" : "' . FoobarJSGenerator::get_meta( "socialText", $meta ) . '",
      "font": {
        "family": "' . $socialFontFamily . '",
        "size": "' . $socialFontSize . 'pt",
        "color": "' . fix_hex_color( $socialFontColor ) . '",
        ' . $shadow . '
      }';

			if ( FoobarJSGenerator::get_meta( "overrideSocial", $meta ) == "on" ) {
				//load the profiles specific to this foobar
				$socials = $meta["socials"];
			} else {
				//load the default profiles from the plugin settings
				if ( is_array( $options ) && array_key_exists( 'socials', $options ) ) {
					$socials = $options['socials'];
				}
			}

			if ( isset($socials) && is_array( $socials ) ) {
				$social_js .= ',
      "profiles": [';
				foreach ( $socials as $profile ) {
					$social_img_icon = $profile['icon'];

					if ( !empty($social_img_icon) && !empty($profile["name"]) && !empty($profile["url"]) ) {
						if ( !FoobarJSGenerator::str_contains( $social_img_icon, '://' ) ) {
							$social_img_icon = $img_src_url_base . $social_img_icon;
						}

						$social_js .= ('
          {"name" : "' . str_replace( '"', '\"', $profile["name"] ) . '",');
						$social_js .= ('"url" : "' . str_replace( '"', '\"', $profile["url"] ) . '",');
						$social_js .= ('"image" : "' . str_replace( '"', '\"', $social_img_icon ) . '",');
						$social_js .= ('"target" : "_blank"},');
					}
				}
				if ( WPPBUtils::ends_with( $social_js, ',' ) ) {
					//cut off last char
					$message_js = substr( $social_js, 0, -1 );
				}
				$social_js .= '
      ]';
			} else {
				//no socials so render nothing
				return '';
			}
			$social_js .= '
    }';

			return $social_js;
		}

		function generate_ssl() {
			if ( is_ssl() ) {
				return ',
    "ssl" : true';
			}
		}

		function generate_rss($meta) {
			if ( array_key_exists( "rssEnabled", $meta ) && $meta["rssEnabled"] == "on" ) {
				return ',
    "rss" : {
      "enabled": true,
      "url": "' . $meta["rssUrl"] . '",
      "maxResults":' . $meta["rssMaxResults"] . ',
      "linkText": "' . $meta["rssLinkText"] . '",
      "linkTarget": "' . $meta["rssLinkTarget"] . '"
    }';
			} else {
				return '';
			}
		}

		function generate_twitter($meta) {
			if ( array_key_exists( "twitterEnabled", $meta ) && $meta["twitterEnabled"] == "on" ) {
				return ',
    "twitter" : {
      "enabled": true,
      "user": "' . $meta["twitterUser"] . '",
      "maxTweets":' . $meta["twitterMaxTweets"] . '
    }';
			} else {
				return '';
			}
		}

		function output_if($var, $text) {
			if ( empty($var) ) {
				return '';
			} else {
				return $text;
			}
		}

		//output the text if the value is not the default
		function output_if_nd($var, $default, $text) {
			if ( !isset($var) ) {
				return '';
			} else {
				if ( $var == $default ) {
					return '';
				} else {
					return $text;
				}
			}
		}

		function convert_to_milliseconds($seconds) {
			if ( !empty ($seconds) ) {
				return intval( $seconds ) * 1000; //convert seconds to milliseconds
			}

			return $seconds;
		}

		function str_contains($haystack, $needle) {
			if ( empty($haystack) || empty($needle) ) {
				return false;
			}

			$pos = strpos( strtolower( $haystack ), strtolower( $needle ) );

			if ( $pos === false ) {
				return false;
			} else {
				return true;
			}
		}

		function get_meta($key, $meta) {
			if ( !is_array( $meta ) ) return null;
			if ( !array_key_exists( $key, $meta ) ) return null;

			return $meta[$key];
		}

		function get_meta2($key, $meta, $default) {
			$value = FoobarJSGenerator::get_meta( $key, $meta );
			if ( $value === null ) return $default;

			return $value;
		}
	}

}

?>