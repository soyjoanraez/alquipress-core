/**
 * File fronend-pro.js
 *
 * Handles toggling the navigation menu for Addon widget
 *
 * @package astra-addon
 */

astraToggleSetupPro = function( mobileHeaderType, body, menu_click_listeners ) {

	var flag = false;
	var menuToggleAllLength;

	if ( 'off-canvas' === mobileHeaderType || 'full-width' === mobileHeaderType ) {
        // comma separated selector added, if menu is outside of Off-Canvas then submenu is not clickable, it work only for Off-Canvas area with dropdown style.
        var __main_header_all = document.querySelectorAll( '#ast-mobile-popup, #ast-mobile-header' );
        if ( body.classList.contains('ast-header-break-point') ) {

            var menu_toggle_all   = document.querySelectorAll( '#ast-mobile-header .main-header-menu-toggle' );
        } else {
            menu_toggle_all   = document.querySelectorAll( '#ast-desktop-header .main-header-menu-toggle' );
		}
		menuToggleAllLength = menu_toggle_all.length;
    } else {

		if ( body.classList.contains('ast-header-break-point') ) {

			var __main_header_all = document.querySelectorAll( '#ast-mobile-header' ),
				menu_toggle_all   = document.querySelectorAll( '#ast-mobile-header .main-header-menu-toggle' );
				menuToggleAllLength = menu_toggle_all.length;
				flag = menuToggleAllLength > 0 ? false : true;
				menuToggleAllLength = flag ? 1 : menuToggleAllLength;
		} else {

			var __main_header_all = document.querySelectorAll( '#ast-desktop-header' ),
				menu_toggle_all = document.querySelectorAll('#ast-desktop-header .main-header-menu-toggle');
				menuToggleAllLength = menu_toggle_all.length;
		}
	}

	if ( menuToggleAllLength > 0 || flag ) {

        for (var i = 0; i < menuToggleAllLength; i++) {

			if ( !flag ) {
				menu_toggle_all[i].setAttribute('data-index', i);

				if (!menu_click_listeners[i]) {
					menu_click_listeners[i] = menu_toggle_all[i];
					menu_toggle_all[i].removeEventListener('click', astraNavMenuToggle);
					menu_toggle_all[i].addEventListener('click', astraNavMenuToggle, false);
				}
			}

            if ('undefined' !== typeof __main_header_all[i]) {

                // To handle the comma seprated selector added above we need this loop.
                for( var mainHeaderCount =0; mainHeaderCount  < __main_header_all.length; mainHeaderCount++ ){

                    if (document.querySelector('header.site-header').classList.contains('ast-builder-menu-toggle-link')) {
                        var astra_menu_toggle = __main_header_all[mainHeaderCount].querySelectorAll('ul.main-header-menu .menu-item-has-children > .menu-link, ul.main-header-menu .ast-menu-toggle');
                    } else {
                        var astra_menu_toggle = __main_header_all[mainHeaderCount].querySelectorAll('ul.main-header-menu .ast-menu-toggle');
                    }
                    // Add Eventlisteners for Submenu.
                    if (astra_menu_toggle.length > 0) {

                        for (var j = 0; j < astra_menu_toggle.length; j++) {
                            astra_menu_toggle[j].removeEventListener('click', AstraToggleSubMenu);
                            astra_menu_toggle[j].addEventListener('click', AstraToggleSubMenu, false);
                        }
                    }
                }
            }
        }
    }
}

