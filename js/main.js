(function($) {
  '$:nomunge'; // Used by YUI compressor.
  $.fn.resizePage = function() {
      h = 0
      this.each(function(){
        h = Math.max(h, $(this).height() );
      });
      $("#page").css({"height": h + "px"});

  };
  
})(jQuery);

$(window).load(function() {
    $(".column").resizePage();
});

$(document).ready(function() {
    var context = '';

    $(".column").resizePage();
    $("a[target=new]").fancybox();
    $("a.iframe").fancybox({"width": 600, "height": 450});
    $(".submenu").hide();
    $(".menu_parent.active > .submenu").show();
    $(".menu_cat_link").click(function () {
        context = this;
        $(this).siblings(".submenu").slideToggle("fast", function (e) {
                        if ($(context).siblings(".submenu").is(":visible"))
                            $(context).addClass("opened");
                        else
                            $(context).removeClass("opened");
                        $(".column").resizePage();
                    });
        return false;
    });

//    $(".column").equalizeBottoms();
    $(".column").resizePage();
});
