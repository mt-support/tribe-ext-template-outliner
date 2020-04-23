( function( $ ) {
    var $panel = $( '#tribe-ext-template-outliner-panel' );

    
    $(document).ready( function($) {
        $( 'body' ).get(0).style.setProperty( '--tribe-ext-template-outliner-color', template_outliner_vars.color );
    } );

    $( '.tribe-ext-template-outliner-toggle > a' ).on( 'click', function( event ) {
        event.stopPropagation();

        if ( 'true' === $panel.data( 'toggle-off' ) ) {
            $panel.data( 'toggle-off', 'false' );
        } else {
            $panel.data( 'toggle-off', 'true' );
            $panel.hide();
            $( '.tribe-ext-template-outliner-border' ).removeClass( 'tribe-ext-template-outliner-border' )
        }

        return false;
    } );

    $panel.on(
        'hover',
        function( event ) {
            if ( event.ctrlKey ) {
                return;
            }

            var xCord = event.screenX;
            var yCord = event.screenY;

            // Class cleanup.
            $panel.removeClass( 'tribe-ext-template-outliner-left tribe-ext-template-outliner-right tribe-ext-template-outliner-top tribe-ext-template-outliner-bottom' );

            // Reposition panel to allow mouse access to entire page.
            if ( 50 > ( xCord / $( window ).width() * 100 ) ) {
                $panel.addClass( 'tribe-ext-template-outliner-right' );
            } else {
                $panel.addClass( 'tribe-ext-template-outliner-left' );
            }

            if ( 50 > ( yCord / $( window ).height() * 100 ) ) {
                $panel.addClass( 'tribe-ext-template-outliner-bottom' );
            } else {
                $panel.addClass( 'tribe-ext-template-outliner-top' );
            }
        }
    );

    $panel.find( 'input' ).dblclick( function () {
        $( this ).select();
        document.execCommand( 'copy' );
    } );

    $('.tribe-ext-template-outliner').each(
    function( index ) {
        var $this  = $( this );
        var $next  = $this.next();

        $next.on( {
            mouseenter: function( event ) {
                if ( 'true' === $panel.data( 'toggle-off' ) ) {
                    return;
                }
                event.stopPropagation();
                event.stopImmediatePropagation();
                $panel.hide();
                var xCord = event.screenX;
                var yCord = event.screenY;

                if ( ! event.ctrlKey ) {
                    // Class cleanup.
                    $panel.removeClass( 'tribe-ext-template-outliner-left tribe-ext-template-outliner-right tribe-ext-template-outliner-top tribe-ext-template-outliner-bottom' );

                    // Reposition panel to allow mouse access to entire page.
                    if ( 50 > ( xCord / $( window ).width() * 100 ) ) {
                        $panel.addClass( 'tribe-ext-template-outliner-right' );
                    } else {
                        $panel.addClass( 'tribe-ext-template-outliner-left' );
                    }

                    if ( 50 > ( yCord / $( window ).height() * 100 ) ) {
                        $panel.addClass( 'tribe-ext-template-outliner-bottom' );
                    } else {
                        $panel.addClass( 'tribe-ext-template-outliner-top' );
                    }
                
                    // Update data in panel.
                    $( '#tribe_ext_tod_plugin_file' ).val( $this.data( 'plugin-file' ) );
                    $( '#tribe_ext_tod_theme_path' ).val( $this.data( 'theme-path' ) );
                    $( '#tribe_ext_tod_pre_html' ).val( $this.data( 'pre-html-filter' ) );
                    $( '#tribe_ext_tod_before_include' ).val( $this.data( 'before-include-action' ) );
                    $( '#tribe_ext_tod_after_include' ).val( $this.data( 'after-include-action' ) );
                    $( '#tribe_ext_tod_template_html' ).val( $this.data( 'template-html-filter' ) );
                    
                    // Add indicator class to hover target.
                    $next.addClass( 'tribe-ext-template-outliner-border' );
                }

                // Show the panel if it's hidden.
                $panel.show();
            },
            mouseleave: function( event ) {
                if ( 'true' === $panel.data( 'toggle-off' ) ) {
                    return;
                }
                event.stopPropagation();
                event.stopImmediatePropagation();

                if ( $next.parent().hasClass( 'tribe-ext-template-outliner' ) ) {
                    console.log('child->parent');
                }

                $next.removeClass( 'tribe-ext-template-outliner-border' );
            }
        } );

    } );

} )(jQuery)