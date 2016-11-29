jQuery(document).ready(function ($) {

    $('.color-picker').wpColorPicker();

    /**
     * Manipulate bacs table.
     */
    $( '.seed-confirm-table .remove_rows' ).click( function() {
        var $tbody = $( this ).closest( '.seed-confirm-table' ).find( 'tbody' );
        if ( $tbody.find( 'tr.current' ).length > 0 ) {
            var $current = $tbody.find( 'tr.current' );
            $current.each( function() {
                $( this ).remove();
            });
        }
        return false;
    });

    $( '.seed-confirm-table.sortable tbody' ).sortable({
        items: 'tr',
        cursor: 'move',
        axis: 'y',
        scrollSensitivity: 40,
        forcePlaceholderSize: true,
        helper: 'clone',
        opacity: 0.65,
        placeholder: 'wc-metabox-sortable-placeholder',
        start: function( event, ui ) {
            ui.item.css( 'background-color', '#f6f6f6' );
        },
        stop: function( event, ui ) {
            ui.item.removeAttr( 'style' );
        }
    });

    $( '.seed-confirm-table .remove_rows' ).click( function() {
        var $tbody = $( this ).closest( '.seed-confirm-table' ).find( 'tbody' );
        if ( $tbody.find( 'tr.current' ).length > 0 ) {
            var $current = $tbody.find( 'tr.current' );
            $current.each( function() {
                $( this ).remove();
            });
        }
        return false;
    });

    var controlled = false;
    var shifted    = false;
    var hasFocus   = false;

    $( document.body ).bind( 'keyup keydown', function( e ) {
        shifted    = e.shiftKey;
        controlled = e.ctrlKey || e.metaKey;
    });

    $( '.seed-confirm-table' ).on( 'focus click', 'input', function( e ) {
        var $this_table = $( this ).closest( 'table, tbody' );
        var $this_row   = $( this ).closest( 'tr' );

        if ( ( e.type === 'focus' && hasFocus !== $this_row.index() ) || ( e.type === 'click' && $( this ).is( ':focus' ) ) ) {
            hasFocus = $this_row.index();

            if ( ! shifted && ! controlled ) {
                $( 'tr', $this_table ).removeClass( 'current' ).removeClass( 'last_selected' );
                $this_row.addClass( 'current' ).addClass( 'last_selected' );
            } else if ( shifted ) {
                $( 'tr', $this_table ).removeClass( 'current' );
                $this_row.addClass( 'selected_now' ).addClass( 'current' );

                if ( $( 'tr.last_selected', $this_table ).length > 0 ) {
                    if ( $this_row.index() > $( 'tr.last_selected', $this_table ).index() ) {
                        $( 'tr', $this_table ).slice( $( 'tr.last_selected', $this_table ).index(), $this_row.index() ).addClass( 'current' );
                    } else {
                        $( 'tr', $this_table ).slice( $this_row.index(), $( 'tr.last_selected', $this_table ).index() + 1 ).addClass( 'current' );
                    }
                }

                $( 'tr', $this_table ).removeClass( 'last_selected' );
                $this_row.addClass( 'last_selected' );
            } else {
                $( 'tr', $this_table ).removeClass( 'last_selected' );
                if ( controlled && $( this ).closest( 'tr' ).is( '.current' ) ) {
                    $this_row.removeClass( 'current' );
                } else {
                    $this_row.addClass( 'current' ).addClass( 'last_selected' );
                }
            }

            $( 'tr', $this_table ).removeClass( 'selected_now' );
        }
    }).on( 'blur', 'input', function() {
        hasFocus = false;
    });

    $('.seed-confirm-table').on( 'click', 'a.add', function(){

        var size = $('.seed-confirm-table').find('tbody .account').length;

        $('<tr class="account">\
                <td class="sort"></td>\
                <td><input type="text" name="bacs_account_name[' + size + ']" /></td>\
                <td><input type="text" name="bacs_account_number[' + size + ']" /></td>\
                <td><input type="text" name="bacs_bank_name[' + size + ']" /></td>\
                <td><input type="text" name="bacs_sort_code[' + size + ']" /></td>\
                <td><input type="text" name="bacs_iban[' + size + ']" /></td>\
                <td><input type="text" name="bacs_bic[' + size + ']" /></td>\
            </tr>').appendTo('.seed-confirm-table tbody');

        return false;
    });

});