astraNavMenuTogglePro = function ( event, body, mobileHeaderType, thisObj ) {

    event.preventDefault();

    var desktop_header = event.target.closest('#ast-desktop-header');

    var desktop_header_content = document.querySelector('#masthead > #ast-desktop-header .ast-desktop-header-content');

    if ( null !== desktop_header && undefined !== desktop_header && '' !== desktop_header ) {

        var desktop_toggle = desktop_header.querySelector( '.main-header-menu-toggle' );
    } else {
        var desktop_toggle = document.querySelector('#masthead > #ast-desktop-header .main-header-menu-toggle');
    }

    var desktop_menu = document.querySelector('#masthead > #ast-desktop-header .ast-desktop-header-content .main-header-bar-navigation');

    if ( 'desktop' === event.currentTarget.trigger_type ) {

        if ( null !== desktop_menu && '' !== desktop_menu && undefined !== desktop_menu ) {
            astraToggleClass(desktop_menu, 'toggle-on');
            if (desktop_menu.classList.contains('toggle-on')) {
                desktop_menu.style.display = 'block';
            } else {
                desktop_menu.style.display = '';
            }
        }
        astraToggleClass(desktop_toggle, 'toggled');
        if ( desktop_toggle.classList.contains( 'toggled' ) ) {
            body.classList.add("ast-main-header-nav-open");
            if ( 'dropdown' === mobileHeaderType ) {
                desktop_header_content.style.display = 'block';
            }
        } else {
            body.classList.remove("ast-main-header-nav-open");
            desktop_header_content.style.display = 'none';
        }
        return;
    }

    var __main_header_all = document.querySelectorAll('#masthead > #ast-mobile-header .main-header-bar-navigation');
    menu_toggle_all 	 = document.querySelectorAll( '#masthead > #ast-mobile-header .main-header-menu-toggle' )
    var event_index = '0';
    var sticky_header = false;
    if ( null !== thisObj.closest( '#ast-fixed-header' ) ) {

        __main_header_all = document.querySelectorAll('#ast-fixed-header > #ast-mobile-header .main-header-bar-navigation');
        menu_toggle_all 	 = document.querySelectorAll( '#ast-fixed-header .main-header-menu-toggle' )

        event_index = '0';
        sticky_header = true;

    }

    if ('undefined' === typeof __main_header_all[event_index]) {
        return false;
    }
    var menuHasChildren = __main_header_all[event_index].querySelectorAll('.menu-item-has-children');
    for (var i = 0; i < menuHasChildren.length; i++) {
        menuHasChildren[i].classList.remove('ast-submenu-expanded');
        var menuHasChildrenSubMenu = menuHasChildren[i].querySelectorAll('.sub-menu');
        for (var j = 0; j < menuHasChildrenSubMenu.length; j++) {
            menuHasChildrenSubMenu[j].style.display = 'none';
        }
    }

    var menu_class = thisObj.getAttribute('class') || '';

    if ( menu_class.indexOf('main-header-menu-toggle') !== -1 ) {
        astraToggleClass(__main_header_all[event_index], 'toggle-on');
        astraToggleClass(menu_toggle_all[event_index], 'toggled');
        if ( sticky_header && 1 < menu_toggle_all.length ) {
            astraToggleClass(menu_toggle_all['1'], 'toggled');
        }
        if (__main_header_all[event_index].classList.contains('toggle-on')) {
            __main_header_all[event_index].style.display = 'block';
            body.classList.add("ast-main-header-nav-open");
        } else {
            __main_header_all[event_index].style.display = '';
            body.classList.remove("ast-main-header-nav-open");
        }
    }
}

const accountMenuToggle = function () {
    const checkAccountActionTypeCondition = astraAddon.hf_account_action_type && 'menu' === astraAddon.hf_account_action_type;
    const accountMenuClickCondition = checkAccountActionTypeCondition && astraAddon.hf_account_show_menu_on && 'click' === astraAddon.hf_account_show_menu_on;

    const headerAccountContainer = document.querySelectorAll('.ast-header-account-wrap');

    if(  headerAccountContainer ) {

        headerAccountContainer.forEach(element => {

            const accountMenu = element.querySelector('.ast-account-nav-menu');

            const handlePointerUp = function( e ) {
                const condition = ( accountMenuClickCondition ) || ( checkAccountActionTypeCondition && document.querySelector('body').classList.contains('ast-header-break-point'));
                if( condition ) {
                    // if the target of the click isn't the container nor a descendant of the container
                    if ( accountMenu && !element.contains( e.target ) ) {
                        accountMenu.style.right = '';
                        accountMenu.style.left = '';
                    }
                }
            };

            // Attach pointerup event listener only once.
            if ( ! element._accountPointerUpHandler ) {
                element._accountPointerUpHandler = handlePointerUp;
                document.addEventListener('pointerup', handlePointerUp);
            }

            const headerAccountTrigger =  element.querySelector( '.ast-header-account-link' );
            if( headerAccountTrigger ) {
                const handleAccountClick = function( e ) {
                    const condition = ( accountMenuClickCondition ) || ( checkAccountActionTypeCondition && document.querySelector('body').classList.contains('ast-header-break-point'));
                    if( condition ) {

                        headerSelectionPosition = e.target.closest('.site-header-section');

                        if( headerSelectionPosition ) {
                            if( headerSelectionPosition.classList.contains('site-header-section-left') ) {
                                accountMenu.style.left   = accountMenu.style.left  === '' ? '-100%' : '';
                                accountMenu.style.right   = accountMenu.style.right  === '' ? 'auto' : '';
                            } else {
                                accountMenu.style.right   = accountMenu.style.right  === '' ? '-100%' : '';
                                accountMenu.style.left   = accountMenu.style.left  === '' ? 'auto' : '';
                            }
                        }
                    }
                };

                // Attach click event listener only once.
                if ( ! headerAccountTrigger._accountClickHandler ) {
                    headerAccountTrigger._accountClickHandler = handleAccountClick;
                    headerAccountTrigger.addEventListener( 'click', handleAccountClick);
                }
            }
        });
    }
}

/**
 * Color Switcher.
 *
 * @since 4.10.0
 */
