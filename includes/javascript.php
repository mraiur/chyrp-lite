<?php
    define('JAVASCRIPT', true);
    require_once "common.php";
?>
$(function() {
    if (Site.ajax)
        Post.prepare_links();

    if (Route.action == "register")
        Passwords.check("input[type='password']#password1", "input[type='password']#password2");

    if (Route.action == "controls")
        Passwords.check("input[type='password']#new_password1", "input[type='password']#new_password2");
});
var Route = {
    action: "<?php echo $_GET['action']; ?>"
}
var Site = {
    url: '<?php echo $config->chyrp_url; ?>',
    key: '<?php if (logged_in() and strpos($_SERVER["HTTP_REFERER"], $config->url) === 0) echo token($_SERVER["REMOTE_ADDR"]); ?>',
    ajax: <?php echo($config->enable_ajax ? "true" : "false"); ?>

}
var Passwords = {
    check: function(selector_primary, selector_confirm) {
        $(selector_primary).keyup(function(e) {
            if (passwordStrength($(this).val()) < 75)
                $(this).removeClass("strong");
            else
                $(this).addClass("strong");
        });
        $(selector_primary).parents("form").on("submit", function(e) {
            if ($(selector_primary).val() !== $(selector_confirm).val()) {
                e.preventDefault();
                alert('<?php echo __("Passwords do not match."); ?>');
            }
        });
    }
}
var Post = {
    id: 0,
    failed: false,
    edit: function(id) {
        Post.id = id;
        $("#post_" + id).loader();
        $.post(Site.url + "/includes/ajax.php", {
            action: "edit_post",
            id: id,
            hash: Site.key
        }, function(data) {
            if (isError(data)) {
                Post.panic();
                return;
            }

            $("#post_" + id).fadeOut("fast", function() {
                $(this).loader(true);
                $(this).replaceWith(data);
                $(window).scrollTop($("#post_edit_form_" + id).offset().top);
                $("#post_edit_form_" + id).css("opacity", 0).animate({ opacity: 1 }, function() {
                    $("#more_options_link_" + id).click(function(e) {
                        e.preventDefault();

                        if ($("#more_options_" + id).css("display") == "none") {
                            $(this).empty().append('<?php echo __("&uarr; Fewer Options"); ?>');
                            $("#more_options_" + id).slideDown("slow");
                        } else {
                            $(this).empty().append('<?php echo __("More Options &darr;"); ?>');
                            $("#more_options_" + id).slideUp("slow");
                        }
                    });
                    $("#post_edit_form_" + id).on("submit", function(e) {
                        e.preventDefault();

                        if (!Post.failed && !!window.FormData) {
                            $(this).loader();
                            $.ajax({
                                type: "POST",
                                url: $(this).attr("action"),
                                data: new FormData(this),
                                processData: false,
                                contentType: false,
                                dataType: "text",
                                error: Post.panic
                            }).done(Post.updated);
                        }
                    });
                    $("#post_cancel_edit_" + id).click(function(e) {
                        e.preventDefault();

                        if (!Post.failed) {
                            $("#post_edit_form_" + id).loader();
                            $.post(Site.url + "/includes/ajax.php", {
                                action: "view_post",
                                context: Route.action,
                                id: id,
                                reason: "cancelled"
                            }, function(data) {
                                $("#post_edit_form_" + id).fadeOut("fast", function() {
                                    $(this).loader(true);
                                    $(this).replaceWith(data);
                                    $(this).hide().fadeIn("fast");
                                });
                            }, "html").fail(Post.panic);
                        }
                    });
                });
            });
        }, "html").fail(Post.panic);
    },
    updated: function(response) {
        id = Post.id;

        if (isError(response)) {
            Post.panic();
            return;
        }

        if (Route.action != "drafts" && Route.action != "view" && $("#post_edit_form_" + id + " select#status").val() == "draft") {
            $("#post_edit_form_" + id).fadeOut("fast", function() {
                $(this).loader(true);
                alert('<?php echo __("Post has been saved as a draft."); ?>');
            })
        } else if (Route.action == "drafts" && $("#post_edit_form_" + id + " select#status").val() != "draft") {
            $("#post_edit_form_" + id).fadeOut("fast", function() {
                $(this).loader(true);
                alert('<?php echo __("Post has been published."); ?>');
            })
        } else {
            $.post(Site.url + "/includes/ajax.php", {
                action: "view_post",
                context: Route.action,
                id: id,
                reason: "edited"
            }, function(data) {
                $("#post_edit_form_" + id).fadeOut("fast", function() {
                    $(this).loader(true);
                    $(this).replaceWith(data);
                    $("#post_" + id).hide().fadeIn("fast");
                });
            }, "html").fail(Post.panic);
        }
    },
    destroy: function(id) {
        $("#post_" + id).loader();
        $.post(Site.url + "/includes/ajax.php", {
            action: "delete_post",
            id: id,
            hash: Site.key
        }, function(response) {
            $("#post_" + id).loader(true);

            if (isError(response)) {
                Post.panic();
                return;
            }

            $("#post_" + id).fadeOut("fast", function() {
                $(this).remove();

                if (Route.action == "view")
                    window.location = '<?php echo $config->url; ?>';
            });
        }, "html").fail(Post.panic);
    },
    prepare_links: function(id) {
        $(".post").last().parent().on("click", ".post_edit_link:not(.no_ajax)", function(e) {
            if (!Post.failed) {
                e.preventDefault();
                var id = $(this).attr("id").replace(/post_edit_/, "");
                Post.edit(id);
            }
        });
        $(".post").last().parent().on("click", ".post_delete_link:not(.no_ajax)", function(e) {
            if (!Post.failed) {
                e.preventDefault();

                if (confirm('<?php echo __("Are you sure you want to delete this post? If you wish to hide it, save it as a draft."); ?>')) {
                    var id = $(this).attr("id").replace(/post_delete_/, "");
                    Post.destroy(id);
                }
            }
        });
    },
    panic: function() {
        Post.failed = true;
        alert('<?php echo __("Oops! Something went wrong on this web page."); ?>');
        $(".ajax_loading").loader(true);
    }
}
<?php $trigger->call("javascript"); ?>
