<?php

class InlineDV
{
    private $tn;
    function __construct($tn)
    {
        $this->tn = $tn;
    }

    public function render($selectedID, $memberInfo, &$html, &$args)
    {
        if (!$selectedID) return;
        $html .= '<script>
    var dv = jQuery("a[name=\"detail-view\"]").closest("div.detail_view");
    // bugfix for AG version 5.97 --->
    if (dv.length==0) dv = jQuery("#detail-view").closest("div.detail_view");
    // <---
    var target = jQuery("tr[data-id].active");
    
    var panel_variation = "default";
    // 2021/09/14 JSE renamed variable "scroll" due to naming conflict
    var idv_scroll = false;
    if (dv.length && target.length) {
        target.addClass("active").find("td").css("background-color", "inherit").css("color", "inherit");
        dv.hide();
        var colspan = target.find("td").length;
        var tr = jQuery("<tr/>").insertAfter(target);
        // target.find("td").addClass("text-muted");
        var td_first = jQuery("<td/>").appendTo(tr);
        var td = jQuery("<td/>").attr("colspan", colspan).appendTo(tr).css("padding",0);
        dv.prependTo(td);
        var selector_panelbody = "#appginihelper-inlinedv-panelbody";
        var ico_close = jQuery("<i/>").addClass("glyphicon glyphicon-remove");
        var ico_hide = jQuery("<i/>").addClass("glyphicon glyphicon-triangle-bottom");
        var btn_close = jQuery("<a/>").attr("href", "' . $this->tn . '_view.php").addClass("btn-xs btn btn-default").append(ico_close);
        var btn_hide = jQuery("<a/>").attr("id", "appginihelper-inlinedv-btn-toggle").attr("href", selector_panelbody).attr("data-toggle", "collapse").addClass("btn-xs btn btn-default").append(ico_hide);
        var heading = dv.find(".panel-heading").attr("id", "appginihelper-plugin-inlinedv-panelheading");
        // heading.closest(".panel").addClass("panel-primary").removeClass("panel-default");
        var heading_title = heading.find("h3").attr("href", selector_panelbody).attr("data-toggle", "collapse").css("cursor", "pointer");
        btn_hide.clone().prependTo(heading_title).removeClass("btn-xs").addClass("btn-link");
        var clearfix =jQuery("<div/>").addClass("clearfix").appendTo(heading);
        
        dv.find(".panel").css("margin-bottom",0).css("border", 0).removeClass("panel-default").addClass("panel-" + panel_variation);
        var panelbody = dv.find(".panel > .panel-body").attr("id", "appginihelper-inlinedv-panelbody").addClass("collapse in");
        var btn_group_target = panelbody;
        var btn_group = jQuery("<div/>").attr("id", "appginihelper-plugin-inlinedv-buttons").addClass("btn-group pull-right")
        .prependTo(heading)
        .append(btn_hide).append(btn_close);
        
        dv.css("padding",0);
        dv.show();
        // bigfix AG 5.97 --->
        jQuery("#appginihelper-inlinedv-panelbody").hide().removeClass("hidden").show();
        // <--

        jQuery("#appginihelper-inlinedv-panelbody").on("show.bs.collapse", function () {
            jQuery("#appginihelper-inlinedv-btn-toggle > i.glyphicon").removeClass("glyphicon-triangle-right").addClass("glyphicon-triangle-bottom");
         });
         
         jQuery("#appginihelper-inlinedv-panelbody").on("hide.bs.collapse", function () {
            jQuery("#appginihelper-inlinedv-btn-toggle > i.glyphicon").removeClass("glyphicon-triangle-bottom").addClass("glyphicon-triangle-right");
         });


        // 2021/09/14 JSE renamed variable "scroll"
        if (idv_scroll) {   
            var offset = target.offset();
            jQuery("html, body").animate({
                scrollTop: offset.top,
                scrollLeft: offset.left
            }, 1000);
        } else {
            dv[0].scrollIntoView({
                behavior: "smooth", // or "smooth" "auto" or "instant"
                block: "start" // or "end"
            });
        }

        
        jQuery(document).one("ajaxStop", function(){ 
             jQuery("#admin-tools-menu-button").prependTo("#appginihelper-plugin-inlinedv-buttons").removeClass("pull-right").addClass("pull-left");
         });

    }
</script>';
    }
}