const astraColorSwitcher = {
	...astraAddon?.colorSwitcher, // Spreading Color Switcher options.

	/**
	 * Initializes the Color Switcher Widget.
	 */
	init: function () {
		if ( ! this?.isInit ) {
			return;
		}

		this.switcherButtons = document.querySelectorAll( '.ast-builder-color-switcher .ast-switcher-button' );

		if ( ! this.switcherButtons?.length ) {
			return;
		}

		this.switcherButtons?.forEach( ( switcherButton ) => {
			switcherButton?.addEventListener( 'click', this.toggle ); // ✅ `this` refers to astraColorSwitcher
		} );

		if ( this.isDarkPalette && this.defaultMode === 'system' ) {
			// Detect system preference and apply mode accordingly.
			this.detectSystemColorScheme();
		}

		// Set initial logo state if switched
		if ( this.isSwitched ) {
			this.switchLogo();
		}
	},

	/**
	 * Detects the system's color scheme preference and sets the theme accordingly.
	 */
	detectSystemColorScheme: function () {
		const storedPreference = this.getCookie( 'astraColorSwitcherState' );

		// Bail early, if user has previously chosen a theme.
		if ( storedPreference !== null ) {
			return;
		}

		// Detect system preference.
		const prefersDark = window.matchMedia( '(prefers-color-scheme: dark)' ).matches;

		if ( prefersDark && ! this.isSwitched ) {
			// Apply the detected or stored theme.
			this.toggle();
		}
	},

	/**
	 * Toggle the palette.
	 *
	 * @param {Event} e Button click event object.
	 */
	toggle: function ( e ) {
		e?.preventDefault();
		const switcher = astraColorSwitcher;

		// Toggle the state
		switcher.isSwitched = ! switcher.isSwitched;

		// Store state in cookie (expires in 90 days).
		switcher.setCookie( 'astraColorSwitcherState', switcher.isSwitched, 90 );

		if ( switcher?.forceReload ) {
			window.location.reload();
			return;
		}

		switcher.switchPaletteColors();
		switcher.switchIcon();
		switcher.switchLogo();

		if ( switcher.isDarkPalette ) {
			switcher.handleDarkModeCompatibility();
		}
	},

	/**
	 * Switch Palette Colors.
	 */
	switchPaletteColors: function () {
		// Choose the correct palette based on `isSwitched` state.
		const currentPalette = this.isSwitched ? this?.palettes?.switched : this?.palettes?.default;

		// Apply the colors to CSS variables.
		currentPalette?.forEach( ( color, index ) => {
			document.documentElement.style.setProperty( `--ast-global-color-${ index }`, color );
			if ( astraAddon?.is_elementor_active ) {
				document.documentElement.style.setProperty( `--e-global-color-astglobalcolor${ index }`, color );
			}
		} );
	},

	/**
	 * Switch Icon.
	 */
	switchIcon: function () {
		this.switcherButtons?.forEach( ( switcherButton ) => {
			const [ defaultIcon, switchedIcon ] = switcherButton?.querySelectorAll( '.ast-switcher-icon' );

			// Avoid icon switching if there is none or only one.
			if ( defaultIcon && switchedIcon ) {
				const [ first, second ] = this.isSwitched ? [ switchedIcon, defaultIcon ] : [ defaultIcon, switchedIcon ];

				// Animate icon.
				switcherButton?.classList.add( 'ast-animate' );

				setTimeout( () => {
					first?.classList.add( 'ast-current' );
					second?.classList.remove( 'ast-current' );
				}, 100 );

				setTimeout( () => switcherButton?.classList.remove( 'ast-animate' ), 200 );
			}

			/// Switch aria attribute.
			const ariaLabelTextKey = this.isSwitched ? 'defaultText' : 'switchedText';
			switcherButton?.setAttribute(
				'aria-label',
				switcherButton?.dataset?.[ ariaLabelTextKey ] || 'Switch color palette.'
			);
		} );
	},

	/**
	 * Switch Logo.
	 */
	switchLogo: function () {
		// Handle color switcher logo switching
		if ( this.isDarkPalette && this?.logos?.switched && this?.logos?.default ) {
			this.switchColorSwitcherLogo();
		}
	},

	/**
	 * Switch Color Switcher Logo.
	 * Handles logo switching for dark/light palette modes.
	 */
	switchColorSwitcherLogo: function () {
		// Target only main logo, exclude sticky header and transparent header logos
		const logoSelectors = [
			'.custom-logo-link:not(.sticky-custom-logo):not(.transparent-custom-logo) .custom-logo',  // Main logo only
			'.site-branding .site-logo-img img:not(.ast-sticky-header-logo)',  // Main site logo, not sticky
			'.ast-site-identity .site-logo-img img:not(.ast-sticky-header-logo)', // Alternative main logo structure
		];

		let logoImages = [];
		
		// Try each selector to find main logo images only
		for ( const selector of logoSelectors ) {
			const foundImages = document.querySelectorAll( selector );
			if ( foundImages.length > 0 ) {
				// Filter out sticky and transparent header logos if they somehow get selected
				logoImages = Array.from( foundImages).filter( ( img ) => {
					// Exclude if parent contains sticky header or transparent header classes
					return ! img.closest( '.ast-sticky-header-logo' ) && 
						   ! img.closest( '.sticky-custom-logo' ) &&
						   ! img.closest( '.transparent-custom-logo' ) &&
						   ! img.classList.contains( 'ast-sticky-header-logo' );
				} );

				if ( logoImages.length > 0 ) {
					break;
				}
			}
		}

		if ( ! logoImages.length ) {
			return;
		}

		// Determine which logo to show based on current state
		const targetSrc = this.isSwitched ? this.logos.switched : this.logos.default;
		
		if ( ! targetSrc ) {
			return;
		}

		// Update each logo image
		this.updateLogoImages( logoImages, targetSrc );
	},

	/**
	 * Update Logo Images.
	 */
	updateLogoImages: function ( logoImages, targetSrc ) {
		logoImages.forEach( ( logoImg ) => {
			if ( logoImg && logoImg.src !== targetSrc ) {
				// Preload image for smoother switching
				const newImg = new Image();
				newImg.onload = function() {
					logoImg.src = targetSrc;
					if ( logoImg.hasAttribute ( 'srcset' ) ) {
						logoImg.removeAttribute( 'srcset' );
					}
					if ( logoImg.hasAttribute( 'data-src' ) ) {
						logoImg.setAttribute( 'data-src', targetSrc );
					}
				};
				newImg.onerror = function() {
					logoImg.src = targetSrc; // Try anyway
				};
				newImg.src = targetSrc;
			}
		} );
	},

	/**
	 * Handle Dark Mode Compatibility.
	 */
	handleDarkModeCompatibility: function () {
		// Add the dark mode class.
		document.body.classList.toggle( 'astra-dark-mode-enable' );

		// Todo: Handle dark compatibility CSS.
	},

	/**
	 * Helper function to set a cookie.
	 */
	setCookie: ( name, value, days ) => {
		const expires = new Date();
		expires.setTime( expires.getTime() + days * 24 * 60 * 60 * 1000 );
		document.cookie = `${ name }=${ value }; expires=${ expires.toUTCString() }; path=/`;
	},

	/**
	 * Helper function to get a cookie.
	 */
	getCookie: ( name ) => {
		const cookies = document.cookie.split( '; ' );
		for ( let cookie of cookies ) {
			const [ key, val ] = cookie.split( '=' );
			if ( key === name ) return val;
		}
		return null;
	},
};

