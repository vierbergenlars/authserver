$(function() {
    /**
     * Disable dropdown menu collapse on click on input field.
     */
    $('.dropdown-menu .input-group').on('click', function(e) {
        e.stopPropagation();
    });

    /**
     * Show a property as active
     */
    var setPropertyActive = function($property) {
        $property.parent().addClass('active');
        $property.find('.fa').removeClass('fa-circle-o').addClass('fa-dot-circle-o');
    }

    /**
     * Show a property as inactive
     */
    var setPropertyInactive = function($property) {
        $property.parent().removeClass('active');
        $property.find('.fa').addClass('fa-circle-o').removeClass('fa-dot-circle-o');
    }

    /**
     * Search query handler
     */
    var searchQuery = (function($searchField) {
        return {
            /**
             * Disassemble search query
             */
            disassemble: function() {
                var val = $searchField.val();
                var parsed = searchGrammar.parse(val);
                var fields = {};
                var properties = [];
                $.each(parsed, function(_, block) {
                    if(block.name == 'is') {
                        properties.push(block.value);
                    } else {
                        fields[block.name] = block.value;
                        $('.js-search-example[data-prefix="'+block.name+'"]').val(block.value);
                    }
                });
                $searchField.data('fields', fields);
                $searchField.data('properties', properties);
                searchQuery.restyleProperties();
            },
            /**
             * Restyle all properties
             */
            restyleProperties: function() {
                var properties = $searchField.data('properties');
                setPropertyInactive($('.js-search-example[data-property]'));
                $.each(properties, function(_, property) {
                    setPropertyActive($('.js-search-example[data-property="'+property+'"]'));
                });
            },
            /**
             * Assemble search query
             */
            assemble: function() {
                var fields = $searchField.data('fields');
                var assembledFields = $.map(fields, function(v, k) {
                    if(!v) {
                        return null;
                    } else if(/^[a-zA-Z0-9]+$/.exec(v)) { // Must match Identifier regexp in search grammar
                        return ''+k+':'+v;
                    } else {
                        return ''+k+': \''+v+'\'';
                    }
                });
                var properties = $searchField.data('properties');
                var assembledProperties = $.map(properties, function(v) {
                    return 'is:'+v;
                });
                var join = Array.prototype.join;
                var val = join.call(assembledFields, ' ') + ' ' + join.call(assembledProperties, ' ');
                $searchField.val(val.trim()).trigger('change');
            }
        };
    })($('.js-search-field'));

    /**
     * Normalize the search field when the examples dropdown gets opened
     */
    $('.js-search-field').parent().find('[data-toggle=dropdown]').on('click', function() {
        try {
            searchQuery.disassemble();
            searchQuery.assemble();
        } catch(e) {
            // noop
        }
    });

    /**
     * Mark search field as errored on syntax error
     */
    $('.js-search-field').on('keyup change', function() {
        try {
            searchQuery.disassemble();
            $(this).parents('.form-group').removeClass('has-error');
        } catch(e) {
            $(this).parents('.form-group').addClass('has-error');
        }
    });

    /**
     * Handle search fields
     */
    $('.js-search-example[data-prefix]').on('keyup', function() {
        var val = $(this).val();
        var prefix = $(this).data('prefix');
        var fields = $('.js-search-field').data('fields');
        fields[prefix] = val;
        $('.js-search-field').data('fields', fields);
        searchQuery.assemble();
    });

    /**
     * Handle search properties
     */
    $('.js-search-example[data-property]').on('click', function(e) {
        e.stopPropagation();
        e.preventDefault();
        // Property belonging to this button
        var prop = $(this).data('property');
        // Properties that should be removed to activate the property on this button
        var removeProps = $(this).data('remove-property').split(',');

        var properties = $('.js-search-field').data('properties');
        if($.inArray(prop, properties) >= 0) {
            // Property already was selected, deselect it
            properties = $.grep(properties, function(v) {
                return v != prop;
            });
        } else {
            // Property was not selected, select it and deselect all conflicts
            properties.push(prop);
            properties = $.grep(properties, function(v) {
                return $.inArray(v, removeProps) == -1;
            });
        }
        $('.js-search-field').data('properties', properties);
        searchQuery.assemble();
        searchQuery.restyleProperties();
    });
});
