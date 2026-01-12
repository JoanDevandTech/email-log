var exportLogs = (function($) {
    'use strict';

    // Top Btn element ID.
    var doActionBtnTopId = 'doaction',
        // Top Btn element ID.
        doActionBtnBottomId = 'doaction2',

        // "Apply" Btn located at the top of the View logs table.
        doActionBtnTop,
        // "Apply" Btn located at the bottom of the View logs table.
        doActionBtnBottom,

        // Bulk Action Dropdown located at the top of the View logs table.
        bulkActionSelectorTopId = 'bulk-action-selector-top',
        // Bulk Action Dropdown located at the bottom of the View logs table.
        bulkActionSelectorBottomId = 'bulk-action-selector-bottom',

        // Email Log checkbox that allows to export by ID.
        emailLogChkbox,

        // Export Logs Columns form element
        exportLogsColumnsFormElemId = 'form#el-select-export-logs-columns-form',

        // Select All Logs checkbox located at the top of the View logs table.
        selectAllLogsTopChkbox,
        // Select All Logs checkbox located at the bottom of the View logs table.
        selectAllLogsBottomChkbox,

        customDateFormatInputTxtClass = 'el-export-logs--column-list-item--custom-format';

    /**
     * Entry point for the module.
     */
    var init = function() {
        _cacheElems();
        _attachBtnHandlers();
        _hideCustomFormatInput();
        _handleDateFormatDropdownActions();
        _handleMoreFieldsUpsell();
    };

    /**
     * Caches the DOM elements for efficiency.
     * @private
     */
    var _cacheElems = function() {
        doActionBtnTop = $( '#' + doActionBtnTopId );
        doActionBtnBottom = $( '#' + doActionBtnBottomId );
        emailLogChkbox = $( 'input[name="email-log[]"]' );
        selectAllLogsTopChkbox = $( '#cb-select-all-1' );
        selectAllLogsBottomChkbox = $( '#cb-select-all-2' );
    };

    /**
     * Attach Btn handlers and describe the events later.
     *
     * This uses Browsers API/Event loop. Defining the actual functions later
     * helps to render pages swiftly.
     *
     * @private
     */
    var _attachBtnHandlers = function() {
        doActionBtnTop.click( _actionBtnHandler );
        doActionBtnBottom.click( _actionBtnHandler );
        emailLogChkbox.change( _emailLogChkboxHandler );
        selectAllLogsTopChkbox.change( _selectAllLogsHandler );
        selectAllLogsBottomChkbox.change( _selectAllLogsHandler );
    };

    /**
     * Handler the for the Bulk Action button.
     * @param e
     * @private
     */
    var _actionBtnHandler = function( e ) {
        var actionsToHandle = {
                export    : 'el-log-list-export',
                exportAll : 'el-log-list-export-all'
            },
            // User selected action.
            actionSelected = _getBulkActionSelectedOptionValue( e ),
            // Hidden element to store if 'Export' or 'Export All' option is selected.
            bulkActionSelectedHdn = $( '#el-action-selected' );

        // Stop propagation only when "Export" or "Export All Logs" option is selected.
        if ( ! Object.values( actionsToHandle ).includes( actionSelected ) ) {
            return;
        }

        // Stop propagation.
        e.preventDefault();

        bulkActionSelectedHdn.val( actionSelected );

        // TODO: Localize the following text.
        tb_show("Export Logs option:", "#TB_inline?inlineId=el-export-logs--column-list-wrapper");
    };

    var _emailLogChkboxHandler = function( e ) {
        if ( $( this ).is(":checked") ) {
            _createHdnInputByNameAndValue( 'email-log[]', e.target.value )
                .appendTo( exportLogsColumnsFormElemId );
        } else {
            $( 'input[type="hidden"][value="' + e.target.value + '"]' ).remove();
        }
    };

    var _selectAllLogsHandler = function() {
        if ( $( this ).is(":checked") ) {
            emailLogChkbox.each( function() {
                _createHdnInputByNameAndValue( 'email-log[]', $( this ).val() )
                    .appendTo( exportLogsColumnsFormElemId );
            } );
        } else {
            $( 'input[type="hidden"][name="email-log[]"]' ).remove();
        }
    };

    var _getBulkActionSelectedOptionValue = function( e ) {
        var dropDownSelectorId;

        // Depending on the button, get the value from the nearest Dropdown.
        if ( e.target.id === doActionBtnTopId ) {
            dropDownSelectorId = bulkActionSelectorTopId;
        } else if ( e.target.id === doActionBtnBottomId ) {
            dropDownSelectorId = bulkActionSelectorBottomId;
        }

        return $( '#' + dropDownSelectorId + ' ' + 'option:selected' ).val();
    };

    var _createHdnInputByNameAndValue = function( name, value ) {
        return $( '<input />', {
            'type': 'hidden',
            'name': name,
            'value': value
        } );
    };

    var _hideCustomFormatInput = function() {
        $( '.' + customDateFormatInputTxtClass ).hide();
    };

    var _handleDateFormatDropdownActions = function () {
        $('#export-log-column-date-format').on('change', function () {
            if ('custom' === $(this).val()) {
                $('.' + customDateFormatInputTxtClass).show();
                return;
            }
            $('.' + customDateFormatInputTxtClass).hide();
        });
    };

    var _handleMoreFieldsUpsell = function() {
        if ( ! $( 'body' ).hasClass( 'more-fields-addon' ) ) {
            return;
        }

        $( '.el-export-logs--more-fields-upsell-list-item' ).hide();
    };

    return {
        init: init
    }
}(jQuery));

exportLogs.init();
