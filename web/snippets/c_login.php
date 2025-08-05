<?php // https://oauth2-client.thephpleague.com/providers/league/ ?>
<main class="col-md-12 ms-sm-auto col-lg-12 px-md-4" id="contentMain">
    <H1><?php _s("Please login", __FILE__, $_SESSION["LANG"]); ?></H1>
    <p>
        <?php _s("You may login with your email address and password or your WhatsApp phone number and password.", __FILE__, $_SESSION["LANG"]); ?><BR>
        <?php _s("If you have NOT yet set your password, please send <B>\"/link\"</B> to our AI address (smart@synaplan.com) or the WhatsApp numbers published on the website.", __FILE__, $_SESSION["LANG"]); ?>
    </p>
    <form action="index.php" method="post">
        <input type="hidden" name="action" value="login">
        <div class="form-group mt-2">
            <label for="email"><?php _s("Email", __FILE__, $_SESSION["LANG"]); ?></label>
            <input type="email" class="form-control mt-2" id="email" name="email" placeholder="<?php _s("Enter your registered email", __FILE__, $_SESSION["LANG"]); ?>">
        </div>
        <div class="form-group mt-2">
            <label for="password"><?php _s("Password", __FILE__, $_SESSION["LANG"]); ?></label>
            <input type="password" class="form-control mt-2" id="password" name="password" placeholder="<?php _s("Enter password", __FILE__, $_SESSION["LANG"]); ?>">
        </div>
        <button type="submit" class="btn btn-primary mt-2"><?php _s("Login", __FILE__, $_SESSION["LANG"]); ?></button>
    </form>
    <BR>
    <p><?php _s("Go to our homepage for more information: <a href=\"https://www.synaplan.com/\">https://synaplan.com/</a>", __FILE__, $_SESSION["LANG"]); ?></p>
</main>