/**
 * Account Login Popup Trigger
 *
 * Moved from theme's JS to addon to ensure the login popup JS always loads with the account component.
 * Fixes cases where the JS was missing when the widget was added due to theme script loading order.
 *
 * @since 4.11.5 Moved from theme to addon
 */
var accountPopupTrigger = function () {
	if ( typeof astraAddon === 'undefined' || 'login' !== astraAddon.hf_account_logout_action ) {
		return;
	}

	// Account login form popup.
	var header_account_trigger =  document.querySelectorAll( '.ast-account-action-login' );

	if (!header_account_trigger.length) {
		return;
	}

	const formWrapper = document.querySelector('#ast-hb-account-login-wrap');

	if (!formWrapper) {
		return;
	}

	const formCloseBtn = document.querySelector('#ast-hb-login-close');

	header_account_trigger.forEach(function(_trigger) {
		_trigger.addEventListener('click', function(e) {
			e.preventDefault();

			formWrapper.classList.add('show');
		});
	});

	if (formCloseBtn) {
		formCloseBtn.addEventListener('click', function(e) {
			e.preventDefault();
			formWrapper.classList.remove('show');
		});
	}
};

document.addEventListener( 'astPartialContentRendered', function() {
    accountMenuToggle();
    accountPopupTrigger();
});

window.addEventListener( 'load', function() {
    accountMenuToggle();
    accountPopupTrigger();
    astraColorSwitcher.init();
} );

document.addEventListener( 'astLayoutWidthChanged', function() {
    accountMenuToggle();
    accountPopupTrigger();
} );

// Fix: Sync toggle button state when anchor links close the menu.
document.addEventListener('click', function(e) {
	let target = e.target.closest('a');
	let href = target && target.getAttribute('href');
	if ( href && href.indexOf('#') !== -1 &&
		 (target.closest('.main-header-bar-navigation') || target.closest('.ast-mobile-header-content') || target.closest('.ast-desktop-header-content')) ) {
		setTimeout(function() {
			let allToggleButtons = document.querySelectorAll('.menu-toggle');
			let menuContent = document.querySelector('.main-header-bar-navigation');
			if ( menuContent && !menuContent.classList.contains('toggle-on') ) {
				allToggleButtons.forEach(function(button) {
					button.classList.remove('toggled');
					button.setAttribute('aria-expanded', 'false');
				});
			}
		}, 10);
	}
});
/**
 * Stick elements
 *
 * => How to use?
 *
 * jQuery( {SELECTOR} ).astHookExtSticky( {
 *		dependent: [{selectors}], 	// Not required. Default: []. Stick element dependent selectors.
 *		stick_upto_scroll: {value}, 	// Not required. Default: 0. Stick element after scroll upto the {value} in px.
 *		gutter: {value}, 			// Not required. Default: 0. Stick element from top of the window in px\.
 * });
 *
 * @package Astra Addon
 * @since  1.0.0
 */

