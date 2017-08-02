jQuery(function() {
  jQuery(".delete, .restore").click(function() {
    return confirm("Are you sure?");
  });
  jQuery("table.data tr:odd").addClass("odd");
	jQuery("table.data tr:even").addClass("even");

	jQuery("html,body").animate({
    scrollTop: jQuery("ins, del").offset().top
  }, 2000);

});