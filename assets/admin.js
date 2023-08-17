jQuery(document).ready(function($) {
    // Handle tab changes
    // $('a.nav-tab').on('click', function(e) {
    //     e.preventDefault();

    //     // Remove active class from all tabs
    //     $('a.nav-tab').removeClass('nav-tab-active');

    //     // Add active class to clicked tab
    //     $(this).addClass('nav-tab-active');

    //     // Hide all tab content
    //     $('.tab-content').hide();

    //     // Show clicked tab content
    //     $($(this).attr('href')).show();
    // });

    // Handle reset to default
    $('#reset_default').on('click', function(e) {
        e.preventDefault();

        // Reset values to defaults
        $('#sssg_base_path').val('static');
        $('#sssg_assets_path').val('/assets');
        $('#sssg_base_url').val($('#siteurl').val() + '/static');
        $('#sssg_export_mode').val('automatic');
        $('#sssg_export_target').val('full');
    });

    // Trigger click on first tab to display it on page load
    // $('a.nav-tab').first().trigger('click');
});