;(function ( $, window, undefined ) {

	var pluginName    = 'astHookExtSticky',
		document      = window.document,
		windowWidth   = jQuery( window ).outerWidth(),
		viewPortWidth = jQuery( window ).width(),
		defaults      = {
			dependent            : [],
			max_width            : '',
			site_layout          : '',
			break_point          : 920,
			admin_bar_height_lg  : 32,
			admin_bar_height_sm  : 46,
			admin_bar_height_xs  : 0,
			stick_upto_scroll    : 0,
			gutter               : 0,
			wrap                 : '<div></div>',

			// Padding support of <body> tag.
			body_padding_support : true,

			// Padding support of <html> tag.
			html_padding_support : true,

			active_shrink : false,
			// Added shrink option.
			shrink               : {
									padding_top    : '',
									padding_bottom : '',
						    	},

			// Enable sticky on mobile
			sticky_on_device 	 : 'desktop',

			header_style 		 : 'none',

			hide_on_scroll 		 : 'no',
		},
		/* Manage hide on scroll down */
		lastScrollTop 		= 0,
		delta 				= 5,
		navbarHeight 		= 0,
		should_stick		= true,
		hideScrollInterval;

	/**
	 * Init
	 *
	 * @since  1.0.0
	 */
	function astHookExtSticky( element, options ) {
		this.element   = element;
		this.options   = $.extend( {}, defaults, options );
		this._defaults = defaults;
		this._name     = pluginName;

		/* Manage hide on scroll down */
		if ( '1' == this.options.hide_on_scroll ) {
			this.navbarHeight = $(element).outerHeight();
		}

		this.lastScrollTop 		= 0;
		this.delta 				= 5;
		this.should_stick		= true;
		this.hideScrollInterval = '';

		this.init();
	}

	/**
	 * Stick element
	 *
	 * @since  1.0.0
	 */
	astHookExtSticky.prototype.stick_me = function( self, type ) {

		var selector      	  = jQuery( self.element ),
			windowWidth       = jQuery( window ).outerWidth(),
			stick_upto_scroll = parseInt( self.options.stick_upto_scroll ),
			max_width         = parseInt( selector.parent().attr( 'data-stick-maxwidth' ) ), // parseInt( self.options.max_width ),
			gutter            = parseInt( selector.parent().attr( 'data-stick-gutter' ) ); // parseInt( self.options.gutter ).
		/**
		 * Check window width
		 */
		 var hook_sticky_header = astraAddon.hook_sticky_header || '';
		 // Any stick header is enabled?
		 if ( 'enabled' == hook_sticky_header ) {
			if ( ( 'desktop' == self.options.sticky_on_device && astraAddon.hook_custom_header_break_point > windowWidth ) ||
				 ( 'mobile' == self.options.sticky_on_device && astraAddon.hook_custom_header_break_point <= windowWidth ) ) {
				self.stickRelease( self );
			} else {
				if ( jQuery( window ).scrollTop() > stick_upto_scroll ) {
				
					if ( 'none' == self.options.header_style ) { 
						if ( 'enabled' == self.options.active_shrink ) {
							self.hasShrink( self, 'stick' );
							var topValue = 'none'; // Default value for 'top' property
							if ( !selector.hasClass( 'ast-custom-header' ) ) {
								topValue = gutter; // If it's not the specified class, set 'top' to 'gutter'
							}
							selector.parent().css( 'min-height', selector.outerHeight() );
							selector.addClass( 'ast-header-sticky-active' ).stop().css( {
								'max-width': max_width,
								'top': topValue, // Setting 'top' property based on the condition
								'padding-top': self.options.shrink.padding_top,
								'padding-bottom': self.options.shrink.padding_bottom,
							} );
							selector.addClass( 'ast-sticky-shrunk' ).stop();
						} else {
							self.hasShrink( self, 'stick' );							
							selector.parent().css( 'min-height', selector.outerHeight() );
							selector.addClass( 'ast-header-sticky-active' ).stop().css( {
								'max-width': max_width,
								'top': gutter, 
								'padding-top': self.options.shrink.padding_top,
								'padding-bottom': self.options.shrink.padding_bottom,
							} );
							selector.addClass( 'ast-sticky-shrunk' ).stop();
						}
					}
					
				} else {
					self.stickRelease( self );
				}
			}
		}

		var hook_sticky_footer = astraAddon.hook_sticky_footer || '';
		// Any stick header is enabled?
		if ( 'enabled' == hook_sticky_footer ) {

			if ( 
				( 'desktop' == self.options.sticky_on_device && astraAddon.hook_custom_footer_break_point > windowWidth ) ||
				( 'mobile' == self.options.sticky_on_device && astraAddon.hook_custom_footer_break_point <= windowWidth )
			) {
				self.stickRelease( self );
			} 
			else{
				jQuery( 'body' ).addClass( 'ast-footer-sticky-active' );
				selector.parent().css( 'min-height', selector.outerHeight() );
				selector.stop().css({
					'max-width' : max_width,
				});
			}
		}
	}

	astHookExtSticky.prototype.update_attrs = function () {

		var self  	          = this,
			selector          = jQuery( self.element ),
			gutter            = parseInt( self.options.gutter ),
			max_width         = self.options.max_width;

		if ( 'none' == self.options.header_style ) {
			var stick_upto_scroll = selector.offset().top || 0;
		}

		/**
		 * Update Max-Width
		 */
		if ( 'ast-box-layout' != self.options.site_layout ) {
			max_width = jQuery( 'body' ).width();
		}

		/**
		 * Check dependent element
		 * - Is exist?
		 * - Has attr 'data-stick-support' with status 'on'
		 */
		if ( self.options.dependent ) {
			jQuery.each( self.options.dependent, function(index, val) {
				if (
					( jQuery( val ).length ) &&
					( jQuery( val ).parent().attr( 'data-stick-support' ) == 'on' )
				) {
					dependent_height   = jQuery( val ).outerHeight();
					gutter            += parseInt( dependent_height );
					stick_upto_scroll -= parseInt( dependent_height );
				}
			});
		}

		/**
		 * Add support for Admin bar height
		 */
		if ( self.options.admin_bar_height_lg && jQuery( '#wpadminbar' ).length && viewPortWidth > 782 ) {
			gutter            += parseInt( self.options.admin_bar_height_lg );
			stick_upto_scroll -= parseInt( self.options.admin_bar_height_lg );
		}

		if ( self.options.admin_bar_height_sm && jQuery( '#wpadminbar' ).length && ( viewPortWidth >= 600 && viewPortWidth <= 782 ) ) {
			gutter            += parseInt( self.options.admin_bar_height_sm );
			stick_upto_scroll -= parseInt( self.options.admin_bar_height_sm );
		}

		if( self.options.admin_bar_height_xs && jQuery( '#wpadminbar' ).length ){
			gutter            += parseInt( self.options.admin_bar_height_xs );
			stick_upto_scroll -= parseInt( self.options.admin_bar_height_xs );
		}

		/**
		 * Add support for <body> tag
		 */
		if ( self.options.body_padding_support ) {
			gutter            += parseInt( jQuery( 'body' ).css( 'padding-top' ), 10 );
			stick_upto_scroll -= parseInt( jQuery( 'body' ).css( 'padding-top' ), 10 );
		}

		/**
		 * Add support for <html> tag
		 */
		if ( self.options.html_padding_support ) {
			gutter            += parseInt( jQuery( 'html' ).css( 'padding-top' ), 10 );
			stick_upto_scroll -= parseInt( jQuery( 'html' ).css( 'padding-top' ), 10 );
		}

		/**
		 * Updated vars
		 */
		self.options.stick_upto_scroll = stick_upto_scroll;

		/**
		 * Update Attributes
		 */
		if ( 'none' == self.options.header_style ) {
			selector.parent()
				.css( 'min-height', selector.outerHeight() )
				.attr( 'data-stick-gutter', parseInt( gutter ) )
				.attr( 'data-stick-maxwidth', parseInt( max_width ) );
		}
	}

	astHookExtSticky.prototype.hasShrink = function( self, method ) {
		
		var st = $( window ).scrollTop();

	    // If they scrolled down and are past the navbar, add class .nav-up.
	    // This is necessary so you never see what is "behind" the navbar.
		var fixed_header = jQuery(self.element);
	    if ( st > fixed_header.outerHeight() ){
	        // Active Shrink
	        jQuery('body').addClass('ast-shrink-custom-header');
	    } else {
	        // Remove Shrink effect
	        jQuery('body').removeClass('ast-shrink-custom-header');
	    }
	}

	astHookExtSticky.prototype.stickRelease = function( self ) {
		var selector = jQuery( self.element );
		
		var hook_sticky_header = astraAddon.hook_sticky_header || '';
		 // Any stick header is enabled?
		if ( 'enabled' == hook_sticky_header ) {
			if ( 'none' == self.options.header_style ) {
				selector.removeClass( 'ast-header-sticky-active' ).stop().css({
					'max-width' : '',
					'top'		: '',
					'padding'	: '',
				});
				selector.parent().css( 'min-height', '' );
				selector.removeClass( 'ast-sticky-shrunk' ).stop();
			}
		}

		var hook_sticky_footer = astraAddon.hook_sticky_footer || '';
		 // Any stick footer is enabled?
		if ( 'enabled' == hook_sticky_footer ) {
			jQuery( 'body' ).removeClass( 'ast-footer-sticky-active' );
		}
	}
	/**
	 * Init Prototype
	 *
	 * @since  1.0.0
	 */
	astHookExtSticky.prototype.init = function () {

		/**
		 * If custom stick options are set
		 */
		if ( jQuery( this.element ) ) {

			var self                       	   = this,
				selector                       = jQuery( self.element ),
				gutter                         = parseInt( self.options.gutter ),
				stick_upto_scroll              = selector.position().top || 0,
				dependent_height               = 0;

			/**
			 *	Add parent <div> wrapper with height element for smooth scroll
			 *
			 *	Added 'data-stick-support' to all sticky elements
			 *	To know the {dependent} element has support of 'stick'
			 */
			 if ( 'none' == self.options.header_style ) {
				selector.wrap( self.options.wrap )
					.parent().css( 'min-height', selector.outerHeight() )
					.attr( 'data-stick-support', 'on' )
					.attr( 'data-stick-maxwidth', parseInt( self.options.max_width ) );
			}

			self.update_attrs();

			// Stick me!.
			jQuery( window ).on('resize', function() {

				self.stickRelease( self );
				self.update_attrs();
				self.stick_me( self );
			} );

			jQuery( window ).on('scroll', function() {
				// update the stick_upto_scroll if normal main header navigation is opend.
				self.stick_me( self, 'scroll' );
			} );

			jQuery( document ).ready(function($) {
				self.stick_me( self );
			} );

		}

	};

	$.fn[pluginName] = function ( options ) {
		return this.each(function () {
			if ( ! $.data( this, 'plugin_' + pluginName )) {
				$.data( this, 'plugin_' + pluginName, new astHookExtSticky( this, options ) );
			}
		});
	}



	var $body = jQuery( 'body' ),
		layout_width             = $body.width(),
		site_layout              = astraAddon.site_layout || '',
		hook_sticky_header = astraAddon.hook_sticky_header || '',
		hook_shrink_header = astraAddon.hook_shrink_header || '';
		sticky_header_on_devices = astraAddon.hook_sticky_header_on_devices || 'desktop',
		site_layout_box_width    = astraAddon.site_layout_box_width || 1200,
		hook_sticky_footer = astraAddon.hook_sticky_footer || '',
		sticky_footer_on_devices = astraAddon.hook_sticky_footer_on_devices || 'desktop';



		switch ( site_layout ) {
			case 'ast-box-layout':
				layout_width = parseInt( site_layout_box_width );
			break;
		}

		jQuery( document ).ready(function($) {
			// Any stick header is enabled?
			if ( 'enabled' == hook_sticky_header ) {

				jQuery( '.ast-custom-header' ).astHookExtSticky({
					sticky_on_device: sticky_header_on_devices,
					header_style: 'none',
					site_layout: site_layout,
					max_width: layout_width,
					active_shrink: hook_shrink_header,
				});

			}

			// Any stick footer is enabled?
			if ( 'enabled' == hook_sticky_footer ) {

				jQuery( '.ast-custom-footer' ).astHookExtSticky({
					sticky_on_device: sticky_footer_on_devices,
					max_width: layout_width,
					site_layout: site_layout,
					header_style: 'none',
				});

			}
	    });

}(jQuery, window));
/**
 * Advanced Search Styling
 *
 * @package Astra Addon
 * @since 1.4.8
 */

