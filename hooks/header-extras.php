<?php

?>
<script src="hooks/AppGiniHelper.min.js"></script>
<script>
    var common = AppGiniHelper.getCommon();
    common.setTitle("<b>DonorSoft</b>");
    common.setIcon("gift");

</script>

<script>
    var lv = AppGiniHelper.LV;
    if (lv != null) {
        lv.setBackgroundGradient("whitesmoke", "silver")
            .setVariation(Variation.primary) // .success .danger .warning .info
            .setIcons("user", "lock")
            .removeLostPassword()
            .removeRememberMe()
            .removeFooter()
            .center();
    }
</script>