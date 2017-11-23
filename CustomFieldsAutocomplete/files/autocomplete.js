// Set new selected value
$('a.auto-value').click(function (evt) {
    evt.preventDefault();
    $("#" + $(this).data('field')).val($(this).data('value'));
});

// Process pressed keys
$('input.custom-value').keyup(function (evt) {
    // Cache new value
    var newValue = $(this).val();
    // Open dropdown menu if it closed
    if ($('div.' + $(this).attr('id')).find('.dropdown-menu').is(":hidden"))
    {
        $(this).dropdown('toggle');
    }
    // Hide not matched and show matched values
    $("#" + $(this).attr('id') + '-values').find('li').each(function (idx, elem) {
        if ($(elem).find('a').data('value').toString().toLowerCase().indexOf(newValue.toLowerCase(), 0) === -1) {
            $(elem).hide();
        } else {
            $(elem).show();
        }
    });
});