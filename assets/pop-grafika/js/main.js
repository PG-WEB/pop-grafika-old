$(document).ready(function() {
    $(".submenu").hide();
    $(".menu_parent.active .submenu").show();
    $(".menu_cat_link").click(function () {
        $(this).siblings(".submenu").slideToggle("fast");
        return false;
    });
});
