// assets/js/admin-scripts.js

jQuery(document).ready(function($){
    var cptIndex = $('#aac-cpt-container .aac-cpt-section').length;
    var taxIndex = {};

    $('#aac-add-cpt').on('click', function(e){
        e.preventDefault();
        var data = {
            action: 'aac_get_post_type_row',
            type: 'post_type',
            index: cptIndex,
            security: aac_ajax_object.ajax_nonce
        };
        $.post(ajaxurl, data, function(response){
            $('#aac-cpt-container').append(response);
            cptIndex++;
        });
    });

    $(document).on('click', '.aac-remove-cpt', function(e){
        e.preventDefault();
        $(this).closest('.aac-cpt-section').remove();
    });

    $(document).on('click', '.aac-add-taxonomy', function(e){
        e.preventDefault();
        var $container = $(this).siblings('.aac-taxonomies-container');
        var postTypeIndex = $(this).closest('.aac-cpt-section').index();
        taxIndex[postTypeIndex] = taxIndex[postTypeIndex] || $container.find('.aac-taxonomy-section').length;
        var data = {
            action: 'aac_get_post_type_row',
            type: 'taxonomy',
            index: taxIndex[postTypeIndex],
            post_type_index: postTypeIndex,
            security: aac_ajax_object.ajax_nonce
        };
        $.post(ajaxurl, data, function(response){
            $container.append(response);
            taxIndex[postTypeIndex]++;
        });
    });

    $(document).on('click', '.aac-remove-taxonomy', function(e){
        e.preventDefault();
        $(this).closest('.aac-taxonomy-section').remove();
    });

    // assets/js/admin-scripts.js

jQuery(document).ready(function($){
    var metaFieldIndex = $('#aac-meta-fields-container .aac-meta-field-row').length;

    $('#aac-add-meta-field').on('click', function(e){
        e.preventDefault();
        var data = {
            action: 'aac_get_meta_field_row',
            index: metaFieldIndex,
            security: aac_ajax_object.ajax_nonce
        };
        $.post(ajaxurl, data, function(response){
            $('#aac-meta-fields-container').append(response);
            metaFieldIndex++;
        });
    });

    $(document).on('click', '.aac-remove-meta-field', function(e){
        e.preventDefault();
        $(this).closest('.aac-meta-field-row').remove();
    });

    $(document).on('change', '.aac-field-type-select', function(){
        var fieldType = $(this).val();
        var $row = $(this).closest('.aac-meta-field-row');
        if ( fieldType === 'text' || fieldType === 'textarea' || fieldType === 'date' ) {
            $row.find('.aac-placeholder-field').show();
        } else {
            $row.find('.aac-placeholder-field').hide();
        }
        if ( fieldType === 'select' || fieldType === 'checkbox' || fieldType === 'radio' ) {
            $row.find('.aac-options-field').show();
        } else {
            $row.find('.aac-options-field').hide();
        }
    });
});

});