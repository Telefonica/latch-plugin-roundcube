<?php
/*
  Latch Roundcube plugin - Integrates Latch into the Roundcube authentication process.
  Copyright (C) 2013 Eleven Paths

  This library is free software; you can redistribute it and/or
  modify it under the terms of the GNU Lesser General Public
  License as published by the Free Software Foundation; either
  version 2.1 of the License, or (at your option) any later version.

  This library is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
  Lesser General Public License for more details.

  You should have received a copy of the GNU Lesser General Public
  License along with this library; if not, write to the Free Software
  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 */
?>
<html>
    <head>
        <style>
            .twoFactorContainer { display:block; width:300px; margin: 5% auto 0 auto; text-align: center; border: solid 1px rgb(184, 184, 184); border-radius:5px}
            .twoFactorHeader {float:left; background: #00b9be; color: #FFF; width:100%; border-top-left-radius: 5px; border-top-right-radius: 5px; font-family: sans-serif;}
            .twoFactorHeader h3 {float: left; margin-left: 10px;}
            .twoFactorHeader img {width: 45px; height: auto; float:left; margin-top: 5px; margin-left:20px}
            .twoFactorForm {clear:left; padding-top:10px;}
            input {margin-top:10px}
            input[type="submit"] {width:65px;}
        </style>
    </head>
    <body>
        <div class="twoFactorContainer">
            <div class="twoFactorHeader">
                <img src="plugins/latchRC/symbol.png">
                <h3>One-time password</h3>
            </div>
            <div class="twoFactorForm">
                <form method="post">
                    <label for="latch_twoFactor">Type your one-time password:</label>
                    <input type="hidden" name="_task" value="<?php echo htmlspecialchars($_POST['_task']); ?>">
                    <input type="hidden" name="_action" value="<?php echo htmlspecialchars($_POST['_action']); ?>">
                    <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="_user" value="<?php echo htmlspecialchars($_POST['_user']); ?>">
                    <input type="hidden" name="_pass" value="<?php echo htmlspecialchars($_POST['_pass']); ?>">
                    <?php if (isset($_POST['_host'])) { // In case it is not configured by default ?>
                    <input type="hidden" name="_host" value="<?php echo htmlspecialchars($_POST['_host']); ?>">
                    <?php } ?>
                    <input type="text" name="latch_twoFactor"><br>
                    <input type="submit" value="Log in">
                </form>
            </div>
        </div>
    </body>
</html>