<div id="form_success" style="background-color: green; color:white;"></div>
<div id="form_error" style="background-color:red; color:white;"></div>
<form id='enquiry_form'>

    <?php wp_nonce_field('wp_rest') ?>
    <label>Name</label> <br />
    <input type="text" name="name" /><br />

    <label>Email</label> <br />
    <input type="text" name="email" /><br />

    <label>Phone</label> <br />
    <input type="text" name="phone" /><br />

    <label>Your Message</label> <br />
    <textarea name="message"></textarea><br />

    <button type="submit">Submit Form</button>
</form>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.3/jquery.min.js"></script>
<script>
    jQuery(document).ready(
        function($) {
            $("#enquiry_form").submit(function(event) {
                event.preventDefault();

                var form = $(this);

                $.ajax({
                    type: "POST",
                    url: "<?php echo get_rest_url(null, 'v1/contact-form/submit') ?>",
                    data: form.serialize(),
                    success: function(res) {
                        form.hide();
                        $("#form_success").html(res).fadeIn();
                    },
                    error: function(err) {
                        $("#form_error").html(err).fadeIn();
                    }
                });
            });

        }
    );
</script>