( function() {

	function body_iphone_classes() {
		var iphone = ( navigator.userAgent.match(/iPhone/i) == 'iPhone' ) ? 'iphone' : '';
		var ipod   = ( navigator.userAgent.match(/iPod/i) == 'iPod' ) ? 'ipod' : '';

		document.body.className += ' ' + iphone;
		document.body.className += ' ' + ipod;
	}
	body_iphone_classes();

	function remove_style_class( style ) {
		var allClasses = document.body.className;
		allClasses = allClasses.replace( style, '' );
    	document.body.className = allClasses;
	}

	function add_style_class( style ) {
		document.body.className += ' ' + style;
	}

	// Helper Function.
	function fade_in( element ) {

		element.style.display = 'block';
		setTimeout(function() {
			element.style.opacity = 1;
		}, 1);
	}

	function fade_out( element ) {

		element.style.opacity = '';
		setTimeout(function() {
			element.style.display = '';
		}, 200);
	}

	function header_cover_form_height( current_header_cover_form ) {

		// Primary header cover search.
		if ( document.body.classList.contains('ast-header-break-point') ) {

			var site_navigation = document.querySelector( '.main-navigation' );
			var main_header_bar = document.querySelector( '.main-header-bar' );

			if( null !== main_header_bar && null !== site_navigation ) {

				var site_navigation_outer_height = site_navigation.offsetHeight;
				var main_header_outer_height     = main_header_bar.offsetHeight;

				// Have a navigation outer height.
				// And primary header NOT have the `No Toggle` style.
				if( site_navigation_outer_height && ( ! document.body.classList.contains('ast-no-toggle-menu-enable') ) ) {
					var search_height = parseFloat(site_navigation_outer_height) - parseFloat(main_header_outer_height);
				} else {
					var search_height = parseFloat(main_header_outer_height);
				}
				current_header_cover_form.style.maxHeight = Math.abs( search_height ) + "px";
			}
		}
	}

	function header_builder_cover_form_height( current_header_cover_form ) {

		// Primary header cover search.
		if ( document.body.classList.contains('ast-header-break-point') ) {

			var site_navigation = document.querySelector( '.main-navigation' );
			var main_header_bar = document.querySelector( '.main-header-bar' );
			var mobile_header_bar = document.querySelector( '.ast-mobile-header-wrap' );

			if( null !== main_header_bar && null !== site_navigation ) {

				var site_navigation_outer_height = site_navigation.offsetHeight;
				var main_header_outer_height     = main_header_bar.offsetHeight;
				var mobile_header_outer_height     = mobile_header_bar.offsetHeight;

				// Have a navigation outer height.
				// And primary header NOT have the `No Toggle` style.
				if( site_navigation_outer_height && ( ! document.body.classList.contains('ast-no-toggle-menu-enable') ) ) {
					var search_height = parseFloat(site_navigation_outer_height) - parseFloat(main_header_outer_height);
				} else {
					var search_height = parseFloat(main_header_outer_height);
				}
				if ( current_header_cover_form.parentNode.classList.contains( 'ast-mobile-header-wrap' ) ) {
					var search_height = parseFloat(mobile_header_outer_height);
				}

				current_header_cover_form.style.maxHeight = Math.abs( search_height ) + "px";
			}
		}
	}

	var searchIcons = document.querySelectorAll( 'a.astra-search-icon:not(.slide-search)' );

	for ( var i = 0; searchIcons.length > i; i++ ) {

			searchIcons[i].onclick = function ( evt ) {

				evt.preventDefault();

				if ( ! evt ) {
					evt = window.event;
				}

				if ( this.classList.contains( 'header-cover' ) ) {
					var header_cover = document.querySelectorAll( '.ast-search-box.header-cover' ),
						header_builder_active 	 = astraAddon.is_header_builder_active || false;

					for (var j = 0; j < header_cover.length; j++) {

						var header_cover_icon = header_cover[j].parentNode.querySelectorAll( 'a.astra-search-icon' );

						for (var k = 0; k < header_cover_icon.length; k++) {
							if ( header_cover_icon[k] == this ) {
								fade_in( header_cover[j] );
								header_cover[j].querySelector( 'input.search-field' ).focus();

								// Set header cover form height.
								if ( header_builder_active ) {
									header_builder_cover_form_height( header_cover[j] );
								} else {
									header_cover_form_height( header_cover[j] );
								}
							}
						};
					};

				} else if ( this.classList.contains( 'full-screen' ) ) {

					var fullScreen = document.getElementById( 'ast-seach-full-screen-form' );
					if ( fullScreen.classList.contains( 'full-screen' ) ) {
						fade_in( fullScreen );
						add_style_class( 'full-screen' );
						fullScreen.querySelector( 'input.search-field' ).focus();
					}
				}
			};
	};

	/* Search Header Cover & Full Screen Close */
	var closes = document.querySelectorAll( '.ast-search-box .close' );
	for (var i = 0, len = closes.length; i < len; ++i) {
		closes[i].onclick = function(evt){

			if ( ! evt) { evt = window.event;
			}
			var self = this;
			while ( 1 ) {
				if ( self.parentNode.classList.contains( 'ast-search-box' ) ) {
					fade_out( self.parentNode );
					remove_style_class( 'full-screen' );
					break;
				} else if ( self.parentNode.classList.contains( 'site-header' ) ) {
					break;
				}
				self = self.parentNode;
			}
		};
	}

	document.onkeydown = function ( evt ) {
		if ( evt.keyCode == 27 ) {
			var fullScreenForm = document.getElementById( 'ast-seach-full-screen-form' );

			if ( null != fullScreenForm ) {
				fade_out( fullScreenForm );
				remove_style_class( 'full-screen' );
			}

			var header_cover = document.querySelectorAll( '.ast-search-box.header-cover' );
			for (var j = 0; j < header_cover.length; j++) {
				fade_out( header_cover[j] );
			}
		}
	}

	window.addEventListener("resize", function() {

		if( 'BODY' !== document.activeElement.tagName ) {
			return;
		}

		// Skip resize event when keyboard display event triggers on devices.
		if( 'INPUT' != document.activeElement.tagName ) {
			var header_cover = document.querySelectorAll( '.ast-search-box.header-cover' );
			if ( ! document.body.classList.contains( 'ast-header-break-point' ) ) {
				for (var j = 0; j < header_cover.length; j++) {
					header_cover[j].style.maxHeight = '';
					header_cover[j].style.opacity = '';
					header_cover[j].style.display = '';
				}
			}
		}
	});

	let closeIcon = document.getElementById("close");
	if ( closeIcon ) {
		closeIcon.addEventListener("keydown", function (event) {
			if (event.key === "Enter") {
				event.preventDefault();
				this.click();
			} else if (event.key === "Tab") {
				event.preventDefault();
			}
		});
	}

} )();
